<?php
/**
 * File: auth-guard.php
 * Module: Shared Presentation / Access Control
 * Description: Reusable access-control checks used by every restricted page.
 *              Centralising the checks here means the rule is written once and
 *              applied consistently, instead of each page repeating its own
 *              session test. Call the required function at the very top of a
 *              page, before any HTML is sent.
 * Author: Siti Sarah Raihanah Binti Azizan
 * Created: 27/10/2026
 * Last Modified: 27/10/2026
 */
 
// The Cart object lives in the session, so its class must be known before
// the session is opened or the stored cart is restored unusable.
require_once __DIR__ . '/../classes/Cart.php';
require_once __DIR__ . '/../classes/CartItem.php';
 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 
/**
 * Blocks the page unless a user is logged in.
 *
 * @return void Redirects to the login page when no session exists.
 */
function requireLogin()
{
    if (!isset($_SESSION['userId'])) {
        header('Location: login.php');
        exit;
    }
}
 
/**
 * Blocks the page unless the logged-in user is an administrator.
 *
 * @return void Redirects to login, or to the catalogue for non-admins.
 */
function requireAdmin()
{
    requireLogin();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: product-list.php');
        exit;
    }
}
 
/**
 * Blocks the page unless the logged-in user is a customer.
 *
 * @return void Redirects to login, or to the catalogue for non-customers.
 */
function requireCustomer()
{
    requireLogin();
    if (($_SESSION['role'] ?? '') !== 'customer') {
        header('Location: product-list.php');
        exit;
    }
}
 