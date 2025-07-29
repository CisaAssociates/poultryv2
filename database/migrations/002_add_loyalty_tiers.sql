-- Create loyalty_tiers table
CREATE TABLE IF NOT EXISTS `loyalty_tiers` (
  `tier_id` INT NOT NULL AUTO_INCREMENT,
  `tier_name` VARCHAR(50) NOT NULL,
  `points_required` INT NOT NULL,
  `discount_percentage` DECIMAL(5,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`tier_id`)
);

-- Insert default loyalty tiers
INSERT INTO `loyalty_tiers` (`tier_name`, `points_required`, `discount_percentage`) VALUES
('Bronze', 0, 0),
('Silver', 500, 5),
('Gold', 1000, 10),
('Platinum', 2000, 15);