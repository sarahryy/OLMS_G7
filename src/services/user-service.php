<?php
/**
 * File: user-service.php
 * Module: User Management
 * Description: Handles authentication, account registration, and user data queries.
 * Author: Sharifah Sofiyyah Lu'lu' binti Syed Firdaus Hilmi
 * Created: 23/10/2026
 */

require_once __DIR__ . '/../config/db-config.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Customer.php';
require_once __DIR__ . '/../classes/Admin.php';

/**
 * Registers a new customer and hashes their password.
 */
function registerCustomer(Customer $customer): bool {
    $conn = getConnection();

    // 1. Check duplicate email
    $checkSql = "SELECT user_id FROM users WHERE email = ? LIMIT 1";
    $checkStmt = $conn->prepare($checkSql);
    $email = $customer->getEmail();
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        $checkStmt->close();
        $conn->close();
        return false;
    }
    $checkStmt->close();

    // 2. Insert into users table
    $hashedPassword = password_hash($customer->getPassword(), PASSWORD_BCRYPT);
    $sqlUser = "INSERT INTO users (name, email, password, phone_number, role) VALUES (?, ?, ?, ?, 'customer')";
    $stmtUser = $conn->prepare($sqlUser);
    $name = $customer->getName();
    $phone = $customer->getPhoneNumber();
    $stmtUser->bind_param("ssss", $name, $email, $hashedPassword, $phone);

    if (!$stmtUser->execute()) {
        $stmtUser->close();
        $conn->close();
        return false;
    }

    $newUserId = $conn->insert_id;
    $stmtUser->close();

    // 3. Insert into customers table
    $sqlCust = "INSERT INTO customers (customer_id, loyalty_points, registration_date) VALUES (?, ?, ?)";
    $stmtCust = $conn->prepare($sqlCust);
    $points = $customer->getLoyaltyPoints();
    $regDate = $customer->getRegistrationDate();
    $stmtCust->bind_param("iis", $newUserId, $points, $regDate);
    $success = $stmtCust->execute();

    $stmtCust->close();
    $conn->close();
    return $success;
}

/**
 * Authenticates user credentials and verifies password hash.
 */
function loginUser(string $email, string $password): ?array {
    $conn = getConnection();
    $sql = "SELECT user_id, name, email, password, role FROM users WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $stmt->close();
            $conn->close();
            return [
                'userId' => (int)$row['user_id'],
                'name'   => $row['name'],
                'email'  => $row['email'],
                'role'   => $row['role']
            ];
        }
    }

    $stmt->close();
    $conn->close();
    return null;
}

/**
 * Fetches user profile data by ID.
 */

function getUserById(int $userId): ?array {
    $conn = getConnection();

    $sql = "SELECT
                u.user_id,
                u.name,
                u.email,
                u.phone_number,
                u.role,
                c.loyalty_points
            FROM users u
            LEFT JOIN customers c ON u.user_id = c.user_id
            WHERE u.user_id = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $data = $stmt->get_result()->fetch_assoc();

    $stmt->close();
    $conn->close();

    return $data ?: null;
}

/**
 * Updates editable profile fields for a user.
 */
function updateUserProfile(int $userId, string $name, string $phone): bool {
    $conn = getConnection();
    $sql = "UPDATE users SET name = ?, phone_number = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $name, $phone, $userId);
    $success = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $success;
}

/**
 * Retrieves all registered users for administrative view.
 */
function getAllUsers(): array {
    $conn = getConnection();
    $sql = "SELECT user_id, name, email, phone_number, role, created_at FROM users ORDER BY user_id ASC";
    $result = $conn->query($sql);
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $conn->close();
    return $users;
}

/**
 * Updates a user's authorization role.
 */
function updateUserRole(int $userId, string $role): bool {
    $conn = getConnection();
    $sql = "UPDATE users SET role = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $role, $userId);
    $success = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $success;
}

/**
 * Deletes a user account.
 */
function deleteUser(int $userId): bool {
    $conn = getConnection();
    $sql = "DELETE FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $success = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $success;
}