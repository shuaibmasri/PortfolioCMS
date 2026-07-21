-- Certificates Module migration: add image support for certificate cards.
ALTER TABLE `certifications`
  ADD COLUMN IF NOT EXISTS `certificate_image_path` VARCHAR(500) NULL AFTER `credential_url`;
