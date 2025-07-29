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
const int servoPin1 = 5;   // Size indicator
const int servoPin2 = 18;  // Egg feeder
const int servoPin3 = 21;  // Egg sorter
const int LOADCELL_DOUT_PIN = 16;
const int LOADCELL_SCK_PIN = 4;

const char* serverUrl = "http://192.168.137.1/poultryv2/api/insert-egg-data.php";

Preferences prefs;
String wifiSSID;
String wifiPassword;
const char* registrationServerUrl = "http://192.168.137.1/poultryv2/api/register-device.php";

Servo myServo1, myServo2, myServo3;
HX711 scale;
TaskHandle_t Servo2TaskHandle = NULL;
SemaphoreHandle_t weightMutex;
float currentWeight = 0;
String currentSize = "";
bool weightStabilized = false;
float calibration_factor = 0.0;
unsigned long lastTareTime = 0;
const unsigned long TARE_INTERVAL = 30000; // Tare every 30 seconds
const float MAX_REASONABLE_WEIGHT = 100.0; // 100g max for eggs

// Diagnostic counters
int hardwareErrorCount = 0;
const int MAX_HARDWARE_ERRORS = 5;
bool hardwareFailure = false;

// Size categories with weight thresholds
const int SIZE_THRESHOLDS[] = {40, 45, 50, 55, 60, 65, 70};
const char* SIZE_NAMES[] = {"Pewee", "Pullets", "Small", "Medium", "Large", "Extra Large", "Jumbo"};
const int NUM_SIZES = 7;

// Servo angles for each size
const int SERVO_ANGLES[] = {0, 30, 60, 90, 120, 150, 180};

struct WeightMetrics {
    float first;
    float last;
    float mode;
    float final;
};

void setup() {
    Serial.begin(115200);
    Serial.println("\nStarting Egg Sorter...");

    // Initialize RGB LED
    pinMode(redPin, OUTPUT);
    pinMode(greenPin, OUTPUT);
    pinMode(bluePin, OUTPUT);
    setRGBColor(255, 0, 0); // Start with red

    // Reset handler
    if (Serial.available()) {
        String cmd = Serial.readStringUntil('\n');
        cmd.trim();
        if (cmd.equalsIgnoreCase("RESET")) {
            prefs.begin("reg", false);
            prefs.clear();
            prefs.end();
            
            prefs.begin("scale", false);
            prefs.clear();
            prefs.end();
            
            Serial.println("Registration and calibration cleared, restarting...");
            delay(500);
            ESP.restart();
        }
    }

    // Initialize HX711 first for hardware check
    initHX711();

    // Load calibration
    prefs.begin("scale", true);
    calibration_factor = prefs.getFloat("cal_factor", 0.0);
    prefs.end();

    // Apply calibration if available
    if (calibration_factor != 0.0) {
        scale.set_scale(calibration_factor);
        Serial.println("Calibration loaded: " + String(calibration_factor));
    }

    // Device registration
    prefs.begin("reg", false);
    if (!prefs.getBool("registered", false)) {
        registerDevice();
    } else {
        wifiSSID = prefs.getString("wifi_ssid", "");
        wifiPassword = prefs.getString("wifi_password", "");
        Serial.println("Using stored WiFi credentials");
    }
    prefs.end();

    // Initialize WiFi
    initWiFi();

    // Initialize servos
    initServos();

    // Create RTOS resources
    weightMutex = xSemaphoreCreateMutex();
    xTaskCreatePinnedToCore(
        Servo2Task, "Servo2Task", 2048, NULL, 2, &Servo2TaskHandle, 0);
        
    Serial.println("Setup complete");
    setRGBColor(0, 0, 255); // Blue - ready
}

void loop() {
    if (hardwareFailure) {
        // Permanent hardware failure state
        setRGBColor(255, 0, 0); // Red
        delay(1000);
        Serial.println("HARDWARE FAILURE - Check load cell connections");
        return;
    }
    
    runTimeDetectIN();
    
    // Auto-tare logic
    if (millis() - lastTareTime > TARE_INTERVAL) {
        scale.tare(10);
        Serial.println("Auto-tare performed");
        lastTareTime = millis();
    }
    
    // Check if scale is ready with timeout
    if (scale.wait_ready_timeout(500)) {
        WeightMetrics weights = getWeightMetrics();
        
        // Check if metrics are valid
        if (weights.final == -999) {
            // Hardware error already handled in getWeightMetrics
            runTimeDetectOUT();
            spacing();
            return;
        }
        
        // Validate weights
        if (weights.final > MAX_REASONABLE_WEIGHT || weights.final < 0) {
            hardwareErrorCount++;
            Serial.println("Implausible weight detected! Hardware issue?");
            
            if (hardwareErrorCount >= MAX_HARDWARE_ERRORS) {
                hardwareFailure = true;
                Serial.println("Critical hardware errors! Entering safe mode.");
                setRGBColor(255, 0, 0); // Red - hardware error
                return;
            }
            
            scale.tare();
            runTimeDetectOUT();
            spacing();
            return;  // Skip this measurement cycle
        }
        
        String size = getSizeCategory(weights.final);
        
        Serial.printf("First: %.2fg | Last: %.2fg | Mode: %.2fg | Final: %.2fg | Size: %s\n", 
                      weights.first, weights.last, weights.mode, weights.final, size.c_str());

        // Update shared variables
        if (xSemaphoreTake(weightMutex, portMAX_DELAY) == pdTRUE) {
            currentWeight = weights.final;
            currentSize = size;
            weightStabilized = true;
            xSemaphoreGive(weightMutex);
        }

        // Control indicator and sorter
        controlServos(weights.final, size);

        // Send data if valid
        if (size != "No weight") {
            if (checkWiFiConnection()) {
                sendToServer(weights, size);
            }
        }

        // Reset error counter on successful measurement
        hardwareErrorCount = 0;
        
        runTimeDetectOUT();
        spacing();
    } else {
        Serial.println("Scale timeout in main loop!");
        hardwareErrorCount++;
        if (hardwareErrorCount >= MAX_HARDWARE_ERRORS) {
            hardwareFailure = true;
        }
    }
    delay(200);
}

void initHX711() {
    Serial.println("Initializing HX711...");
    scale.begin(LOADCELL_DOUT_PIN, LOADCELL_SCK_PIN);
    
    // Use stable 10Hz mode
    scale.set_gain(128);  // Gain 128 = 10Hz
    
    // Reset HX711
    scale.power_down();
    delay(100);
    scale.power_up();
    
    // Check if scale is responding
    if (!scale.wait_ready_timeout(1000)) {
        Serial.println("HX711 not found! Check wiring:");
        Serial.printf("DOUT: %d, SCK: %d\n", LOADCELL_DOUT_PIN, LOADCELL_SCK_PIN);
        setRGBColor(255, 0, 0); // Red - hardware error
        hardwareFailure = true;
        return;
    }
    
    // Check initial reading
    long initial = scale.read();
    Serial.printf("Initial raw reading: %ld\n", initial);
    
    // Check for invalid readings
    if (initial == 0x7FFFFF || initial == 0x800000 || initial == -1) {
        Serial.println("INVALID INITIAL READING - HARDWARE FAILURE");
        setRGBColor(255, 165, 0); // Orange
        hardwareFailure = true;
        return;
    }
    
    Serial.println("HX711 initialized");
    setRGBColor(0, 255, 0); // Green - hardware OK
    delay(1000);
}

void initWiFi() {
    if (hardwareFailure) return;
    
    Serial.println("Connecting to WiFi...");
    setRGBColor(255, 255, 0); // Yellow - connecting
    
    WiFi.begin(wifiSSID.c_str(), wifiPassword.c_str());
    unsigned long startTime = millis();
    while (WiFi.status() != WL_CONNECTED && millis() - startTime < 15000) {
        delay(250);
        Serial.print(".");
    }
    
    if (WiFi.status() == WL_CONNECTED) {
        Serial.println("\nWiFi connected!");
        Serial.print("IP Address: ");
        Serial.println(WiFi.localIP());
        setRGBColor(0, 0, 255); // Blue - connected
    } else {
        Serial.println("\nWiFi connection failed");
        setRGBColor(255, 0, 255); // Purple - WiFi error
    }
}

void initServos() {
    if (hardwareFailure) return;
    
    myServo1.attach(servoPin1);
    myServo2.attach(servoPin2);
    myServo3.attach(servoPin3);
    myServo1.write(0);
    myServo2.write(0);
    myServo3.write(0);
    delay(500);
    Serial.println("Servos initialized");
}

WeightMetrics getWeightMetrics() {
    const int numReadings = 11;  // Optimal for median calculation
    float readings[numReadings];
    WeightMetrics metrics = {-1, -1, -1, -999}; // Default error state

    // Collect readings with hardware validation
    for (int i = 0; i < numReadings; i++) {
        if (!scale.wait_ready_timeout(500)) {
            Serial.println("Scale timeout in getWeightMetrics!");
            return metrics;
        }
        
        long raw = scale.read();
        
        // Validate raw ADC reading
        if (raw == 0x7FFFFF || raw == 0x800000 || raw == -1) {
            Serial.printf("Invalid raw reading: %ld - Hardware issue?\n", raw);
            hardwareErrorCount++;
            
            if (hardwareErrorCount >= MAX_HARDWARE_ERRORS) {
                hardwareFailure = true;
                Serial.println("Critical hardware errors! Entering safe mode.");
                setRGBColor(255, 0, 0); // Red - hardware error
            }
            
            return metrics;
        }
        
        float reading = scale.get_units(1);
        if (reading < 0) reading = 0;
        
        readings[i] = reading;
        delay(95);  // Delay for 10Hz mode (100ms - processing time)
    }

    // Store first and last
    metrics.first = readings[0];
    metrics.last = readings[numReadings-1];

    // Sort readings for median calculation
    for (int i = 0; i < numReadings-1; i++) {
        for (int j = i+1; j < numReadings; j++) {
            if (readings[j] < readings[i]) {
                float temp = readings[i];
                readings[i] = readings[j];
                readings[j] = temp;
            }
        }
    }
    
    // Calculate final weight as median
    metrics.final = readings[numReadings/2];  // Median is middle value

    // Simple mode calculation (most frequent value in sorted array)
    float maxValue = readings[0];
    float maxCount = 1;
    float currentValue = readings[0];
    float currentCount = 1;

    for (int i = 1; i < numReadings; i++) {
        if (fabs(readings[i] - currentValue) < 0.5) { // Group similar values
            currentCount++;
        } else {
            if (currentCount > maxCount) {
                maxCount = currentCount;
                maxValue = currentValue;
            }
            currentValue = readings[i];
            currentCount = 1;
        }
    }
    
    // Check last group
    if (currentCount > maxCount) {
        maxValue = currentValue;
    }
    
    metrics.mode = maxValue;

    return metrics;
}

void controlServos(float weight, String size) {
    if (hardwareFailure) return;
    
    // Find size index
    int sizeIndex = -1;
    for (int i = NUM_SIZES - 1; i >= 0; i--) {
        if (weight >= SIZE_THRESHOLDS[i]) {
            sizeIndex = i;
            break;
        }
    }
    
    // Set indicator servo
    if (sizeIndex >= 0) {
        myServo1.write(SERVO_ANGLES[sizeIndex]);
    } else {
        myServo1.write(0);
    }

    // Wait for feeder to complete
    delay(600);
    
    // Set sorter servo
    if (sizeIndex >= 0) {
        myServo3.write(SERVO_ANGLES[sizeIndex]);
    } else {
        myServo3.write(0);
    }
    
    delay(500); // Hold position
    myServo3.write(0); // Return to neutral
}

void sendToServer(WeightMetrics weights, String size) {
  if (hardwareFailure) return;
  
  HTTPClient http;
  DynamicJsonDocument doc(256);
  
  doc["weight"] = weights.final;
  doc["size"] = size;
  doc["first_weight"] = weights.first;
  doc["last_weight"] = weights.last;
  doc["mode_weight"] = weights.mode;
  doc["egg_weight"] = weights.final;
  
  char macStr[18];
  getMacAddress(macStr);
  doc["mac"] = String(macStr);
  
  String payload;
  serializeJson(doc, payload);
  Serial.println("Sending: " + payload);

  http.begin(serverUrl);
  http.addHeader("Content-Type", "application/json");
  http.setTimeout(5000); // Set timeout to 5 seconds
  int httpCode = http.POST(payload);

  if (httpCode == HTTP_CODE_OK || httpCode == HTTP_CODE_CREATED) {
      Serial.println("Data sent successfully");
  } else {
      Serial.printf("Server error: %d\n", httpCode);
      if (httpCode < 0) {
          Serial.println("Error: " + http.errorToString(httpCode));
      } else {
          Serial.println("Response: " + http.getString());
      }
  }
  http.end();
}

void registerDevice() {
  Serial.println("Starting device registration...");
  setRGBColor(0, 255, 255); // Cyan - registration mode
  
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
      delay(250);
      Serial.print(".");
  }

  if (WiFi.status() != WL_CONNECTED) {
      Serial.println("\nFailed to connect! Using stored credentials next boot");
      return;
  }

  Serial.println("\nConnected to WiFi!");
  char macStr[18];
  getMacAddress(macStr);
  String deviceIP = WiFi.localIP().toString();

  HTTPClient httpReg;
  String postData = "device_type=" + deviceType + 
                    "&device_mac=" + String(macStr) +
                    "&device_wifi=" + wifiSSID + 
                    "&device_wifi_pass=" + wifiPassword +
                    "&device_wifi_ip=" + deviceIP + 
                    "&device_serial_no=" + deviceSerialNo;

  httpReg.begin(registrationServerUrl);
  httpReg.addHeader("Content-Type", "application/x-www-form-urlencoded");
  httpReg.setTimeout(5000);
  int httpCode = httpReg.POST(postData);

  if (httpCode > 0) {
      Serial.println("Registration successful!");
      prefs.begin("reg", false);
      prefs.putString("wifi_ssid", wifiSSID);
      prefs.putString("wifi_password", wifiPassword);
      prefs.putBool("registered", true);
      prefs.end();
  } else {
      Serial.printf("Registration failed: %d\n", httpCode);
      Serial.println("Error: " + httpReg.errorToString(httpCode));
  }
  httpReg.end();
}

bool checkWiFiConnection() {
  if (hardwareFailure) return false;
  
  if (WiFi.status() == WL_CONNECTED) return true;
  
  Serial.println("Reconnecting to WiFi...");
  setRGBColor(255, 165, 0); // Orange - reconnecting
  WiFi.disconnect();
  WiFi.begin(wifiSSID.c_str(), wifiPassword.c_str());
  
  unsigned long startTime = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - startTime < 10000) {
      delay(250);
      Serial.print(".");
  }
  
  if (WiFi.status() == WL_CONNECTED) {
      Serial.println("\nWiFi reconnected!");
      setRGBColor(0, 0, 255); // Blue - connected
      return true;
  }
  Serial.println("\nWiFi connection failed");
  setRGBColor(255, 0, 255); // Purple - WiFi error
  return false;
}

// --- Helper Functions ---
void getMacAddress(char* buffer) {
    uint64_t chipId = ESP.getEfuseMac();
    snprintf(buffer, 18, "%02X:%02X:%02X:%02X:%02X:%02X",
             (uint8_t)((chipId >> 40) & 0xFF),
             (uint8_t)((chipId >> 32) & 0xFF),
             (uint8_t)((chipId >> 24) & 0xFF),
             (uint8_t)((chipId >> 16) & 0xFF),
             (uint8_t)((chipId >> 8) & 0xFF),
             (uint8_t)(chipId & 0xFF));
}

String getSizeCategory(float weight) {
    for (int i = NUM_SIZES - 1; i >= 0; i--) {
        if (weight >= SIZE_THRESHOLDS[i]) {
            return SIZE_NAMES[i];
        }
    }
    return "No weight";
}

void setRGBColor(int r, int g, int b) {
    analogWrite(redPin, r);
    analogWrite(greenPin, g);
    analogWrite(bluePin, b);
}

void runTimeDetectIN() { setRGBColor(0, 0, 255); }    // Blue
void runTimeDetectOUT() { setRGBColor(0, 255, 0); }   // Green
void spacing() { Serial.println("\n-------------------------"); }

void Servo2Task(void *pvParameters) {
    while (1) {
        if (xSemaphoreTake(weightMutex, portMAX_DELAY) == pdTRUE) {
            if (weightStabilized && !hardwareFailure) {
                weightStabilized = false;
                xSemaphoreGive(weightMutex);
                
                // Feed egg sequence
                myServo2.write(0);
                vTaskDelay(100 / portTICK_PERIOD_MS);
                myServo2.write(180);
                vTaskDelay(500 / portTICK_PERIOD_MS);
                myServo2.write(0);
            } else {
                xSemaphoreGive(weightMutex);
            }
        }
        vTaskDelay(50 / portTICK_PERIOD_MS);
    }
}