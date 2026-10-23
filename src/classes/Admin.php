<?php
/**
 * File: Admin.php
 * Module: User Management
 * Description: Administrative entity inheriting from User with elevated privileges.
 * Author: Sharifah Sofiyyah Lu'lu' binti Syed Firdaus Hilmi
 * Created: 23/10/2026
 */

require_once __DIR__ . '/User.php';

class Admin extends User {
    public function __construct(
        string $name,
        string $email,
        string $password,
        string $phoneNumber,
        ?int $userId = null
    ) {
        parent::__construct($name, $email, $password, $phoneNumber, 'admin', $userId);
    }
}