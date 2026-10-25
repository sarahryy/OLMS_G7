<?php
/**
 * File: User.php
 * Module: User Management
 * Description: Base class encapsulating core user attributes and accessors.
 * Author: Sharifah Sofiyyah Lu'lu' binti Syed Firdaus Hilmi
 * Created: 23/10/2026
 */

class User {
    protected ?int $userId;
    protected string $name;
    protected string $email;
    protected string $password;
    protected string $phoneNumber;
    protected string $role;

    public function __construct(
        string $name,
        string $email,
        string $password,
        string $phoneNumber,
        string $role = 'customer',
        ?int $userId = null
    ) {
        $this->userId = $userId;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->phoneNumber = $phoneNumber;
        $this->role = $role;
    }

    public function getUserId(): ?int { return $this->userId; }
    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }
    public function getPassword(): string { return $this->password; }
    public function getPhoneNumber(): string { return $this->phoneNumber; }
    public function getRole(): string { return $this->role; }

    public function setUserId(?int $userId): void { $this->userId = $userId; }
    public function setName(string $name): void { $this->name = $name; }
    public function setEmail(string $email): void { $this->email = $email; }
    public function setPassword(string $password): void { $this->password = $password; }
    public function setPhoneNumber(string $phoneNumber): void { $this->phoneNumber = $phoneNumber; }
    public function setRole(string $role): void { $this->role = $role; }
}