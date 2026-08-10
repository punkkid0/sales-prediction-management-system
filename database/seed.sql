-- Seed data for Sales Prediction Management System (Phase 1)
-- Default login password for all users: password123
-- Hash: bcrypt of "password123" ($2y$ compatible with PHP password_verify)

USE sales_prediction_db;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE sales;
TRUNCATE TABLE promotions;
TRUNCATE TABLE products;
TRUNCATE TABLE customers;
TRUNCATE TABLE suppliers;
TRUNCATE TABLE users;
TRUNCATE TABLE forecasts;
TRUNCATE TABLE model_runs;
SET FOREIGN_KEY_CHECKS = 1;

-- password123 (PHP password_hash)
SET @pwd = '$2y$10$zcMtOz2Vtwo8lQaLDSAaOO6I7Hr7UVbwaLIrY9o3GHRhg6EqrKtQC';

INSERT INTO users (name, email, password_hash, role, is_active) VALUES
('System Admin', 'admin@spms.local', @pwd, 'admin', 1),
('Sales Manager', 'manager@spms.local', @pwd, 'manager', 1),
('Staff One', 'staff1@spms.local', @pwd, 'staff', 1),
('Staff Two', 'staff2@spms.local', @pwd, 'staff', 1);

INSERT INTO suppliers (name, phone, email, address) VALUES
('Global Supplies Ltd', '08011110001', 'contact@globalsupplies.test', '12 Industrial Road'),
('FreshGoods Partners', '08011110002', 'hello@freshgoods.test', '45 Market Street'),
('TechParts Nigeria', '08011110003', 'sales@techparts.test', '8 Computer Village');

INSERT INTO customers (name, phone, email, address) VALUES
('Walk-in Customer', NULL, NULL, NULL),
('Ada Okonkwo', '08022220001', 'ada@example.com', 'Lagos'),
('Chinedu Bello', '08022220002', 'chinedu@example.com', 'Abuja'),
('Fatima Yusuf', '08022220003', 'fatima@example.com', 'Kano'),
('Retail Mart PLC', '08022220004', 'orders@retailmart.test', 'Port Harcourt');

INSERT INTO products (name, category, unit_price, stock_qty, reorder_level, supplier_id, is_active) VALUES
('Rice 50kg', 'Food', 45000.00, 80, 15, 1, 1),
('Vegetable Oil 5L', 'Food', 8500.00, 120, 20, 1, 1),
('Indomie Carton', 'Food', 6200.00, 200, 30, 2, 1),
('Peak Milk Tin', 'Food', 1800.00, 150, 25, 2, 1),
('Sugar 1kg', 'Food', 1500.00, 90, 20, 1, 1),
('USB Flash 32GB', 'Electronics', 4500.00, 60, 10, 3, 1),
('Power Bank 10000mAh', 'Electronics', 12000.00, 40, 8, 3, 1),
('Wireless Mouse', 'Electronics', 5500.00, 55, 10, 3, 1),
('Notebook A4 Pack', 'Stationery', 2500.00, 100, 15, 1, 1),
('Ballpoint Pen Box', 'Stationery', 1800.00, 75, 12, 1, 1),
('Detergent 1kg', 'Household', 2200.00, 70, 15, 2, 1),
('Tissue Pack', 'Household', 900.00, 5, 20, 2, 1);

INSERT INTO promotions (product_id, title, discount_pct, start_date, end_date, is_active) VALUES
(1, 'Rice Weekend Promo', 5.00, DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1),
(3, 'Noodles Bonanza', 10.00, DATE_SUB(CURDATE(), INTERVAL 14 DAY), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 1),
(NULL, 'Store-wide Holiday Teaser', 3.00, DATE_SUB(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 1),
(6, 'Gadgets Week', 8.00, DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_SUB(CURDATE(), INTERVAL 30 DAY), 0);

INSERT INTO sales (product_id, customer_id, staff_id, quantity, unit_price, total, sale_date) VALUES
(1, 2, 3, 2, 45000.00, 90000.00, DATE_SUB(CURDATE(), INTERVAL 6 DAY)),
(2, 3, 3, 5, 8500.00, 42500.00, DATE_SUB(CURDATE(), INTERVAL 6 DAY)),
(3, 1, 4, 10, 6200.00, 62000.00, DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
(4, 4, 3, 8, 1800.00, 14400.00, DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
(5, 2, 4, 6, 1500.00, 9000.00, DATE_SUB(CURDATE(), INTERVAL 4 DAY)),
(6, 5, 3, 3, 4500.00, 13500.00, DATE_SUB(CURDATE(), INTERVAL 4 DAY)),
(7, 3, 4, 2, 12000.00, 24000.00, DATE_SUB(CURDATE(), INTERVAL 3 DAY)),
(8, 1, 3, 4, 5500.00, 22000.00, DATE_SUB(CURDATE(), INTERVAL 3 DAY)),
(9, 2, 4, 5, 2500.00, 12500.00, DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(10, 4, 3, 3, 1800.00, 5400.00, DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(11, 1, 4, 4, 2200.00, 8800.00, DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(12, 5, 3, 2, 900.00, 1800.00, DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(1, 3, 3, 1, 45000.00, 45000.00, CURDATE()),
(3, 1, 4, 6, 6200.00, 37200.00, CURDATE()),
(6, 2, 3, 2, 4500.00, 9000.00, CURDATE());
