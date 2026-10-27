<?php
/**
 * File: index.php
 * Module: Shared Presentation / System Integration
 * Description: Single entry point for StationeryMart. Directs the visitor to
 *              the correct starting page for their role, so that the system
 *              always opens on a usable screen instead of a directory listing.
 * Author: Siti Sarah Raihanah Binti Azizan
 * Created: 27/10/2026
 */
 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 
if (!isset($_SESSION['userId'])) {
    // Not signed in yet.
    header('Location: login.php');
} elseif (($_SESSION['role'] ?? '') === 'admin') {
    // Administrators start at the product catalogue they maintain.
    header('Location: product-list.php');
} else {
    // Customers start at the catalogue they shop from.
    header('Location: product-list.php');
}
exit;
 