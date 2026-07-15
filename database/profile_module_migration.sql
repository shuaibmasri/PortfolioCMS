-- Profile Module migration: move profile-specific values out of website_settings.
ALTER TABLE `profiles`
  ADD COLUMN `cover_photo_path` VARCHAR(500) NULL AFTER `profile_image_path`,
  ADD COLUMN `logo_path` VARCHAR(500) NULL AFTER `cover_photo_path`,
  ADD COLUMN `favicon_path` VARCHAR(500) NULL AFTER `logo_path`,
  ADD COLUMN `nationality` VARCHAR(100) NULL AFTER `location`,
  ADD COLUMN `date_of_birth` DATE NULL AFTER `nationality`,
  ADD COLUMN `years_of_experience` TINYINT UNSIGNED NULL AFTER `date_of_birth`,
  ADD COLUMN `current_position` VARCHAR(200) NULL AFTER `years_of_experience`,
  ADD COLUMN `current_company` VARCHAR(200) NULL AFTER `current_position`,
  ADD COLUMN `professional_summary` TEXT NULL AFTER `biography`;
