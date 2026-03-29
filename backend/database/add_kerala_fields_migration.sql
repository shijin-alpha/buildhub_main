-- Migration: Add kerala_district and construction_start_month to layout_requests
-- Run this once against your buildhub database

ALTER TABLE `layout_requests`
  ADD COLUMN IF NOT EXISTS `kerala_district` VARCHAR(50) DEFAULT NULL
    COMMENT 'Kerala district name (one of 14 districts)',
  ADD COLUMN IF NOT EXISTS `construction_start_month` TINYINT UNSIGNED DEFAULT NULL
    COMMENT 'Planned construction start month (1=Jan … 12=Dec)';
