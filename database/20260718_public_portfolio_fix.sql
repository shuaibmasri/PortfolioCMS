-- Align older installations with the public project query and seed CV projects.
ALTER TABLE `projects`
  ADD COLUMN IF NOT EXISTS `status` VARCHAR(30) NOT NULL DEFAULT 'planned' AFTER `is_public`;

UPDATE `projects` SET `status` = 'completed' WHERE `status` = 'planned' AND `is_public` = 1;

INSERT INTO `projects` (`title`, `slug`, `short_description`, `description`, `status`, `display_order`, `is_public`)
SELECT seed.title, seed.slug, seed.short_description, seed.description, 'completed', seed.display_order, 1
FROM (
  SELECT 'ERP System' AS title, 'erp-system' AS slug, 'Integrated ERP modules for operational workflows.' AS short_description, 'Designed and implemented ERP modules including HR, Asset Management, and Stock & Purchase systems.' AS description, 1 AS display_order
  UNION ALL SELECT 'HR System', 'hr-system', 'Human resources management module.', 'Designed HR workflows for employee records, attendance, and personnel operations.', 2
  UNION ALL SELECT 'Asset Management System', 'asset-management-system', 'Asset lifecycle and inventory management.', 'Developed asset registration, tracking, and reporting capabilities.', 3
  UNION ALL SELECT 'Stock & Purchase System', 'stock-purchase-system', 'Stock control and purchasing workflows.', 'Implemented inventory, purchase, supplier, and stock movement management.', 4
  UNION ALL SELECT 'e-Invoice System', 'e-invoice-system', 'Electronic invoicing for Gulf customers.', 'Developed e-Invoice systems for Saudi Arabian and Gulf market requirements.', 5
  UNION ALL SELECT 'Hospital System', 'hospital-system', 'Hospital management solution.', 'Supervised development of hospital operations and supporting subsystems.', 6
  UNION ALL SELECT 'Clinic System', 'clinic-system', 'Clinic management solution.', 'Supervised development of clinic operations and supporting subsystems.', 7
  UNION ALL SELECT 'Water Project Management System', 'water-project-management-system', 'Water project management workflows.', 'Developed management capabilities for water project operations.', 8
  UNION ALL SELECT 'Electricity Project Management System', 'electricity-project-management-system', 'Electricity project management workflows.', 'Developed management capabilities for electricity project operations.', 9
  UNION ALL SELECT 'Flutter Apps', 'flutter-apps', 'Cross-platform mobile applications.', 'Built and deployed cross-platform mobile applications using Flutter.', 10
  UNION ALL SELECT 'APIs', 'apis', 'Backend API development and integrations.', 'Created and maintained APIs that integrate web and mobile applications with backend services.', 11
  UNION ALL SELECT 'AfrahOnline Website', 'afrahonline-website', 'AfrahOnline website contribution.', 'Contributed to the development of the AfrahOnline website with the team.', 12
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `projects` AS existing_project WHERE existing_project.slug = seed.slug);

INSERT INTO `technologies` (`name`, `slug`)
SELECT seed.name, seed.slug
FROM (
  SELECT 'PHP' AS name, 'php' AS slug UNION ALL SELECT 'MySQL', 'mysql' UNION ALL SELECT 'Bootstrap 5', 'bootstrap-5' UNION ALL SELECT 'Flutter', 'flutter' UNION ALL SELECT 'REST API', 'rest-api'
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `technologies` AS existing_technology WHERE existing_technology.slug = seed.slug);

INSERT IGNORE INTO `project_technologies` (`project_id`, `technology_id`)
SELECT p.project_id, t.technology_id
FROM `projects` AS p
INNER JOIN `technologies` AS t ON (
  (p.slug = 'flutter-apps' AND t.slug = 'flutter')
  OR (p.slug = 'apis' AND t.slug = 'rest-api')
  OR (p.slug = 'afrahonline-website' AND t.slug IN ('php', 'mysql', 'bootstrap-5'))
  OR (p.slug IN ('erp-system', 'hr-system', 'asset-management-system', 'stock-purchase-system', 'e-invoice-system', 'hospital-system', 'clinic-system', 'water-project-management-system', 'electricity-project-management-system') AND t.slug IN ('php', 'mysql'))
);
