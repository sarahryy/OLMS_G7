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