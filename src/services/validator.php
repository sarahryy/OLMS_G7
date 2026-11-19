<?php
// File: /src/services/validator.php

class Validator {
    /**
     * Data Sanitization: Strips whitespace, tags, and hidden null-byte characters.
     */
    public static function sanitizeString(?string $data): string {
        if ($data === null) {
            return '';
        }
        $clean = str_replace("\0", '', $data); // Remove null bytes
        return trim(strip_tags($clean));
    }

    /**
     * Email Validation: Trims, checks RFC format, enforces length boundary (<= 150).
     */
    public static function validateEmail(?string $email): ?string {
        if ($email === null) {
            return null;
        }
        $clean = trim($email);
        if (strlen($clean) === 0 || strlen($clean) > 150) {
            return null;
        }
        if (!filter_var($clean, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        return strtolower($clean);
    }

    /**
     * Phone Number Validation: Regex check for Malaysian/international format (9-15 digits).
     */
    public static function validatePhone(?string $phone): ?string {
        if ($phone === null) {
            return null;
        }
        $clean = trim($phone);
        // Allows optional +, digits, spaces, and hyphens; 9 to 15 digits total
        if (!preg_match('/^[0-9+\-\s]{9,15}$/', $clean)) {
            return null;
        }
        return $clean;
    }

    /**
     * Integer Assertion: Ensures positive numbers within safe integer range.
     */
    public static function validatePositiveInt($value, int $min = 1, int $max = 2147483647): ?int {
        $validated = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => $min, 'max_range' => $max]
        ]);
        return ($validated === false) ? null : $validated;
    }

    /**
     * Password Assertion: Minimum 8 characters, maximum 72 (bcrypt truncation limit).
     */
    public static function validatePassword(?string $password): bool {
        if ($password === null) {
            return false;
        }
        $len = strlen($password);
        return ($len >= 8 && $len <= 72);
    }

    /**
     * Output Pathway Sanitization: Escapes dangerous HTML entities to prevent Stored XSS.
     */
    public static function escapeHtml(?string $data): string {
        return htmlspecialchars($data ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}