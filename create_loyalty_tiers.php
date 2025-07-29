<?php
// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'poultryv2_db';

$mysqli = new mysqli($host, $username, $password, $database);

if ($mysqli->connect_error) {
    die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

// Create loyalty_tiers table
$create_table = "CREATE TABLE IF NOT EXISTS `loyalty_tiers` (
  `tier_id` INT NOT NULL AUTO_INCREMENT,
  `tier_name` VARCHAR(50) NOT NULL,
  `points_required` INT NOT NULL,
  `discount_percentage` DECIMAL(5,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`tier_id`)
)";

if ($mysqli->query($create_table)) {
    echo "Table loyalty_tiers created successfully\n";
} else {
    echo "Error creating table: " . $mysqli->error . "\n";
}

// Insert default loyalty tiers
$insert_tiers = "INSERT INTO `loyalty_tiers` (`tier_name`, `points_required`, `discount_percentage`) VALUES
('Bronze', 0, 0),
('Silver', 500, 5),
('Gold', 1000, 10),
('Platinum', 2000, 15)";

if ($mysqli->query($insert_tiers)) {
    echo "Default loyalty tiers inserted successfully\n";
} else {
    echo "Error inserting tiers: " . $mysqli->error . "\n";
}

$mysqli->close();
echo "Done!\n";