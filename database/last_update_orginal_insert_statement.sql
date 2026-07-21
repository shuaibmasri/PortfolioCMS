```sql
-- ====================================================
-- Profiles: Location and Optional User Link
-- ====================================================

UPDATE `profiles`
SET `location` = 'Sana''a, Yemen'
WHERE `profile_id` = @profile_id
  AND @profile_id IS NOT NULL;

SET @profiles_has_user_id := (
  SELECT COUNT(*)
  FROM `information_schema`.`columns`
  WHERE `table_schema` = DATABASE()
    AND `table_name` = 'profiles'
    AND `column_name` = 'user_id'
);

SET @link_profile_user_sql := IF(
  @profiles_has_user_id > 0,
  'UPDATE `profiles` SET `user_id` = ? WHERE `profile_id` = ?',
  'SELECT ''profiles.user_id does not exist; profile-user link skipped.'' AS message'
);

PREPARE `link_profile_user_statement` FROM @link_profile_user_sql;
EXECUTE `link_profile_user_statement` USING @user_id, @profile_id;
DEALLOCATE PREPARE `link_profile_user_statement`;

-- Validation
SELECT @profile_id AS `profile_id`, @user_id AS `user_id`, @profiles_has_user_id AS `profiles_has_user_id`;

-- ====================================================
-- Educations: Bachelor''s Degree
-- ====================================================

INSERT INTO `educations`
  (
    `profile_id`,
    `institution_name`,
    `degree`,
    `field_of_study`,
    `start_date`,
    `end_date`,
    `grade`,
    `display_order`,
    `is_public`
  )
SELECT
  @profile_id,
  'Sana''a University',
  'Bachelor''s degree',
  'Information Systems',
  STR_TO_DATE('2013', '%Y'),
  STR_TO_DATE('2017', '%Y'),
  '91.48%',
  1,
  1
WHERE @profile_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `educations`
    WHERE `profile_id` = @profile_id
      AND `institution_name` = 'Sana''a University'
      AND `degree` = 'Bachelor''s degree'
      AND `field_of_study` = 'Information Systems'
  );

SET @bachelor_education_id := IF(ROW_COUNT() = 1, LAST_INSERT_ID(), NULL);

SELECT `education_id`
INTO @bachelor_education_id
FROM `educations`
WHERE `profile_id` = @profile_id
  AND `institution_name` = 'Sana''a University'
  AND `degree` = 'Bachelor''s degree'
  AND `field_of_study` = 'Information Systems'
ORDER BY `education_id`
LIMIT 1;

-- Validation
SELECT @bachelor_education_id AS `bachelor_education_id`;

-- ====================================================
-- Projects: Major CV Systems
-- ====================================================

INSERT IGNORE INTO `projects`
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
  ('ERP System', 'erp-system', 'Designed and implemented ERP modules.', 'Designed and implemented ERP modules including HR, Asset Management, and Stock & Purchase systems.', 'completed', 1, 1),
  ('HR System', 'hr-system', 'Designed and implemented ERP modules including HR.', 'Designed and implemented ERP modules including HR, Asset Management, and Stock & Purchase systems.', 'completed', 2, 1),
  ('Asset Management System', 'asset-management-system', 'Designed and implemented ERP modules including Asset Management.', 'Designed and implemented ERP modules including HR, Asset Management, and Stock & Purchase systems.', 'completed', 3, 1),
  ('Stock & Purchase System', 'stock-purchase-system', 'Designed and implemented ERP modules including Stock & Purchase systems.', 'Designed and implemented ERP modules including HR, Asset Management, and Stock & Purchase systems.', 'completed', 4, 1),
  ('e-Invoice System', 'e-invoice-system', 'Developed e-Invoice systems specifically for Gulf and Saudi Arabian customers.', 'Developed e-Invoice systems specifically for Gulf and Saudi Arabian customers.', 'completed', 5, 1),
  ('Water Project Management System', 'water-project-management-system', 'Development of water and electricity project management systems.', 'Development of water and electricity project management systems.', 'completed', 6, 1),
  ('Electricity Project Management System', 'electricity-project-management-system', 'Development of water and electricity project management systems.', 'Development of water and electricity project management systems.', 'completed', 7, 1),
  ('Hospital System', 'hospital-system', 'Supervising the team specialized in developing sub-systems such as the hospital and clinic system.', 'Supervising the team specialized in developing sub-systems such as the hospital and clinic system.', 'completed', 8, 1),
  ('Clinic System', 'clinic-system', 'Supervising the team specialized in developing sub-systems such as the hospital and clinic system.', 'Supervising the team specialized in developing sub-systems such as the hospital and clinic system.', 'completed', 9, 1),
  ('Flutter Apps', 'flutter-apps', 'Built and deployed cross-platform mobile applications using Flutter.', 'Built and deployed cross-platform mobile applications using Flutter.', 'completed', 10, 1),
  ('APIs', 'apis', 'Created and maintained APIs for integrating front-end and mobile apps with backend systems.', 'Created and maintained APIs for integrating front-end and mobile apps with backend systems.', 'completed', 11, 1);

-- Validation
SELECT `project_id`, `title`, `slug`
FROM `projects`
WHERE `slug` IN (
  'erp-system',
  'hr-system',
  'asset-management-system',
  'stock-purchase-system',
  'e-invoice-system',
  'water-project-management-system',
  'electricity-project-management-system',
  'hospital-system',
  'clinic-system',
  'flutter-apps',
  'apis'
)
ORDER BY `display_order`;

-- ====================================================
-- Technologies: All CV Technologies
-- ====================================================

INSERT IGNORE INTO `technologies` (`name`, `slug`) VALUES
  ('C#', 'c-sharp'),
  ('ASP.NET', 'asp-net'),
  ('VB.NET', 'vb-net'),
  ('SQL', 'sql'),
  ('Dart', 'dart'),
  ('PHP', 'php'),
  ('.NET Framework', 'dot-net-framework'),
  ('.NET Core', 'dot-net-core'),
  ('Flutter', 'flutter'),
  ('SQL Server', 'sql-server'),
  ('Visual Studio', 'visual-studio'),
  ('Visual Code', 'visual-code'),
  ('Android Studio', 'android-studio'),
  ('Git', 'git'),
  ('Postman', 'postman'),
  ('SSMS', 'ssms');

-- Validation
SELECT `technology_id`, `name`, `slug`
FROM `technologies`
WHERE `slug` IN (
  'c-sharp',
  'asp-net',
  'vb-net',
  'sql',
  'dart',
  'php',
  'dot-net-framework',
  'dot-net-core',
  'flutter',
  'sql-server',
  'visual-studio',
  'visual-code',
  'android-studio',
  'git',
  'postman',
  'ssms'
)
ORDER BY `name`;

-- ====================================================
-- TODO: Social Links and Project Assets
-- ====================================================

-- TODO: Missing GitHub profile URL.
-- TODO: Missing LinkedIn profile URL.
-- TODO: Missing public portfolio URL.
-- TODO: Missing project screenshots for project_images.
```