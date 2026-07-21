```sql
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
START TRANSACTION;

-- ====================================================
-- Users
-- ====================================================
-- TODO: The users table has no username column. "shuaib" is stored in full_name.

INSERT INTO `users`
  (`email`, `password_hash`, `full_name`, `is_active`)
VALUES
  (
    'sh.almassri2013@gmail.com',
    '$2y$10$MD0fK6SDsB0XbkN/TAiNfOeM3f3JR1kNoDN5f6O00Yy2ujogjzZxG',
    'shuaib',
    1
  )
ON DUPLICATE KEY UPDATE
  `user_id` = LAST_INSERT_ID(`user_id`);

SET @user_id := LAST_INSERT_ID();

SELECT `user_id`
INTO @user_id
FROM `users`
WHERE `email` = 'sh.almassri2013@gmail.com'
LIMIT 1;

-- Validation
SELECT @user_id AS `user_id`;

-- ====================================================
-- Profiles
-- ====================================================

INSERT INTO `profiles`
  (
    `first_name`,
    `last_name`,
    `professional_title`,
    `biography`,
    `professional_summary`,
    `email`,
    `phone`,
    `location`,
    `is_public`
  )
SELECT
  'Shuaib',
  'Al-Masri',
  'ERP Developer',
  'ERP Developer with over 5 years of experience in designing and developing efficient business systems for Human Resources, Asset Management, Inventory & Procurement, Sales, and Customer Operations. These systems are actively used by various banks and public and private institutions, including Al-Qutaibi Bank, Al-Sharq Bank, and the Traffic Police Department in Aden. Skilled in C#, SQL Server, and Web API development. Proficient in API integration, cross-platform mobile app development using Flutter. In addition to the above technical skills, I manage and supervise the team specializing in developing other systems (banking systems, exchange systems, and other subsystems) as the head of the company''s development department, which allows me to combine technical and managerial skills.',
  'ERP Developer with over 5 years of experience in designing and developing efficient business systems for Human Resources, Asset Management, Inventory & Procurement, Sales, and Customer Operations. These systems are actively used by various banks and public and private institutions, including Al-Qutaibi Bank, Al-Sharq Bank, and the Traffic Police Department in Aden. Skilled in C#, SQL Server, and Web API development. Proficient in API integration, cross-platform mobile app development using Flutter. In addition to the above technical skills, I manage and supervise the team specializing in developing other systems (banking systems, exchange systems, and other subsystems) as the head of the company''s development department, which allows me to combine technical and managerial skills.',
  'sh.almassri2013@gmail.com',
  '+967 773046555',
  NULL,
  1
WHERE NOT EXISTS (
  SELECT 1
  FROM `profiles`
  WHERE `email` = 'sh.almassri2013@gmail.com'
);

SET @profile_id := IF(ROW_COUNT() = 1, LAST_INSERT_ID(), NULL);

SELECT `profile_id`
INTO @profile_id
FROM `profiles`
WHERE `email` = 'sh.almassri2013@gmail.com'
ORDER BY `profile_id`
LIMIT 1;

-- Validation
SELECT @profile_id AS `profile_id`;

-- TODO: Missing information: location text could not be reliably represented from the CV source.

-- ====================================================
-- Skill Categories
-- ====================================================

INSERT IGNORE INTO `skill_categories` (`name`, `display_order`) VALUES
  ('Languages', 1),
  ('Frameworks', 2),
  ('Databases', 3),
  ('Tools', 4),
  ('Concepts', 5),
  ('Soft Skills', 6);

-- Validation
SELECT `skill_category_id`, `name`
FROM `skill_categories`
WHERE `name` IN ('Languages', 'Frameworks', 'Databases', 'Tools', 'Concepts', 'Soft Skills')
ORDER BY `display_order`;

-- ====================================================
-- Skills
-- ====================================================

INSERT IGNORE INTO `skills`
  (`skill_category_id`, `name`, `display_order`, `is_public`)
SELECT `sc`.`skill_category_id`, `source`.`name`, `source`.`display_order`, 1
FROM (
  SELECT 'Languages' AS `category_name`, 'C#' AS `name`, 1 AS `display_order`
  UNION ALL SELECT 'Languages', 'ASP.NET', 2
  UNION ALL SELECT 'Languages', 'VB.NET', 3
  UNION ALL SELECT 'Languages', 'SQL', 4
  UNION ALL SELECT 'Languages', 'Dart', 5
  UNION ALL SELECT 'Languages', 'PHP', 6
  UNION ALL SELECT 'Frameworks', '.NET Framework', 1
  UNION ALL SELECT 'Frameworks', '.NET Core', 2
  UNION ALL SELECT 'Frameworks', 'Flutter', 3
  UNION ALL SELECT 'Databases', 'SQL Server', 1
  UNION ALL SELECT 'Tools', 'Visual Studio', 1
  UNION ALL SELECT 'Tools', 'Visual Code', 2
  UNION ALL SELECT 'Tools', 'Android Studio', 3
  UNION ALL SELECT 'Tools', 'Git', 4
  UNION ALL SELECT 'Tools', 'Postman', 5
  UNION ALL SELECT 'Tools', 'SSMS', 6
  UNION ALL SELECT 'Concepts', 'ERP systems', 1
  UNION ALL SELECT 'Concepts', 'API development', 2
  UNION ALL SELECT 'Concepts', 'e-Invoicing', 3
  UNION ALL SELECT 'Concepts', 'Cross-platform apps', 4
  UNION ALL SELECT 'Soft Skills', 'Strong teamwork and communication', 1
  UNION ALL SELECT 'Soft Skills', 'Leadership and time management', 2
  UNION ALL SELECT 'Soft Skills', 'Quick learner and problem-solver', 3
) AS `source`
INNER JOIN `skill_categories` AS `sc`
  ON `sc`.`name` = `source`.`category_name`;

-- Validation
SELECT COUNT(*) AS `skill_records`
FROM `skills` AS `s`
INNER JOIN `skill_categories` AS `sc`
  ON `sc`.`skill_category_id` = `s`.`skill_category_id`
WHERE `sc`.`name` IN ('Languages', 'Frameworks', 'Databases', 'Tools', 'Concepts', 'Soft Skills');

-- ====================================================
-- Work Experiences
-- ====================================================

-- CV date: Nov 2020 – Present
INSERT INTO `work_experiences`
  (
    `profile_id`,
    `employer_name`,
    `job_title`,
    `start_date`,
    `end_date`,
    `is_current`,
    `description`,
    `display_order`,
    `is_public`
  )
SELECT
  @profile_id,
  'Contact For Information Systems Company',
  'Systems Developer',
  STR_TO_DATE('Nov 2020', '%b %Y'),
  NULL,
  1,
  'Systems and Software Development Officer.',
  1,
  1
WHERE @profile_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `work_experiences`
    WHERE `profile_id` = @profile_id
      AND `employer_name` = 'Contact For Information Systems Company'
      AND `job_title` = 'Systems Developer'
  );

SET @contact_experience_id := IF(ROW_COUNT() = 1, LAST_INSERT_ID(), NULL);

SELECT `work_experience_id`
INTO @contact_experience_id
FROM `work_experiences`
WHERE `profile_id` = @profile_id
  AND `employer_name` = 'Contact For Information Systems Company'
  AND `job_title` = 'Systems Developer'
ORDER BY `work_experience_id`
LIMIT 1;

-- CV date: Nov 2018 – Oct 2020
INSERT INTO `work_experiences`
  (
    `profile_id`,
    `employer_name`,
    `job_title`,
    `start_date`,
    `end_date`,
    `is_current`,
    `display_order`,
    `is_public`
  )
SELECT
  @profile_id,
  'Ebda3Soft Company',
  'Systems Developer',
  STR_TO_DATE('Nov 2018', '%b %Y'),
  STR_TO_DATE('Oct 2020', '%b %Y'),
  0,
  2,
  1
WHERE @profile_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `work_experiences`
    WHERE `profile_id` = @profile_id
      AND `employer_name` = 'Ebda3Soft Company'
      AND `job_title` = 'Systems Developer'
  );

SET @ebda3soft_experience_id := IF(ROW_COUNT() = 1, LAST_INSERT_ID(), NULL);

SELECT `work_experience_id`
INTO @ebda3soft_experience_id
FROM `work_experiences`
WHERE `profile_id` = @profile_id
  AND `employer_name` = 'Ebda3Soft Company'
  AND `job_title` = 'Systems Developer'
ORDER BY `work_experience_id`
LIMIT 1;

-- CV date: Apr 2018 – Jul 2018
INSERT INTO `work_experiences`
  (
    `profile_id`,
    `employer_name`,
    `job_title`,
    `start_date`,
    `end_date`,
    `is_current`,
    `display_order`,
    `is_public`
  )
SELECT
  @profile_id,
  'SmartApps Company',
  'Web Developer (Internship)',
  STR_TO_DATE('Apr 2018', '%b %Y'),
  STR_TO_DATE('Jul 2018', '%b %Y'),
  0,
  3,
  1
WHERE @profile_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `work_experiences`
    WHERE `profile_id` = @profile_id
      AND `employer_name` = 'SmartApps Company'
      AND `job_title` = 'Web Developer (Internship)'
  );

SET @smartapps_experience_id := IF(ROW_COUNT() = 1, LAST_INSERT_ID(), NULL);

SELECT `work_experience_id`
INTO @smartapps_experience_id
FROM `work_experiences`
WHERE `profile_id` = @profile_id
  AND `employer_name` = 'SmartApps Company'
  AND `job_title` = 'Web Developer (Internship)'
ORDER BY `work_experience_id`
LIMIT 1;

-- Validation
SELECT @contact_experience_id AS `contact_experience_id`;
SELECT @ebda3soft_experience_id AS `ebda3soft_experience_id`;
SELECT @smartapps_experience_id AS `smartapps_experience_id`;

-- ====================================================
-- Work Experience Highlights
-- ====================================================

INSERT INTO `work_experience_highlights`
  (`work_experience_id`, `highlight_text`, `display_order`)
SELECT
  @contact_experience_id,
  `source`.`highlight_text`,
  `source`.`display_order`
FROM (
  SELECT 'Designed and implemented ERP modules including HR, Asset Management, and Stock & Purchase systems. These systems are used by several clients, including banks, government institutions, and private companies.' AS `highlight_text`, 1 AS `display_order`
  UNION ALL SELECT 'Developed e-Invoice systems specifically for Gulf and Saudi Arabian customers.', 2
  UNION ALL SELECT 'Development of water and electricity project management systems.', 3
  UNION ALL SELECT 'Supervising the team specialized in developing sub-systems such as the hospital and clinic system.', 4
  UNION ALL SELECT 'Built and deployed cross-platform mobile applications using Flutter.', 5
  UNION ALL SELECT 'Created and maintained APIs for integrating front-end and mobile apps with backend systems.', 6
) AS `source`
WHERE @contact_experience_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `work_experience_highlights`
    WHERE `work_experience_id` = @contact_experience_id
      AND `highlight_text` = `source`.`highlight_text`
  );

INSERT INTO `work_experience_highlights`
  (`work_experience_id`, `highlight_text`, `display_order`)
SELECT
  @ebda3soft_experience_id,
  `source`.`highlight_text`,
  `source`.`display_order`
FROM (
  SELECT 'Developed estate and tourism systems using VB.NET and SQL Server.' AS `highlight_text`, 1 AS `display_order`
  UNION ALL SELECT 'Collaborated on HR system development as part of a small agile team.', 2
) AS `source`
WHERE @ebda3soft_experience_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `work_experience_highlights`
    WHERE `work_experience_id` = @ebda3soft_experience_id
      AND `highlight_text` = `source`.`highlight_text`
  );

INSERT INTO `work_experience_highlights`
  (`work_experience_id`, `highlight_text`, `display_order`)
SELECT
  @smartapps_experience_id,
  'Contributed to the development of AfrahOnline website with the team.',
  1
WHERE @smartapps_experience_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `work_experience_highlights`
    WHERE `work_experience_id` = @smartapps_experience_id
      AND `highlight_text` = 'Contributed to the development of AfrahOnline website with the team.'
  );

-- Validation
SELECT COUNT(*) AS `work_experience_highlight_records`
FROM `work_experience_highlights`
WHERE `work_experience_id` IN (
  @contact_experience_id,
  @ebda3soft_experience_id,
  @smartapps_experience_id
);

-- ====================================================
-- Educations
-- ====================================================

INSERT INTO `educations`
  (
    `profile_id`,
    `institution_name`,
    `degree`,
    `field_of_study`,
    `end_date`,
    `description`,
    `display_order`,
    `is_public`
  )
SELECT
  @profile_id,
  'Ministry of Youth & Sports + British Board',
  'Diploma',
  'Modern Business Administration',
  STR_TO_DATE('2017', '%Y'),
  '2017',
  1,
  1
WHERE @profile_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `educations`
    WHERE `profile_id` = @profile_id
      AND `institution_name` = 'Ministry of Youth & Sports + British Board'
      AND `degree` = 'Diploma'
  );

-- Validation
SELECT `education_id`, `institution_name`, `degree`
FROM `educations`
WHERE `profile_id` = @profile_id
  AND `institution_name` = 'Ministry of Youth & Sports + British Board'
  AND `degree` = 'Diploma';

-- TODO: Missing information: the institution name for "Bachelor''s in Information Systems" could not be reliably represented from the CV source.
-- TODO: Missing information: "Bachelor''s in Information Systems", "91.48%", and "2017" require the missing institution name before insertion.

-- ====================================================
-- Projects
-- ====================================================

INSERT INTO `projects`
  (
    `title`,
    `slug`,
    `short_description`,
    `description`,
    `status`,
    `display_order`,
    `is_public`
  )
VALUES
  (
    'AfrahOnline website',
    'afrahonline-website',
    'Contributed to the development of AfrahOnline website with the team.',
    'Contributed to the development of AfrahOnline website with the team.',
    'completed',
    1,
    1
  )
ON DUPLICATE KEY UPDATE
  `project_id` = LAST_INSERT_ID(`project_id`);

SET @afrahonline_project_id := LAST_INSERT_ID();

SELECT `project_id`
INTO @afrahonline_project_id
FROM `projects`
WHERE `slug` = 'afrahonline-website'
LIMIT 1;

-- Validation
SELECT @afrahonline_project_id AS `afrahonline_project_id`;

-- ====================================================
-- Certifications
-- ====================================================

INSERT INTO `certifications`
  (`profile_id`, `name`, `issuing_organization`, `issued_date`, `display_order`, `is_public`)
SELECT
  @profile_id,
  `source`.`name`,
  `source`.`issuing_organization`,
  STR_TO_DATE(`source`.`issued_year`, '%Y'),
  `source`.`display_order`,
  1
FROM (
  SELECT 'Agile Project Management' AS `name`, 'Edraak' AS `issuing_organization`, '2025' AS `issued_year`, 1 AS `display_order`
  UNION ALL SELECT 'Google IT Support Professional Certificate', 'Google', '2021', 2
  UNION ALL SELECT 'Introduction to Networking', 'Edraak', '2020', 3
  UNION ALL SELECT 'Cyber Security Certificate', 'Future Learning', '2018', 4
  UNION ALL SELECT 'Diploma in English Language', 'American Academy', '2017', 5
  UNION ALL SELECT 'A+ Computer Maintenance', 'Engineering Center', '2016', 6
) AS `source`
WHERE @profile_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `certifications`
    WHERE `profile_id` = @profile_id
      AND `name` = `source`.`name`
      AND `issuing_organization` = `source`.`issuing_organization`
  );

-- Validation
SELECT COUNT(*) AS `certification_records`
FROM `certifications`
WHERE `profile_id` = @profile_id;

-- TODO: Missing information: issuing organization for "Intro to Business Management".
-- TODO: Missing information: issuing organization for "Google E-marketing Skills".
-- TODO: Missing information: issuing organization for "Project Management & Human Development Diploma".
-- TODO: Missing information: individual certificate details for "Other certificates".
-- TODO: Missing information: credential IDs, credential URLs, exact issue dates, expiry dates, certificate files, and certificate images.

-- ====================================================
-- Languages
-- ====================================================

INSERT IGNORE INTO `languages` (`name`, `iso_code`) VALUES
  ('Arabic', 'ar'),
  ('English', 'en');

SET @arabic_language_id := NULL;
SET @english_language_id := NULL;

SELECT `language_id`
INTO @arabic_language_id
FROM `languages`
WHERE `name` = 'Arabic'
LIMIT 1;

SELECT `language_id`
INTO @english_language_id
FROM `languages`
WHERE `name` = 'English'
LIMIT 1;

-- Validation
SELECT @arabic_language_id AS `arabic_language_id`;
SELECT @english_language_id AS `english_language_id`;

-- ====================================================
-- Profile Languages
-- ====================================================

INSERT IGNORE INTO `profile_languages`
  (`profile_id`, `language_id`, `proficiency_level`, `display_order`, `is_public`)
SELECT
  @profile_id,
  @arabic_language_id,
  'Native',
  1,
  1
WHERE @profile_id IS NOT NULL
  AND @arabic_language_id IS NOT NULL;

INSERT IGNORE INTO `profile_languages`
  (`profile_id`, `language_id`, `proficiency_level`, `display_order`, `is_public`)
SELECT
  @profile_id,
  @english_language_id,
  'Intermediate level',
  2,
  1
WHERE @profile_id IS NOT NULL
  AND @english_language_id IS NOT NULL;

-- Validation
SELECT `profile_id`, `language_id`, `proficiency_level`
FROM `profile_languages`
WHERE `profile_id` = @profile_id;

-- ====================================================
-- Website Settings
-- ====================================================

INSERT INTO `website_settings`
  (`setting_key`, `setting_value`, `value_type`, `setting_group`, `description`, `is_public`)
VALUES
  ('website_name', 'Shuaib Al-Masri', 'string', 'general', 'Website Name', 0),
  ('website_tagline', 'ERP Developer', 'string', 'general', 'Website Tagline', 0),
  ('website_description', 'ERP Developer with over 5 years of experience in designing and developing efficient business systems for Human Resources, Asset Management, Inventory & Procurement, Sales, and Customer Operations. These systems are actively used by various banks and public and private institutions, including Al-Qutaibi Bank, Al-Sharq Bank, and the Traffic Police Department in Aden. Skilled in C#, SQL Server, and Web API development. Proficient in API integration, cross-platform mobile app development using Flutter. In addition to the above technical skills, I manage and supervise the team specializing in developing other systems (banking systems, exchange systems, and other subsystems) as the head of the company''s development department, which allows me to combine technical and managerial skills.', 'string', 'general', 'Website Description', 0),
  ('contact_email', 'sh.almassri2013@gmail.com', 'string', 'contact', 'Contact Email', 0),
  ('contact_phone', '+967 773046555', 'string', 'contact', 'Contact Phone', 0)
ON DUPLICATE KEY UPDATE
  `setting_value` = VALUES(`setting_value`),
  `value_type` = VALUES(`value_type`),
  `setting_group` = VALUES(`setting_group`),
  `description` = VALUES(`description`),
  `is_public` = VALUES(`is_public`);

-- Validation
SELECT `setting_key`, `setting_value`
FROM `website_settings`
WHERE `setting_key` IN (
  'website_name',
  'website_tagline',
  'website_description',
  'contact_email',
  'contact_phone'
)
ORDER BY `setting_key`;

COMMIT;

-- ====================================================
-- Final Import Report
-- ====================================================
-- Inserted tables:
-- users, profiles, skill_categories, skills, work_experiences,
-- work_experience_highlights, educations, projects, certifications,
-- languages, profile_languages, website_settings.
--
-- Each parent key is retrieved immediately after insertion:
-- users: email unique key.
-- profiles: email lookup.
-- work experiences: profile ID, employer name, and job title.
-- projects: slug unique key.
-- languages: name unique key.
--
-- Every child insert is protected with:
-- WHERE @parent_id IS NOT NULL
--
-- The script is idempotent:
-- unique-key tables use INSERT IGNORE or ON DUPLICATE KEY UPDATE;
-- non-unique parent tables use NOT EXISTS guards before insertion;
-- child rows use NOT EXISTS guards before insertion.
```