<?php
require 'db_con/db_conn.php';
session_start();

// Ensure admin access
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

// Handle user deletion
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    echo "<p class='success'>User deleted successfully.</p>";
}

// Fetch all users
// Fetch all users with username included
$stmt = $pdo->query("SELECT users.id, users.name, users.username, users.email, users.role, departments.name AS department 
                     FROM users 
                     LEFT JOIN departments ON users.department_id = departments.id");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>User Management</h2>
<a href="admin_add_user.php" class="btn">Add New User</a>

<table>
    <tr>
        <th>Name</th>
        <th>Username</th>
        <th>Email</th>
        <th>Role</th>
        <th>Department</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($users as $user) : ?>
    <tr>
        <td><?= htmlspecialchars($user['name']) ?></td>
        <td><?= htmlspecialchars($user['username']) ?></td>
        <td><?= htmlspecialchars($user['email']) ?></td>
        <td><?= htmlspecialchars($user['role']) ?></td>
        <td><?= $user['department'] ?: 'N/A' ?></td>
        <td>
            <a href="admin_edit_user.php?id=<?= $user['id'] ?>" class="btn">Edit</a>
            <a href="?delete=<?= $user['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
