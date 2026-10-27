<?php
/**
 * File: manage-category.php
 * Module: Category Management
 * Description: Administrator interface for adding, editing, listing and
 *              deleting product categories. Deleting a category leaves its
 *              products in place with no category assigned.
 * Module author: Savitta
 * Layout migrated to the shared header/footer on 27/10/2026.
 */
 
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../services/category-service.php';
 
requireAdmin();
 
$errors     = [];
$successMsg = '';
 
$action = $_GET['action'] ?? 'list';
$editId = $_GET['id'] ?? null;
 
$categoryNameInput = '';
$editingCategory   = null;
 
// ---- Handle form submissions (create or update) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryNameInput = trim($_POST['category_name'] ?? '');
    $formAction        = $_POST['form_action'] ?? '';
    $catId             = $_POST['category_id'] ?? null;
 
    if ($categoryNameInput === '') {
        $errors[] = 'Category name cannot be empty.';
    }
 
    if (empty($errors)) {
        if ($formAction === 'add') {
            $newId = createCategory($categoryNameInput);
            if ($newId > 0) {
                $successMsg        = "Category added successfully (ID {$newId}).";
                $categoryNameInput = '';
            } else {
                $errors[] = 'Failed to add category. The name may already exist.';
            }
        } elseif ($formAction === 'edit' && $catId) {
            if (updateCategory($catId, $categoryNameInput)) {
                header('Location: manage-category.php?success='
                     . urlencode('Category updated successfully.'));
                exit;
            }
            $errors[] = 'Failed to update category.';
        }
    }
}
 
// ---- Handle deletion ----
if ($action === 'delete' && $editId) {
    if (deleteCategory($editId)) {
        header('Location: manage-category.php?success='
             . urlencode('Category deleted successfully.'));
        exit;
    }
    $errors[] = 'Failed to delete category.';
}
 
// ---- Load the record being edited ----
if ($action === 'edit' && $editId) {
    $editingCategory = getCategoryById($editId);
    if ($editingCategory) {
        $categoryNameInput = $editingCategory['category_name'];
    } else {
        $errors[] = 'That category could not be found.';
        $action   = 'list';
    }
}
 
// ---- Success message carried through a redirect ----
if (isset($_GET['success'])) {
    $successMsg = $_GET['success'];
}
 
$categories = getAllCategories();
 
$pageTitle = 'Manage Categories';
include __DIR__ . '/../includes/header.php';
?>
 
<h1 class="page-title">Manage Categories</h1>
<p class="page-subtitle">
    <?= count($categories) ?> categor<?= count($categories) === 1 ? 'y' : 'ies' ?> defined
</p>
 
<?php if ($successMsg !== ''): ?>
    <div class="message-success"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>
 
<?php if (!empty($errors)): ?>
    <div class="message-error">
        <ul>
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
 
<h2 class="section-title">
    <?= ($action === 'edit') ? 'Edit Category' : 'Add New Category' ?>
</h2>
 
<form method="post"
      action="manage-category.php<?= ($action === 'edit') ? '?action=edit&id=' . urlencode($editId) : '' ?>">
    <input type="hidden" name="form_action" value="<?= ($action === 'edit') ? 'edit' : 'add' ?>">
    <?php if ($action === 'edit'): ?>
        <input type="hidden" name="category_id" value="<?= htmlspecialchars($editId) ?>">
    <?php endif; ?>
 
    <div class="form-group">
        <label class="form-label" for="category_name">Category Name *</label>
        <input type="text" id="category_name" name="category_name" class="form-input"
               value="<?= htmlspecialchars($categoryNameInput) ?>" required>
    </div>
 
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">
            <?= ($action === 'edit') ? 'Update Category' : 'Add Category' ?>
        </button>
        <?php if ($action === 'edit'): ?>
            <a class="btn btn-secondary" href="manage-category.php">Cancel</a>
        <?php endif; ?>
    </div>
</form>
 
<h2 class="section-title">Existing Categories</h2>
 
<?php if (empty($categories)): ?>
 
    <div class="no-results">No categories found.</div>
 
<?php else: ?>
 
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Category Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><?= htmlspecialchars($cat['category_id']) ?></td>
                    <td><?= htmlspecialchars($cat['category_name']) ?></td>
                    <td>
                        <a class="btn btn-secondary btn-small"
                           href="manage-category.php?action=edit&id=<?= (int)$cat['category_id'] ?>">Edit</a>
                        <a class="btn btn-danger btn-small"
                           href="manage-category.php?action=delete&id=<?= (int)$cat['category_id'] ?>"
                           onclick="return confirm('Delete this category? Products in it will be left with no category.');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
 
<?php endif; ?>
 
<?php include __DIR__ . '/../includes/footer.php'; ?>
 