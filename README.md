# StationeryMart — Online Local Mart System (OLMS)

**CSEB5223 Software Construction & Methods**
Semester 1, 2026/2027 · Universiti Tenaga Nasional
Lecturer: Dr. Yim Ling Loo

---

## 1. Project Description

StationeryMart is a web-based Online Local Mart System that brings a local
stationery shop online. Customers can browse the stationery catalogue by
category, search for products, add items to a cart, and place orders for
delivery or self-collection. An administrator manages the product catalogue,
categories, stock levels, customer accounts and incoming orders.

The system is developed incrementally across Labs 1–10 as the semester
project for CSEB5223, with each lab adding a layer of functionality on top
of the pre-construction plan defined in Lab 2.

---

## 2. Team Members

| Name | Student ID | 
|------|-----------|
| SITI SARAH RAIHANAH BINTI AZIZAN | BSW01085701 | 
| SHARIFAH SOFIYYAH LU’LU’ BINTI SYED FIRDAUS HILMI | BSW01086483 |
| SAVITTA RAM A/P P.RAMASAMY | BSW01085816 |
| NUR DINI NADHIRAH BINTI ABDUL RASHID | BSW01085717 |

---

## 3. Technology Stack

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5, CSS3, JavaScript |
| Backend | PHP |
| Database | MySQL (phpMyAdmin) |
| Web Server | Apache (WAMP) |
| Environment | Web-based |
| Version Control | Git / GitHub |
| Design Tools | Mermaid / draw.io (UML diagrams) |

---

## 4. System Modules

| Module | Description | Classes Involved |
|--------|-------------|------------------|
| User Management | Registration, login, profile management, role separation | User, Customer, Admin |
| Product Catalogue | Product listing, search, categorisation | Product, Category |
| Shopping Cart | Add, update and remove items before checkout | Cart, CartItem |
| Order Management | Order placement, status tracking, cancellation | Order, OrderItem |
| Payment | Payment recording and receipt generation | Payment |
| Shipping | Delivery tracking and status updates | Shipping |
| Review | Customer product reviews and ratings | Review |

---

## 5. Class Overview

The system consists of 12 classes. User is an abstract class inherited by
Customer and Admin.

| Class | Type | Purpose |
|-------|------|---------|
| User | Abstract | Shared identity and authentication attributes |
| Customer | Concrete | Browses products, places orders, writes reviews |
| Admin | Concrete | Manages products, orders, users and reports |
| Product | Entity | A stationery item available for purchase |
| Category | Entity | Groups products (Pens, Paper, Files, etc.) |
| Cart | Entity | A customer's active shopping cart |
| CartItem | Entity | A single line within a cart |
| Order | Entity | A confirmed purchase |
| OrderItem | Entity | A single line within an order |
| Payment | Entity | Payment record for an order |
| Shipping | Entity | Delivery tracking for an order |
| Review | Entity | A customer's rating and comment on a product |

Full class diagram: /docs/diagrams/class-diagram.png

---

## 6. Key Design Decisions

Abstract User class. Customer and Admin share identity and
authentication attributes, so these are defined once in an abstract parent
and inherited. Shared attributes use protected (#) visibility so
subclasses can access them directly.

Composition for line items. Order composes OrderItem and Cart
composes CartItem (filled diamond). A line item has no meaning
independently of its parent and is deleted with it.

Category as a separate class. Category is modelled as its own class
rather than a string attribute on Product, so category names remain
consistent and can be managed centrally by the administrator.

shippingAddress stored on Order, not Shipping. The delivery
address is held as a snapshot on Order at the time of purchase. If it
referenced the customer's profile address instead, updating that profile
would retroactively alter the address shown on past orders. Shipping
does not duplicate the attribute; it retrieves the address through its
bidirectional association with Order, keeping one authoritative source
for the value.

No foreign keys in the class diagram. Relationships are expressed as
associations with multiplicity. Foreign keys appear only in the database
schema.

---

## 7. Coding Standards

### 7.1 Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Class | PascalCase | Product, OrderItem |
| Variable | camelCase | $productPrice, $cartTotal |
| Function / Method | camelCase, verb-led | getProductById(), calculateSubtotal() |
| Constant | UPPER_SNAKE_CASE | MAX_ORDER_QUANTITY |
| Boolean | is / has prefix | $isAvailable, $hasStock |
| PHP file | kebab-case | product-service.php |
| HTML / CSS / JS file | kebab-case | product-list.php, main-style.css |
| CSS class | kebab-case | .product-card |
| HTML id | camelCase | productSearchInput |
| Database table | lowercase plural | products, order_items |
| Database column | snake_case | product_id, created_at |

### 7.2 Term Dictionary

Agreed vocabulary — all members use these terms exactly.

| Concept | Use this | Never use |
|---------|----------|-----------|
| Product identifier | productId | prodID, pID, itemId |
| Product name | productName | name, title |
| Category | category | type, group |
| Stock quantity | quantity | qty, stock |
| Customer | customer | user, buyer|
| Admin | admin | staff, employee|
| Order line | orderItem | cartItem, lineItem |
| Price per unit | unitPrice | price (reserved for Product only) |
| Line total | subtotal | total, lineTotal |
| Order total | totalAmount | total, grandTotal |
| Order status | status | state, orderStatus |
| Delivery address | shippingAddress | address (reserved for User only) |

Product categories are standardised as: Pens & Pencils, Paper & Notebooks,
Files & Folders, Art Supplies, Office Supplies, School Kits.

### 7.3 Documentation Standards

Every file begins with a header block:

    /**
     * File: product-service.php
     * Module: Product Management
     * Description: Handles retrieval and management of stationery products.
     * Author: [Name]
     * Created: 23/09/2026
     */

Every function carries a docblock stating purpose, parameters and return:

    /**
     * Searches the catalogue by product name or category.
     * @param string $keyword  Search term entered by the customer
     * @param string $category Category filter, or "all"
     * @return array Matching products; empty array if none found
     */
    function searchProducts($keyword, $category) { }

Inline comments explain why, not what. Commented-out code is never
committed, Git history preserves it.

### 7.4 Function Modularity

| Rule | Detail |
|------|--------|
| Single responsibility | One function, one task. If describing it needs "and", split it. |
| Length | Maximum 30 lines per function |
| Parameters | Maximum 4; pass an array or object beyond that |
| No duplication | Shared logic lives in /src/utils/ |
| Separation of concerns | Data-access functions are separate from display functions |
| No logic in markup | Event handlers call named functions; no logic inside onclick |
| Validation | Every function taking user input validates it before use |

---

## 8. Folder Structure

    /src
      /config          → db-config.php (database connection)
      /css             → stylesheets
      /js              → client-side scripts
      /includes        → header.php, footer.php, shared components
      /services        → data access and business logic
      /utils           → shared helper functions
      /pages           → product-list.php, cart.php, checkout.php
      /assets          → images and icons
    /docs
      /diagrams        → use case and class diagrams
      /database        → schema and seed SQL
      /reports         → lab report PDFs
    /tests             → unit tests
    README.md
    .gitignore

---

## 9. Setup Instructions

1. Install WAMP Server and start Apache and MySQL.
2. Clone the repository into the WAMP web root:

       cd C:\wamp64\www
       git clone https://github.com/sarahryy/OLMS_G7.git

3. Open phpMyAdmin at http://localhost/phpmyadmin
4. Create a database named `olms_g7`
5. Import /docs/database/olms_g7.sql
6. Copy db-config.example.php to db-config.php in /src/config/
   and enter your local MySQL credentials.
7. Open http://localhost/OLMS_G7/src/pages/index.php
> db-config.php is listed in .gitignore and must never be committed.
> Only db-config.example.php is tracked.

---

## 10. Version Control Workflow

### 10.1 Branches

 Branch | Purpose | Rules |
|--------|---------|-------|
| main | Stable, working code only | Protected. No direct pushes. Pull request with 1 approval required. |
| feature/<name> | New functionality | Branched from main. Example:feature/product-search |
| fix/<name> | Defect correction | Branched from main. Example: fix/cart-total-calculation |
| docs/<name> | Documentation changes only | Branched from main. Example: docs/lab2-report |
