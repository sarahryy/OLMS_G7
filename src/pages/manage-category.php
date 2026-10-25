<?php
/**
 * File: manage-category.php
 * Module: Category Management
 * Description: Admin interface for adding, editing, listing, and deleting product categories.
 * Author: Savitta
 */

require_once __DIR__ . '/../services/category-service.php';

$errors = [];
$successMsg = '';

$action = $_GET['action'] ?? 'list';
$editId = $_GET['id'] ?? null;

$categoryNameInput = '';
$editingCategory = null;

// Handle Form Submissions (Create or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryNameInput = trim($_POST['category_name'] ?? '');
    $formAction = $_POST['form_action'] ?? '';
    $catId = $_POST['category_id'] ?? null;

    if ($categoryNameInput === '') {
        $errors[] = 'Category name cannot be empty.';
    }

    if (empty($errors)) {
        if ($formAction === 'add') {
            $newId = createCategory($categoryNameInput);
            if ($newId > 0) {
                $successMsg = "Category successfully added (ID: $newId).";
                $categoryNameInput = '';
            } else {
                $errors[] = 'Failed to add category. Please try again.';
            }
        } elseif ($formAction === 'edit' && $catId) {
            if (updateCategory($catId, $categoryNameInput)) {
                $successMsg = "Category successfully updated.";
                header("Location: manage-category.php?success=" . urlencode($successMsg));
                exit();
            } else {
                $errors[] = 'Failed to update category.';
            }
        }
    }
}

// Handle Deletion via GET request
if ($action === 'delete' && $editId) {
    if (deleteCategory($editId)) {
        $successMsg = "Category successfully deleted.";
        header("Location: manage-category.php?success=" . urlencode($successMsg));
        exit();
    } else {
        $errors[] = 'Failed to delete category.';
    }
}

// Handle Edit Mode Data Fetching
if ($action === 'edit' && $editId) {
    $editingCategory = getCategoryById($editId);
    if ($editingCategory) {
        $categoryNameInput = $editingCategory['category_name'];
    }
}

// Check for redirect success messages
if (isset($_GET['success'])) {
    $successMsg = $_GET['success'];
}

// Fetch all categories for the listing table
$categories = getAllCategories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Categories — StationeryMart</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 700px; margin: 40px auto; line-height: 1.5; }
        label { display: block; margin-top: 12px; font-weight: bold; }
        input[type="text"] { width: 100%; padding: 6px; box-sizing: border-box; }
        .error   { background: #fdd; padding: 10px; border: 1px solid #c00; margin-bottom: 12px; }
        .success { background: #dfd; padding: 10px; border: 1px solid #0a0; margin-bottom: 12px; }
        .card { border: 1px solid #ccc; padding: 16px; margin-bottom: 24px; background: #fafafa; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; }
        button, .btn-action { padding: 6px 12px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 14px; }
        .btn-primary { background-color: #0275d8; color: #fff; border: none; margin-top: 12px; }
        .btn-warning { background-color: #f0ad4e; color: #fff; border-radius: 3px; }
        .btn-danger  { background-color: #d9534f; color: #fff; border-radius: 3px; border: none; }
        .btn-secondary { background-color: #777; color: #fff; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>Manage Categories</h1>

    <p><a href="manage-category.php">&larr; Back to Category List</a></p>

    <?php if ($successMsg !== ''): ?>
        <div class="success"><?= htmlspecialchars($successMsg) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Add / Edit Form Card -->
    <div class="card">
        <h3><?= ($action === 'edit') ? 'Edit Category' : 'Add New Category' ?></h3>
        <form method="post" action="manage-category.php<?= ($action === 'edit') ? '?action=edit&id=' . $editId : '' ?>">
            <input type="hidden" name="form_action" value="<?= ($action === 'edit') ? 'edit' : 'add' ?>">
            <?php if ($action === 'edit'): ?>
                <input type="hidden" name="category_id" value="<?= htmlspecialchars($editId) ?>">
            <?php endif; ?>

            <label for="category_name">Category Name *</label>
            <input type="text" id="category_name" name="category_name" value="<?= htmlspecialchars($categoryNameInput) ?>" required>

            <button type="submit" class="btn-primary"><?= ($action === 'edit') ? 'Update Category' : 'Add Category' ?></button>
            <?php if ($action === 'edit'): ?>
                <a href="manage-category.php" class="btn-secondary" style="padding: 7px 12px; margin-left: 6px;">Cancel</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Category Table Listing -->
    <h3>Existing Categories</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Category Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?= htmlspecialchars($cat['category_id']) ?></td>
                        <td><?= htmlspecialchars($cat['category_name']) ?></td>
                        <td>
                            <a href="manage-category.php?action=edit&id=<?= $cat['category_id'] ?>" class="btn-action btn-warning">Edit</a>
                            <a href="manage-category.php?action=delete&id=<?= $cat['category_id'] ?>" class="btn-action btn-danger" onclick="return confirm('Are you sure you want to delete this category? Associated products will have their category set to null.');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">No categories found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
