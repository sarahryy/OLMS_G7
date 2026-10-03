<?php
/**
 * File: header.php
 * Module: Shared Presentation
 * Description: Shared page header for all StationeryMart (OLMS) pages.
 *              Outputs the document head, stylesheet link and navigation bar.
 *              Set $pageTitle before including this file to set the page title.
 * Author: Siti Sarah Raihanah Binti Azizan
 * Created: 02/10/2026
 * Last Modified: 02/10/2026
 */


$pageTitle = isset($pageTitle) ? $pageTitle : 'StationeryMart';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> | StationeryMart</title>
    <link rel="stylesheet" href="../css/main-style.css">
</head>
<body>


<nav class="nav-bar">
    <span class="nav-brand">StationeryMart</span>
    <a class="nav-link" href="product-list.php">All Products</a>
    <a class="nav-link" href="search-product.php">Search</a>
    <a class="nav-link" href="add-product.php">Add Product</a>
    <a class="nav-link" href="edit-product.php">Edit Product</a>
    <a class="nav-link" href="delete-product.php">Delete Product</a>
</nav>


<div class="page-container">