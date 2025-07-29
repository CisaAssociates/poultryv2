-- Add stock_count column to trays table
ALTER TABLE `trays` ADD COLUMN `stock_count` INT NOT NULL DEFAULT 30 AFTER `egg_count`;

-- Update existing trays to set stock_count equal to egg_count
UPDATE `trays` SET `stock_count` = `egg_count`;

-- Add comment to explain the difference between egg_count and stock_count
-- egg_count: Number of eggs in a tray (always 30 for a full tray)
-- stock_count: Number of trays available in stock (inventory)