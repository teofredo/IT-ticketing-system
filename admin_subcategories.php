<?php
require 'db_con/db_conn.php';
session_start();

if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

// Add or delete subcategories
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    
    $stmt = $pdo->prepare("INSERT INTO subcategories (category_id, name, description) VALUES (?, ?, ?)");
    $stmt->execute([$category_id, $name, $description]);
    echo "<p class='success'>Subcategory added successfully.</p>";
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM subcategories WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    echo "<p class='success'>Subcategory deleted successfully.</p>";
}

// Fetch categories and subcategories
$categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
$subcategories = $pdo->query("
    SELECT subcategories.id, subcategories.name, subcategories.description, categories.name AS category 
    FROM subcategories 
    JOIN categories ON subcategories.category_id = categories.id
")->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Manage Subcategories</h2>
<form method="POST">
    <select name="category_id" required>
        <option value="">Select Category</option>
        <?php foreach ($categories as $category): ?>
            <option value="<?= $category['id'] ?>"><?= $category['name'] ?></option>
        <?php endforeach; ?>
    </select>
    <input type="text" name="name" placeholder="Subcategory Name" required>
    <textarea name="description" placeholder="Description"></textarea>
    <button type="submit">Add Subcategory</button>
</form>

<table>
    <tr>
        <th>ID</th>
        <th>Category</th>
        <th>Name</th>
        <th>Description</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($subcategories as $sub): ?>
    <tr>
        <td><?= $sub['id'] ?></td>
        <td><?= $sub['category'] ?></td>
        <td><?= $sub['name'] ?></td>
        <td><?= $sub['description'] ?></td>
        <td>
            <a href="?delete=<?= $sub['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
