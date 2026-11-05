-- Crescita: 7-Level BFS 3-Matrix Database Schema
-- Database: pinnacle_hunt

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS ledger;
DROP TABLE IF EXISTS commissions;
DROP TABLE IF EXISTS withdrawals;
DROP TABLE IF EXISTS matrix_nodes;
DROP TABLE IF EXISTS user_packages;
DROP TABLE IF EXISTS packages;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS settings;

SET FOREIGN_KEY_CHECKS = 1;

-- Users table
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    sponsor_id INT UNSIGNED NULL,
    wallet_balance DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    reserve_balance DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_sponsor FOREIGN KEY (sponsor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Packages table (10 sequential tiers)
CREATE TABLE packages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    price DECIMAL(15, 2) NOT NULL,
    direct_commission_percent DECIMAL(5, 2) NOT NULL DEFAULT 20.00,
    sort_order INT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Packages (Enrollments & Progressions)
CREATE TABLE user_packages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    package_id INT UNSIGNED NOT NULL,
    status ENUM('active', 'completed', 'upgraded') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_up_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_up_package FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
    UNIQUE KEY uk_user_pkg (user_id, package_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Matrix Nodes (Company-Wide BFS 3-Matrix per Package Tier)
CREATE TABLE matrix_nodes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    package_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    parent_id INT UNSIGNED NULL,
    depth INT UNSIGNED NOT NULL DEFAULT 1,
    position_index TINYINT UNSIGNED NOT NULL DEFAULT 1,
    children_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mn_pkg FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
    CONSTRAINT fk_mn_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_mn_parent FOREIGN KEY (parent_id) REFERENCES matrix_nodes(id) ON DELETE SET NULL,
    INDEX idx_pkg_depth (package_id, depth),
    INDEX idx_pkg_parent_children (package_id, parent_id, children_count),
    INDEX idx_pkg_user (package_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Commissions Table
CREATE TABLE commissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    source_user_id INT UNSIGNED NOT NULL,
    package_id INT UNSIGNED NOT NULL,
    type ENUM('direct_sponsor', 'matrix_level') NOT NULL,
    level TINYINT UNSIGNED NULL,
    amount DECIMAL(15, 2) NOT NULL,
    reserved_amount DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    net_amount DECIMAL(15, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_comm_src FOREIGN KEY (source_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_comm_pkg FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Financial Ledger Table
CREATE TABLE ledger (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type ENUM('credit', 'debit', 'reserve_hold', 'reserve_release', 'upgrade_payment') NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    balance_after DECIMAL(15, 2) NOT NULL,
    reserve_after DECIMAL(15, 2) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ledger_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Withdrawals Table
CREATE TABLE withdrawals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    payout_details TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    processed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_wd_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings Table
CREATE TABLE settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    description VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed 10 Investment Packages
INSERT INTO packages (id, name, price, direct_commission_percent, sort_order, status) VALUES
(1, 'Tier 1 - Starter', 500.00, 20.00, 1, 'active'),
(2, 'Tier 2 - Basic', 1000.00, 20.00, 2, 'active'),
(3, 'Tier 3 - Bronze', 2000.00, 20.00, 3, 'active'),
(4, 'Tier 4 - Pro', 3000.00, 20.00, 4, 'active'),
(5, 'Tier 5 - Silver', 4000.00, 20.00, 5, 'active'),
(6, 'Tier 6 - Advanced', 5000.00, 20.00, 6, 'active'),
(7, 'Tier 7 - Executive', 10000.00, 20.00, 7, 'active'),
(8, 'Tier 8 - Master', 25000.00, 20.00, 8, 'active'),
(9, 'Tier 9 - Elite', 50000.00, 20.00, 9, 'active'),
(10, 'Tier 10 - Pinnacle', 100000.00, 20.00, 10, 'active')
ON DUPLICATE KEY UPDATE name=VALUES(name), price=VALUES(price);

-- Seed System Settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('reserve_deduction_rate', '25.00', 'Percentage of matrix commissions withheld for auto-upgrades'),
('direct_sponsor_percent', '20.00', 'Global direct referral commission percentage (zero reserve cutting)'),
('direct_referrals_required_for_upgrade', '5', 'Direct referrals required to qualify for next matrix without Level 2 deduction'),
('level_2_unqualified_deduction_percent', '50.00', 'Percentage deducted from Matrix Level 2 into reserve pool if user has < 5 direct referrals'),
('matrix_level_1_percent', '12.50', 'Matrix Level 1 Payout Percentage'),
('matrix_level_2_percent', '10.00', 'Matrix Level 2 Payout Percentage'),
('matrix_level_3_percent', '7.50', 'Matrix Level 3 Payout Percentage'),
('matrix_level_4_percent', '5.00', 'Matrix Level 4 Payout Percentage'),
('matrix_level_5_percent', '2.50', 'Matrix Level 5 Payout Percentage'),
('matrix_level_6_percent', '1.25', 'Matrix Level 6 Payout Percentage'),
('matrix_level_7_percent', '1.25', 'Matrix Level 7 Payout Percentage')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

-- Seed Admin User (password: Admin@123456)
INSERT INTO users (id, username, email, password, full_name, phone, role, status) VALUES
(1, 'admin', 'admin@crescita.com', '$2y$10$ptr0wM7lL6Cb/b5srwPFhu6Mcgd37Y6mEwxkcL7d4lnAKx57utIdu', 'System Administrator', '+919999999999', 'admin', 'active')
ON DUPLICATE KEY UPDATE role='admin', password=VALUES(password);

-- Seed Default Affiliate User 'rose' (password: Rose@123456)
INSERT INTO users (id, username, email, password, full_name, phone, role, sponsor_id, status) VALUES
(2, 'rose', 'rose@crescita.com', '$2y$10$ptr0wM7lL6Cb/b5srwPFhu6Mcgd37Y6mEwxkcL7d4lnAKx57utIdu', 'Rose Affiliate', '+919888888888', 'user', 1, 'active')
ON DUPLICATE KEY UPDATE username='rose', password=VALUES(password);
