#include "HX711.h"
#include <ESP32Servo.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <freertos/FreeRTOS.h>
#include <freertos/task.h>
#include <freertos/semphr.h>
#include <Preferences.h>

// Pin Definitions
const int bluePin = 25;
const int greenPin = 26;
const int redPin = 27;
const int servoPin1 = 5;   
const int servoPin2 = 18; 
const int servoPin3 = 21;  
const int LOADCELL_DOUT_PIN = 16;
const int LOADCELL_SCK_PIN = 4;

const int stability = 9;  
const float STABILITY_THRESHOLD = 0.3; 
const char* serverUrl = "http://192.168.137.1/poultryv2/api/insert-egg-data.php";

// One-time registration storage
Preferences prefs;
String wifiSSID;
String wifiPassword;
const char* registrationServerUrl = "http://192.168.137.1/poultryv2/api/register-device.php";

// Global Objects
Servo myServo1, myServo2, myServo3;
HX711 scale;
TaskHandle_t Servo2TaskHandle = NULL;
SemaphoreHandle_t weightMutex;
float currentWeight = 0;
String currentSize = "";
bool feedNextEgg = false;
bool eggPresent = false;
bool sortPending = false;
unsigned long sortStartTime = 0;

void setup() {
  Serial.begin(115200);

  // Reset option
  if (Serial.available()) {
    String cmd = Serial.readStringUntil('\n');
    cmd.trim();
    if (cmd.equalsIgnoreCase("RESET")) {
      prefs.begin("reg", false);
      prefs.clear();
      Serial.println("Registration cleared, restarting...");
      delay(500);
      ESP.restart();
    }
  }

  // One-time registration
  prefs.begin("reg", false);
  if (!prefs.getBool("registered", false)) {
    registerDevice();
  } else {
    wifiSSID = prefs.getString("wifi_ssid", "");
    wifiPassword = prefs.getString("wifi_password", "");
    Serial.println("Skipping registration, using stored credentials.");
  }

  connectWiFi();

  // Initialize hardware
  pinMode(LED_BUILTIN, OUTPUT);
  pinMode(redPin, OUTPUT);
  pinMode(greenPin, OUTPUT);
  pinMode(bluePin, OUTPUT);

  // Attach servos
  myServo1.attach(servoPin1);
  myServo2.attach(servoPin2);
  myServo3.attach(servoPin3);

  // Initialize scale
  scale.begin(LOADCELL_DOUT_PIN, LOADCELL_SCK_PIN);
  calibrateScale();
  scale.tare();

  // Create RTOS resources
  weightMutex = xSemaphoreCreateMutex();
  xTaskCreatePinnedToCore(
    Servo2Task, "Servo2Task", 2048, NULL, 1, &Servo2TaskHandle, 0);
}

void loop() {
  // Handle WiFi reconnection
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi disconnected. Reconnecting...");
    connectWiFi();
  }

  runTimeDetectIN();
  
  if (scale.is_ready()) {
    float weight = getStableWeight();
    
    if (weight >= 40 && !eggPresent) {
      eggPresent = true;
      String size = getSizeCategory(weight);
      Serial.printf("Weight: %.2f g\nSize: %s\n", weight, size.c_str());

      // Update shared variables
      if (xSemaphoreTake(weightMutex, portMAX_DELAY) == pdTRUE) {
        currentWeight = weight;
        currentSize = size;
        xSemaphoreGive(weightMutex);
      }

      // Control indicator servo
      controlIndicator(weight);

      // Send data if valid
      if (WiFi.status() == WL_CONNECTED) { 
        sendToServer(weight);
      }

      // Schedule sorting
      sortPending = true;
      sortStartTime = millis();
    }

    // Handle pending sorting operation
    if (sortPending && (millis() - sortStartTime >= 1000)) {
      runSorter();
      sortPending = false;
      
      // Prepare for next egg
      if (xSemaphoreTake(weightMutex, portMAX_DELAY) == pdTRUE) {
        feedNextEgg = true;
        eggPresent = false;
        xSemaphoreGive(weightMutex);
      }
      scale.tare();
    }

    runTimeDetectOUT();
    spacing();
  }
  delay(200);
}

// RTOS Task for Egg Feeder (Servo2)
void Servo2Task(void *pvParameters) {
  while (1) {
    if (xSemaphoreTake(weightMutex, portMAX_DELAY) == pdTRUE) {
      if (feedNextEgg) {
        feedNextEgg = false;
        xSemaphoreGive(weightMutex);
        
        // Feed egg sequence
        myServo2.write(0);
        vTaskDelay(100 / portTICK_PERIOD_MS);
        myServo2.write(180);
        vTaskDelay(500 / portTICK_PERIOD_MS);
        myServo2.write(0);
        Serial.println("Egg feeding completed");
      } else {
        xSemaphoreGive(weightMutex);
      }
    }
    vTaskDelay(100 / portTICK_PERIOD_MS);
  }
}

void controlIndicator(float weight) {
  if (weight >= 70) myServo1.write(180);
  else if (weight >= 65) myServo1.write(150);
  else if (weight >= 60) myServo1.write(120);
  else if (weight >= 55) myServo1.write(90);
  else if (weight >= 50) myServo1.write(60);
  else if (weight >= 45) myServo1.write(30);
  else myServo1.write(0);
}

void runSorter() {
  String size;
  if (xSemaphoreTake(weightMutex, portMAX_DELAY) == pdTRUE) {
    size = currentSize;
    xSemaphoreGive(weightMutex);
  }

  Serial.println("Starting sorting operation...");
  
  if (size == "Jumbo") myServo3.write(180);
  else if (size == "Extra Large") myServo3.write(150);
  else if (size == "Large") myServo3.write(120);
  else if (size == "Medium") myServo3.write(90);
  else if (size == "Small") myServo3.write(60);
  else if (size == "Pullets") myServo3.write(30);
  else if (size == "Pewee") myServo3.write(0);
  
  delay(500); // Hold position
  myServo3.write(0); // Return to neutral
  Serial.println("Sorting completed");
}

// Improved stable weight measurement
float getStableWeight() {
  const int numReadings = 21;
  float readings[numReadings];
  float sum = 0;

  // Collect and display readings
  Serial.println("Raw Readings:");
  for (int i = 0; i < numReadings; i++) {
    readings[i] = scale.get_units();
    sum += readings[i];
    Serial.printf("[%2d]: %6.2fg", i, readings[i]);
    if ((i+1) % 3 == 0) Serial.println();
    else Serial.print("\t");
    delay(50);
  }
  Serial.println();

  // Check for stability
  for (int i = 0; i <= numReadings - stability; i++) {
    float minVal = readings[i];
    float maxVal = readings[i];
    float windowSum = readings[i];
    
    for (int j = 1; j < stability; j++) {
      windowSum += readings[i+j];
      if (readings[i+j] < minVal) minVal = readings[i+j];
      if (readings[i+j] > maxVal) maxVal = readings[i+j];
    }
    
    if ((maxVal - minVal) <= STABILITY_THRESHOLD) {
      float avg = windowSum / stability;
      Serial.printf("Stable sequence: %d-%d, Avg: %.2fg\n", i, i+stability-1, avg);
      return avg;
    }
  }

  // Use middle average as fallback
  float middleSum = 0;
  const int middleStart = (numReadings - stability) / 2;
  for (int i = middleStart; i < middleStart + stability; i++) {
    middleSum += readings[i];
  }
  float middleAvg = middleSum / stability;
  Serial.printf("Using middle average: %.2fg\n", middleAvg);
  return middleAvg;
}

// Server Communication
void sendToServer(float weight) {
  HTTPClient http;
  DynamicJsonDocument doc(200);
  
  String deviceMAC = prefs.getString("device_mac", "");
  doc["mac"] = deviceMAC;
  doc["egg_weight"] = weight;
  
  String payload;
  serializeJson(doc, payload);

  http.begin(serverUrl);
  http.addHeader("Content-Type", "application/json");
  int httpCode = http.POST(payload);

  if (httpCode > 0) {
    Serial.printf("Server Response: %d\n%s\n", httpCode, http.getString().c_str());
  } else {
    Serial.printf("Error: %s\n", http.errorToString(httpCode).c_str());
  }
  http.end();
}

// Helper Functions
String getSizeCategory(float weight) {
  if (weight >= 70) return "Jumbo";
  else if (weight >= 65) return "Extra Large";
  else if (weight >= 60) return "Large";
  else if (weight >= 55) return "Medium";
  else if (weight >= 50) return "Small";
  else if (weight >= 45) return "Pullets";
  else if (weight >= 40) return "Pewee";
  else return "No weight";
}

void calibrateScale() {
  Preferences scalePrefs;
  scalePrefs.begin("scale", false);
  float calFactor = scalePrefs.getFloat("calFactor", 0.0);

  if (calFactor == 0.0) {
    Serial.println("Calibration Started");
    setRGBColor(255, 0, 0);
    delay(2000);
    scale.tare();

    Serial.println("Place 100g weight...");
    setRGBColor(0, 255, 0);
    delay(5000);

    float calibrationSum = 0;
    for (int i = 0; i < 10; i++) {
      calibrationSum += scale.get_units(10);
      delay(200);
    }

    calFactor = calibrationSum / 1000.0;
    scale.set_scale(calFactor);
    scalePrefs.putFloat("calFactor", calFactor);
    Serial.printf("New Calibration Factor: %.4f\n", calFactor);

    setRGBColor(255, 0, 0);
    delay(5000);
    scale.tare();
  } else {
    scale.set_scale(calFactor);
    Serial.printf("Using stored Calibration Factor: %.4f\n", calFactor);
  }
  
  scalePrefs.end();
  setRGBColor(0, 0, 0);
}

void connectWiFi() {
  Serial.println("Connecting to WiFi...");
  WiFi.begin(wifiSSID.c_str(), wifiPassword.c_str());
  
  unsigned long startTime = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - startTime < 20000) {
    delay(200);
    Serial.print(".");
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nConnected to Wi-Fi!");
    Serial.print("IP Address: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("\nFailed to connect to WiFi!");
  }
}

void registerDevice() {
  String deviceType, deviceSerialNo;
  Serial.println("Enter Device Type:");
  while (!Serial.available());
  deviceType = Serial.readStringUntil('\n');

  Serial.println("Enter WiFi SSID:");
  while (!Serial.available());
  wifiSSID = Serial.readStringUntil('\n');

  Serial.println("Enter WiFi Password:");
  while (!Serial.available());
  wifiPassword = Serial.readStringUntil('\n');

  Serial.println("Enter Device Serial Number:");
  while (!Serial.available());
  deviceSerialNo = Serial.readStringUntil('\n');

  Serial.println("Connecting to WiFi...");
  WiFi.begin(wifiSSID.c_str(), wifiPassword.c_str());
  
  unsigned long startTime = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - startTime < 20000) {
    delay(200);
    Serial.print(".");
  }

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("\nFailed to connect to WiFi. Check credentials.");
    return;
  }

  Serial.println("\nConnected to WiFi!");
  uint64_t chipId = ESP.getEfuseMac();
  char macStr[18];
  snprintf(macStr, sizeof(macStr), "%02X:%02X:%02X:%02X:%02X:%02X",
           (uint8_t)(chipId >> 40), (uint8_t)(chipId >> 32), (uint8_t)(chipId >> 24),
           (uint8_t)(chipId >> 16), (uint8_t)(chipId >> 8), (uint8_t)chipId);
  Serial.print("MAC Address: ");
  Serial.println(macStr);

  HTTPClient httpReg;
  String postData = "device_type=" + deviceType + 
                    "&device_mac=" + String(macStr) +
                    "&device_wifi=" + wifiSSID + 
                    "&device_wifi_pass=" + wifiPassword +
                    "&device_wifi_ip=" + WiFi.localIP().toString() +
                    "&device_serial_no=" + deviceSerialNo;

  httpReg.begin(registrationServerUrl);
  httpReg.addHeader("Content-Type", "application/x-www-form-urlencoded");
  int httpCode = httpReg.POST(postData);

  if (httpCode > 0) {
    Serial.println("Registration successful!");
    prefs.putString("device_mac", String(macStr));
    prefs.putString("wifi_ssid", wifiSSID);
    prefs.putString("wifi_password", wifiPassword);
    prefs.putBool("registered", true);
  } else {
    Serial.printf("Registration failed: %d\n", httpCode);
  }
  httpReg.end();
}

void runTimeDetectIN() { setRGBColor(0, 0, 255); }
void runTimeDetectOUT() { setRGBColor(255, 0, 255); }

void setRGBColor(int red, int green, int blue) {
  analogWrite(redPin, red);
  analogWrite(greenPin, green);
  analogWrite(bluePin, blue);
}

void spacing() { Serial.println("\n-------------------------\n"); }