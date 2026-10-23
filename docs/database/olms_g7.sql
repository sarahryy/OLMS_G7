-- ============================================================
-- File: olms_g7.sql
-- Module: Database Schema
-- Description: Schema and seed data for OLMS_G7 StationeryMart.
-- Author: [Your Name - SITI SARAH RAIHANAH BINTI AZIZAN]
-- Created: 03/10/2026
-- ============================================================

CREATE DATABASE IF NOT EXISTS olms_g7
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE olms_g7;

-- ------------------------------------------------------------
-- Table: categories
-- ------------------------------------------------------------
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;

CREATE TABLE categories (
  category_id   INT AUTO_INCREMENT PRIMARY KEY,
  category_name VARCHAR(100) NOT NULL UNIQUE
);

-- ------------------------------------------------------------
-- Table: products
-- ------------------------------------------------------------
CREATE TABLE products (
  product_id     INT AUTO_INCREMENT PRIMARY KEY,
  product_name   VARCHAR(150)  NOT NULL,
  description    TEXT,
  price          DECIMAL(10,2) NOT NULL,
  stock_quantity INT           NOT NULL DEFAULT 0,
  brand          VARCHAR(100),
  category_id    INT,
  image_url      VARCHAR(255),
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_products_category
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
    ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- Seed data: categories
-- ------------------------------------------------------------
INSERT INTO categories (category_name) VALUES
  ('Pens & Pencils'),
  ('Paper & Notebooks'),
  ('Files & Folders'),
  ('Art Supplies'),
  ('Office Supplies'),
  ('School Kits');

-- ------------------------------------------------------------
-- Seed data: products (sample)
-- ------------------------------------------------------------
INSERT INTO products
  (product_name, description, price, stock_quantity, brand, category_id, image_url)
VALUES
  ('Pilot G2 Gel Pen 0.5mm', 'Smooth-writing retractable gel pen.', 6.50, 120, 'Pilot', 1, ''),
  ('Faber-Castell 2B Pencil', 'Classic graphite pencil for writing and sketching.', 1.20, 300, 'Faber-Castell', 1, ''),
  ('A4 Exercise Book 80pg', 'Ruled exercise book, 80 pages.', 2.80, 200, 'Campap', 2, ''),
  ('A4 Ring File 2 inch', 'Durable 2-inch ring file with spine label.', 8.90, 75, 'Suremark', 3, ''),
  ('Acrylic Paint Set 12 colours', '12 x 12ml acrylic paint tubes.', 15.90, 40, 'Reeves', 4, ''),
  ('Stapler Medium', 'Medium-duty stapler with 1000 staples.', 9.90, 60, 'Max', 5, ''),
  ('Primary School Starter Kit', 'Complete starter kit for primary students.', 29.90, 25, 'StationeryMart', 6, '');


  -- --------------------------------------------------------
-- Table structure for `users` (Base user authentication)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    role ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `customers` (Extends users)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS customers (
    customer_id INT PRIMARY KEY,
    loyalty_points INT DEFAULT 0,
    registration_date DATE DEFAULT (CURRENT_DATE),
    CONSTRAINT fk_customers_user FOREIGN KEY (customer_id) 
        REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed Data for Default Admin & Test Customer
-- --------------------------------------------------------
-- Note: Default password for both is 'Password123'
INSERT INTO users (name, email, password, phone_number, role) VALUES 
('System Admin', 'admin@stationerymart.com', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lR0e0vA5k1qZ8x8j8O0F4bYx6J2Ki', '0123456789', 'admin'),
('Ali Customer', 'ali@example.com', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lR0e0vA5k1qZ8x8j8O0F4bYx6J2Ki', '0198765432', 'customer')
ON DUPLICATE KEY UPDATE email = email;

INSERT INTO customers (customer_id, loyalty_points) 
SELECT user_id, 50 FROM users WHERE email = 'ali@example.com'
ON DUPLICATE KEY UPDATE customer_id = customer_id;