
<?php
/**
 * File: UserTest.php
 * Module: Authentication Testing
 * Description: Tests user classes and user-service functions.
 */

require_once __DIR__ . '/TestRunner.php';

require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/classes/Customer.php';
require_once __DIR__ . '/../src/classes/Admin.php';
require_once __DIR__ . '/../src/services/user-service.php';

echo "--- USER AND AUTHENTICATION TESTS ---" . PHP_EOL;

// Check user classes
TestRunner::assertTrue(
    class_exists('User'),
    'User class exists'
);

TestRunner::assertTrue(
    class_exists('Customer'),
    'Customer class exists'
);

TestRunner::assertTrue(
    class_exists('Admin'),
    'Admin class exists'
);

// Check user-service functions
TestRunner::assertTrue(
    function_exists('registerCustomer'),
    'registerCustomer function exists'
);

TestRunner::assertTrue(
    function_exists('loginUser'),
    'loginUser function exists'
);

TestRunner::assertTrue(
    function_exists('getUserById'),
    'getUserById function exists'
);

// Invalid login test (requires database connection)
TestRunner::assertNull(
    loginUser('', ''),
    'Login rejects empty credentials'
);

// Non-existing user test (requires database connection)
TestRunner::assertNull(
    getUserById(-1),
    'Invalid user ID returns null'
);