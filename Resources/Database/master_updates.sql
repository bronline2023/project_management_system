-- Master DB Updates for B2B SaaS and B2C Wallets

-- We will use the existing subscription_plans schema.
-- Checking and inserting default plans into the existing table schema.
INSERT INTO `subscription_plans` (`plan_name`, `monthly_price`, `yearly_price`, `features`)
SELECT * FROM (SELECT 'Standard Project Management', 500.00, 5000.00, 'Tasks, Invoicing, Customers') AS tmp
WHERE NOT EXISTS (SELECT plan_name FROM `subscription_plans` WHERE plan_name = 'Standard Project Management') LIMIT 1;

INSERT INTO `subscription_plans` (`plan_name`, `monthly_price`, `yearly_price`, `features`)
SELECT * FROM (SELECT 'Advanced Digital Services', 1000.00, 10000.00, 'Tasks, Invoicing, Customers, HR, Digital Services') AS tmp
WHERE NOT EXISTS (SELECT plan_name FROM `subscription_plans` WHERE plan_name = 'Advanced Digital Services') LIMIT 1;

-- 2. Tenants (Subscribers)
CREATE TABLE IF NOT EXISTS `tenants` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `company_name` varchar(255) NOT NULL,
    `owner_name` varchar(255) NOT NULL,
    `owner_email` varchar(255) NOT NULL UNIQUE,
    `owner_phone` varchar(20) NOT NULL,
    `domain_name` varchar(255) NOT NULL UNIQUE, 
    `db_name` varchar(255) NOT NULL UNIQUE,
    `db_user` varchar(255) NOT NULL DEFAULT 'root', 
    `db_pass` varchar(255) NOT NULL DEFAULT '',
    `folder_path` varchar(255) NOT NULL, 
    `plan_id` int(11) NOT NULL,
    `subscription_start` date NOT NULL,
    `subscription_end` date NOT NULL,
    `status` enum('pending','active','suspended','expired') NOT NULL DEFAULT 'pending',
    `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert a Demo Tenant for testing the architecture (mapped to localhost initially or maybe demo.localhost)
INSERT INTO `tenants` (`company_name`, `owner_name`, `owner_email`, `owner_phone`, `domain_name`, `db_name`, `folder_path`, `plan_id`, `subscription_start`, `subscription_end`, `status`)
SELECT * FROM (SELECT 'Demo Corp', 'Demo Owner', 'demo@tenant.com', '9999999999', 'demo.localhost', 'pms_tenant_demo', 'uploads/tenants/demo_localhost', 2, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'active') AS tmp
WHERE NOT EXISTS (SELECT domain_name FROM `tenants` WHERE domain_name = 'demo.localhost') LIMIT 1;

-- 3. B2C Wallets
CREATE TABLE IF NOT EXISTS `b2c_wallets` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` int(11) NOT NULL UNIQUE,
    `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
    `points` int(11) NOT NULL DEFAULT 0,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `b2c_wallet_transactions` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `wallet_id` int(11) NOT NULL,
    `type` enum('credit','debit') NOT NULL,
    `amount` decimal(10,2) NOT NULL,
    `description` varchar(255) NOT NULL,
    `transaction_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Google Auth ID column in Users (for B2C sign in)
SET @dbname = DATABASE();
SET @tablename = 'users';
SET @columnname = 'google_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD ", @columnname, " VARCHAR(255) DEFAULT NULL;")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
