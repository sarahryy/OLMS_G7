<?php
/**
 * File: Customer.php
 * Module: User Management
 * Description: Specialized customer entity inheriting from the base User class.
 * Author: Sharifah Sofiyyah Lu'lu' binti Syed Firdaus Hilmi
 * Created: 23/10/2026
 */

require_once __DIR__ . '/User.php';

class Customer extends User {
    private int $loyaltyPoints;
    private string $registrationDate;

    public function __construct(
        string $name,
        string $email,
        string $password,
        string $phoneNumber,
        int $loyaltyPoints = 0,
        ?string $registrationDate = null,
        ?int $userId = null
    ) {
        parent::__construct($name, $email, $password, $phoneNumber, 'customer', $userId);
        $this->loyaltyPoints = $loyaltyPoints;
        $this->registrationDate = $registrationDate ?? date('Y-m-d');
    }

    public function getLoyaltyPoints(): int { return $this->loyaltyPoints; }
    public function getRegistrationDate(): string { return $this->registrationDate; }

    public function setLoyaltyPoints(int $loyaltyPoints): void { $this->loyaltyPoints = $loyaltyPoints; }
    public function setRegistrationDate(string $registrationDate): void { $this->registrationDate = $registrationDate; }
}