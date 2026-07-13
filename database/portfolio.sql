-- Portfolio Management System schema for MySQL 8.
CREATE DATABASE IF NOT EXISTS `portfolio_cms`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `portfolio_cms`;

START TRANSACTION;

-- Stores the single portfolio administrator login account.
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(150) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores the public portfolio owner's identity and contact details.
CREATE TABLE IF NOT EXISTS `profiles` (
  `profile_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `professional_title` VARCHAR(200) NOT NULL,
  `biography` TEXT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) NULL,
  `location` VARCHAR(200) NULL,
  `profile_image_path` VARCHAR(500) NULL,
  `is_public` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`profile_id`),
  KEY `idx_profiles_is_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Controls the enabled state and display order of public portfolio sections.
CREATE TABLE IF NOT EXISTS `portfolio_sections` (
  `portfolio_section_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `section_key` VARCHAR(100) NOT NULL,
  `display_name` VARCHAR(150) NOT NULL,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`portfolio_section_id`),
  UNIQUE KEY `uq_portfolio_sections_key` (`section_key`),
  KEY `idx_portfolio_sections_visibility` (`is_enabled`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Groups skills into reusable categories.
CREATE TABLE IF NOT EXISTS `skill_categories` (
  `skill_category_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`skill_category_id`),
  UNIQUE KEY `uq_skill_categories_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores individual portfolio skills.
CREATE TABLE IF NOT EXISTS `skills` (
  `skill_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `skill_category_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `proficiency_level` TINYINT UNSIGNED NULL,
  `description` TEXT NULL,
  `icon_path` VARCHAR(500) NULL,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_public` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`skill_id`),
  UNIQUE KEY `uq_skills_category_name` (`skill_category_id`, `name`),
  KEY `idx_skills_visibility` (`is_public`, `display_order`),
  CONSTRAINT `fk_skills_category`
    FOREIGN KEY (`skill_category_id`) REFERENCES `skill_categories` (`skill_category_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Maintains the reference list of spoken languages.
CREATE TABLE IF NOT EXISTS `languages` (
  `language_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `iso_code` VARCHAR(10) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`language_id`),
  UNIQUE KEY `uq_languages_name` (`name`),
  UNIQUE KEY `uq_languages_iso_code` (`iso_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Links a profile to languages and records proficiency.
CREATE TABLE IF NOT EXISTS `profile_languages` (
  `profile_id` BIGINT UNSIGNED NOT NULL,
  `language_id` BIGINT UNSIGNED NOT NULL,
  `proficiency_level` VARCHAR(50) NOT NULL,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_public` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`profile_id`, `language_id`),
  KEY `idx_profile_languages_visibility` (`profile_id`, `is_public`, `display_order`),
  CONSTRAINT `fk_profile_languages_profile`
    FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`profile_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_profile_languages_language`
    FOREIGN KEY (`language_id`) REFERENCES `languages` (`language_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores employment history for the profile.
CREATE TABLE IF NOT EXISTS `work_experiences` (
  `work_experience_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `profile_id` BIGINT UNSIGNED NOT NULL,
  `employer_name` VARCHAR(200) NOT NULL,
  `job_title` VARCHAR(200) NOT NULL,
  `employment_type` VARCHAR(50) NULL,
  `location` VARCHAR(200) NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NULL,
  `is_current` TINYINT(1) NOT NULL DEFAULT 0,
  `description` TEXT NULL,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_public` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`work_experience_id`),
  KEY `idx_work_experiences_profile_visibility` (`profile_id`, `is_public`, `start_date`),
  CONSTRAINT `fk_work_experiences_profile`
    FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`profile_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores ordered achievements and responsibilities for each work experience.
CREATE TABLE IF NOT EXISTS `work_experience_highlights` (
  `work_experience_highlight_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `work_experience_id` BIGINT UNSIGNED NOT NULL,
  `highlight_text` TEXT NOT NULL,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`work_experience_highlight_id`),
  KEY `idx_work_highlights_experience_order` (`work_experience_id`, `display_order`),
  CONSTRAINT `fk_work_highlights_experience`
    FOREIGN KEY (`work_experience_id`) REFERENCES `work_experiences` (`work_experience_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores educational qualifications.
CREATE TABLE IF NOT EXISTS `educations` (
  `education_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `profile_id` BIGINT UNSIGNED NOT NULL,
  `institution_name` VARCHAR(250) NOT NULL,
  `degree` VARCHAR(200) NULL,
  `field_of_study` VARCHAR(200) NULL,
  `location` VARCHAR(200) NULL,
  `start_date` DATE NULL,
  `end_date` DATE NULL,
  `grade` VARCHAR(100) NULL,
  `description` TEXT NULL,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_public` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`education_id`),
  KEY `idx_educations_profile_visibility` (`profile_id`, `is_public`, `display_order`),
  CONSTRAINT `fk_educations_profile`
    FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`profile_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores professional certifications and their verification details.
CREATE TABLE IF NOT EXISTS `certifications` (
  `certification_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `profile_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(250) NOT NULL,
  `issuing_organization` VARCHAR(250) NOT NULL,
  `credential_id` VARCHAR(200) NULL,
  `credential_url` VARCHAR(500) NULL,
  `issued_date` DATE NULL,
  `expiry_date` DATE NULL,
  `certificate_file_path` VARCHAR(500) NULL,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_public` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`certification_id`),
  KEY `idx_certifications_profile_visibility` (`profile_id`, `is_public`, `display_order`),
  KEY `idx_certifications_expiry_date` (`expiry_date`),
  CONSTRAINT `fk_certifications_profile`
    FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`profile_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores awards, honors, and other recognitions.
CREATE TABLE IF NOT EXISTS `achievements` (
  `achievement_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `profile_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(250) NOT NULL,
  `issuing_organization` VARCHAR(250) NULL,
  `achievement_date` DATE NULL,
  `description` TEXT NULL,
  `reference_url` VARCHAR(500) NULL,
  `image_path` VARCHAR(500) NULL,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_public` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`achievement_id`),
  KEY `idx_achievements_profile_visibility` (`profile_id`, `is_public`, `display_order`),
  KEY `idx_achievements_date` (`achievement_date`),
  CONSTRAINT `fk_achievements_profile`
    FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`profile_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Classifies portfolio projects.
CREATE TABLE IF NOT EXISTS `project_categories` (
  `project_category_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(150) NOT NULL,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`project_category_id`),
  UNIQUE KEY `uq_project_categories_name` (`name`),
  UNIQUE KEY `uq_project_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores public portfolio projects.
CREATE TABLE IF NOT EXISTS `projects` (
  `project_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_category_id` BIGINT UNSIGNED NULL,
  `title` VARCHAR(250) NOT NULL,
  `slug` VARCHAR(250) NOT NULL,
  `short_description` VARCHAR(500) NULL,
  `description` TEXT NOT NULL,
  `client_name` VARCHAR(200) NULL,
  `project_url` VARCHAR(500) NULL,
  `repository_url` VARCHAR(500) NULL,
  `started_date` DATE NULL,
  `completed_date` DATE NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_public` TINYINT(1) NOT NULL DEFAULT 1,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`project_id`),
  UNIQUE KEY `uq_projects_slug` (`slug`),
  KEY `idx_projects_category_visibility` (`project_category_id`, `is_public`, `display_order`),
  KEY `idx_projects_featured_visibility` (`is_featured`, `is_public`),
  CONSTRAINT `fk_projects_category`
    FOREIGN KEY (`project_category_id`) REFERENCES `project_categories` (`project_category_id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores reusable technologies used by projects.
CREATE TABLE IF NOT EXISTS `technologies` (
  `technology_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`technology_id`),
  UNIQUE KEY `uq_technologies_name` (`name`),
  UNIQUE KEY `uq_technologies_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Maps projects to the technologies they use.
CREATE TABLE IF NOT EXISTS `project_technologies` (
  `project_id` BIGINT UNSIGNED NOT NULL,
  `technology_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`project_id`, `technology_id`),
  KEY `idx_project_technologies_technology` (`technology_id`),
  CONSTRAINT `fk_project_technologies_project`
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_project_technologies_technology`
    FOREIGN KEY (`technology_id`) REFERENCES `technologies` (`technology_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores multiple ordered images for each project.
CREATE TABLE IF NOT EXISTS `project_images` (
  `project_image_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `image_path` VARCHAR(500) NOT NULL,
  `alt_text` VARCHAR(255) NULL,
  `caption` VARCHAR(500) NULL,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_cover_image` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`project_image_id`),
  KEY `idx_project_images_project_order` (`project_id`, `display_order`),
  KEY `idx_project_images_cover` (`project_id`, `is_cover_image`),
  CONSTRAINT `fk_project_images_project`
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores future freelance service offerings.
CREATE TABLE IF NOT EXISTS `services` (
  `service_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(200) NOT NULL,
  `short_description` VARCHAR(500) NULL,
  `description` TEXT NOT NULL,
  `icon_path` VARCHAR(500) NULL,
  `starting_price` DECIMAL(12,2) NULL,
  `currency_code` CHAR(3) NULL,
  `pricing_unit` VARCHAR(50) NULL,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_public` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`service_id`),
  UNIQUE KEY `uq_services_slug` (`slug`),
  KEY `idx_services_visibility` (`is_public`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores client and colleague testimonials, optionally tied to a project.
CREATE TABLE IF NOT EXISTS `testimonials` (
  `testimonial_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` BIGINT UNSIGNED NULL,
  `author_name` VARCHAR(150) NOT NULL,
  `author_title` VARCHAR(150) NULL,
  `organization_name` VARCHAR(200) NULL,
  `author_image_path` VARCHAR(500) NULL,
  `testimonial_text` TEXT NOT NULL,
  `testimonial_date` DATE NULL,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_public` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`testimonial_id`),
  KEY `idx_testimonials_project_visibility` (`project_id`, `is_public`),
  KEY `idx_testimonials_visibility` (`is_public`, `display_order`),
  CONSTRAINT `fk_testimonials_project`
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Maintains the list of supported social media platforms.
CREATE TABLE IF NOT EXISTS `social_platforms` (
  `social_platform_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `base_url` VARCHAR(255) NULL,
  `icon_identifier` VARCHAR(100) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`social_platform_id`),
  UNIQUE KEY `uq_social_platforms_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Links public social URLs to a profile.
CREATE TABLE IF NOT EXISTS `profile_social_links` (
  `profile_social_link_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `profile_id` BIGINT UNSIGNED NOT NULL,
  `social_platform_id` BIGINT UNSIGNED NOT NULL,
  `profile_url` VARCHAR(500) NOT NULL,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_public` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`profile_social_link_id`),
  UNIQUE KEY `uq_profile_social_links_platform` (`profile_id`, `social_platform_id`),
  KEY `idx_profile_social_links_visibility` (`profile_id`, `is_public`, `display_order`),
  CONSTRAINT `fk_profile_social_links_profile`
    FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`profile_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_profile_social_links_platform`
    FOREIGN KEY (`social_platform_id`) REFERENCES `social_platforms` (`social_platform_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores messages submitted through the public contact form.
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `contact_message_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sender_name` VARCHAR(150) NOT NULL,
  `sender_email` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(250) NULL,
  `message_body` TEXT NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'new',
  `read_at` DATETIME NULL,
  `replied_at` DATETIME NULL,
  `ip_address` VARBINARY(16) NULL,
  `user_agent` VARCHAR(1000) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`contact_message_id`),
  KEY `idx_contact_messages_status_created` (`status`, `created_at`),
  KEY `idx_contact_messages_sender_email` (`sender_email`),
  KEY `idx_contact_messages_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores general, SEO, contact, and analytics site settings.
CREATE TABLE IF NOT EXISTS `website_settings` (
  `website_setting_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(150) NOT NULL,
  `setting_value` TEXT NOT NULL,
  `value_type` VARCHAR(30) NOT NULL DEFAULT 'string',
  `setting_group` VARCHAR(100) NULL,
  `description` VARCHAR(500) NULL,
  `is_public` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`website_setting_id`),
  UNIQUE KEY `uq_website_settings_key` (`setting_key`),
  KEY `idx_website_settings_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Defines selectable website themes.
CREATE TABLE IF NOT EXISTS `themes` (
  `theme_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `theme_key` VARCHAR(100) NOT NULL,
  `description` VARCHAR(500) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`theme_id`),
  UNIQUE KEY `uq_themes_name` (`name`),
  UNIQUE KEY `uq_themes_theme_key` (`theme_key`),
  KEY `idx_themes_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores configurable presentation values for each theme.
CREATE TABLE IF NOT EXISTS `theme_settings` (
  `theme_setting_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `theme_id` BIGINT UNSIGNED NOT NULL,
  `setting_key` VARCHAR(150) NOT NULL,
  `setting_value` TEXT NOT NULL,
  `value_type` VARCHAR(30) NOT NULL DEFAULT 'string',
  `description` VARCHAR(500) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`theme_setting_id`),
  UNIQUE KEY `uq_theme_settings_theme_key` (`theme_id`, `setting_key`),
  CONSTRAINT `fk_theme_settings_theme`
    FOREIGN KEY (`theme_id`) REFERENCES `themes` (`theme_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores uploaded CV files available for visitor download.
CREATE TABLE IF NOT EXISTS `cv_files` (
  `cv_file_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `profile_id` BIGINT UNSIGNED NOT NULL,
  `original_filename` VARCHAR(255) NOT NULL,
  `storage_path` VARCHAR(500) NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `file_size_bytes` BIGINT UNSIGNED NOT NULL,
  `file_checksum` CHAR(64) NULL,
  `version_label` VARCHAR(100) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cv_file_id`),
  KEY `idx_cv_files_profile_active` (`profile_id`, `is_active`),
  KEY `idx_cv_files_created_at` (`created_at`),
  CONSTRAINT `fk_cv_files_profile`
    FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`profile_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores privacy-conscious visitor session metadata.
CREATE TABLE IF NOT EXISTS `visitor_sessions` (
  `visitor_session_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_token` CHAR(64) NOT NULL,
  `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_hash` CHAR(64) NULL,
  `country_code` CHAR(2) NULL,
  `referrer_url` VARCHAR(1000) NULL,
  `landing_path` VARCHAR(500) NULL,
  `device_type` VARCHAR(30) NULL,
  `browser_name` VARCHAR(100) NULL,
  `operating_system` VARCHAR(100) NULL,
  PRIMARY KEY (`visitor_session_id`),
  UNIQUE KEY `uq_visitor_sessions_token` (`session_token`),
  KEY `idx_visitor_sessions_started_at` (`started_at`),
  KEY `idx_visitor_sessions_ip_hash_started` (`ip_hash`, `started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Records individual public page views for analytics.
CREATE TABLE IF NOT EXISTS `page_views` (
  `page_view_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `visitor_session_id` BIGINT UNSIGNED NULL,
  `project_id` BIGINT UNSIGNED NULL,
  `page_path` VARCHAR(500) NOT NULL,
  `viewed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `referrer_url` VARCHAR(1000) NULL,
  PRIMARY KEY (`page_view_id`),
  KEY `idx_page_views_viewed_at` (`viewed_at`),
  KEY `idx_page_views_session_viewed` (`visitor_session_id`, `viewed_at`),
  KEY `idx_page_views_project_viewed` (`project_id`, `viewed_at`),
  KEY `idx_page_views_path_viewed` (`page_path`, `viewed_at`),
  CONSTRAINT `fk_page_views_session`
    FOREIGN KEY (`visitor_session_id`) REFERENCES `visitor_sessions` (`visitor_session_id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_page_views_project`
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Records downloads of uploaded CVs and dynamically generated resumes.
CREATE TABLE IF NOT EXISTS `download_events` (
  `download_event_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cv_file_id` BIGINT UNSIGNED NULL,
  `visitor_session_id` BIGINT UNSIGNED NULL,
  `download_type` VARCHAR(50) NOT NULL,
  `source_path` VARCHAR(500) NULL,
  `downloaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_hash` CHAR(64) NULL,
  `user_agent` VARCHAR(1000) NULL,
  PRIMARY KEY (`download_event_id`),
  KEY `idx_download_events_cv_downloaded` (`cv_file_id`, `downloaded_at`),
  KEY `idx_download_events_session_downloaded` (`visitor_session_id`, `downloaded_at`),
  KEY `idx_download_events_type_downloaded` (`download_type`, `downloaded_at`),
  KEY `idx_download_events_downloaded_at` (`downloaded_at`),
  CONSTRAINT `fk_download_events_cv_file`
    FOREIGN KEY (`cv_file_id`) REFERENCES `cv_files` (`cv_file_id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_download_events_session`
    FOREIGN KEY (`visitor_session_id`) REFERENCES `visitor_sessions` (`visitor_session_id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seeds the standard public portfolio sections.
INSERT IGNORE INTO `portfolio_sections` (`section_key`, `display_name`, `display_order`) VALUES
  ('home', 'Home', 1),
  ('about', 'About', 2),
  ('skills', 'Skills', 3),
  ('experience', 'Experience', 4),
  ('education', 'Education', 5),
  ('certifications', 'Certifications', 6),
  ('projects', 'Projects', 7),
  ('services', 'Services', 8),
  ('achievements', 'Achievements', 9),
  ('testimonials', 'Testimonials', 10),
  ('contact', 'Contact', 11);

-- Seeds the standard social media platforms.
INSERT IGNORE INTO `social_platforms` (`name`, `base_url`, `icon_identifier`) VALUES
  ('LinkedIn', 'https://www.linkedin.com/', 'linkedin'),
  ('GitHub', 'https://github.com/', 'github'),
  ('Facebook', 'https://www.facebook.com/', 'facebook'),
  ('X', 'https://x.com/', 'x'),
  ('Email', 'mailto:', 'email');

COMMIT;
