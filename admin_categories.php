<?php
require 'db_con/db_conn.php';
session_start();

if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

// Add new category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
    $stmt->execute([$name]);
    echo "<p class='success'>Category added successfully.</p>";
}

// Fetch categories
$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Manage Categories</h2>
<form method="POST">
    <input type="text" name="name" placeholder="Category Name" required>
    <button type="submit">Add Category</button>
</form>

<table>
    <tr>
        <th>ID</th>
        <th>Name</th>
    </tr>
    <?php foreach ($categories as $category) : ?>
    <tr>
        <td><?= $category['id'] ?></td>
        <td><?= $category['name'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>
