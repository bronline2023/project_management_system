INSERT INTO `roles` (`role_name`, `permissions`, `dashboard_permissions`)
SELECT * FROM (SELECT 'Master Admin', '*', '["show_financial_summary","show_task_summary","show_user_client_summary","show_appointment_summary","show_pending_actions","show_recent_activity","show_notifications"]') AS tmp
WHERE NOT EXISTS (SELECT role_name FROM `roles` WHERE role_name = 'Master Admin') LIMIT 1;

INSERT INTO `roles` (`role_name`, `permissions`, `dashboard_permissions`)
SELECT * FROM (SELECT 'Super Admin', '*', '["show_financial_summary","show_task_summary","show_user_client_summary","show_appointment_summary","show_pending_actions","show_recent_activity","show_notifications"]') AS tmp
WHERE NOT EXISTS (SELECT role_name FROM `roles` WHERE role_name = 'Super Admin') LIMIT 1;

INSERT INTO `roles` (`role_name`, `permissions`, `dashboard_permissions`)
SELECT * FROM (SELECT 'District Manager', '["clients","appointments","reports","user_dashboard"]', '["show_financial_summary","show_task_summary"]') AS tmp
WHERE NOT EXISTS (SELECT role_name FROM `roles` WHERE role_name = 'District Manager') LIMIT 1;

INSERT INTO `roles` (`role_name`, `permissions`, `dashboard_permissions`)
SELECT * FROM (SELECT 'Retailer', '["my_tasks","my_appointments","submit_work","worker_dashboard"]', '["show_task_summary"]') AS tmp
WHERE NOT EXISTS (SELECT role_name FROM `roles` WHERE role_name = 'Retailer') LIMIT 1;
