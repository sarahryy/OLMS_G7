<?php
/**
 * File: search-product.php
 * Module: Product Management
 * Description: Allows the user to search the catalogue by product name and
 *              filter by category. Matching products are shown using the
 *              shared display function; a clear message is displayed when
 *              no product matches the search criteria.
 * Author: Siti Sarah Raihanah Binti Azizan
 * Created: 03/10/2026
 */

require_once __DIR__ . '/../services/product-service.php';
require_once __DIR__ . '/../includes/product-display.php';

// Collect search criteria 
$keyword    = trim($_GET['keyword']  ?? '');
$categoryId = trim($_GET['category'] ?? 'all');
$hasSearched = isset($_GET['keyword']) || isset($_GET['category']);

$categoryNames = getCategoryNames();
$results       = [];

if ($hasSearched) {
    $results = searchProducts($keyword, $categoryId);
}

$pageTitle = 'Search Products';
include __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">Search Products</h1>
<p class="page-subtitle">Search by product name, or filter by category</p>

//form
<form method="get" action="search-product.php">
    <div class="search-bar">
        <input type="text"
               class="form-input"
               name="keyword"
               placeholder="Search by product name..."
               value="<?= htmlspecialchars($keyword) ?>">

        <select class="form-select" name="category">
            <option value="all">All Categories</option>
            <?php foreach ($categoryNames as $id => $name): ?>
                <option value="<?= (int)$id ?>"
                    <?= ((string)$categoryId === (string)$id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($name) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn btn-primary">Search</button>
    </div>
</form>


<?php if ($hasSearched): ?>

    <?php if (!empty($results)): ?>
        <p class="page-subtitle">
            <?= count($results) ?> product<?= count($results) === 1 ? '' : 's' ?> found.
        </p>
    <?php endif; ?>

    <?php
    displayProductTable(
        $results,
        $categoryNames,
        'No products found matching your search.'
    );
    ?>

<?php else: ?>

    <div class="subtittle">
        Enter a product name or choose a category, then click Search.
    </div>

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>