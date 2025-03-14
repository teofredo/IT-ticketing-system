<?php
require 'db_con/db_conn.php';
session_start();

// Fetch departments for the dropdown
$stmt = $pdo->query("SELECT id, name FROM departments");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $department_id = $_POST['department_id'];

    // Check if the username already exists
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $checkStmt->execute([$username]);
    if ($checkStmt->fetch()) {
        echo "<p class='error'>Username already exists. Please choose a different one.</p>";
    } else {
        // Insert the new user with the username and department
        $stmt = $pdo->prepare("INSERT INTO users (name, username, email, password, role, department_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $username, $email, $password, $role, $department_id]);
        echo "<p class='success'>User added successfully.</p>";
    }
}
?>

<h2>Add New User</h2>
<form method="POST">
    <input type="text" name="name" placeholder="Full Name" required>
    <input type="text" name="username" placeholder="Username" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    
    <select name="role">
        <option value="employee">Employee</option>
        <option value="technician">Technician</option>
        <option value="admin">Admin</option>
    </select>

    <select name="department_id" required>
        <option value="" disabled selected>Select Department</option>
        <?php foreach ($departments as $department) : ?>
            <option value="<?= $department['id'] ?>"><?= $department['name'] ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Add User</button>
</form>
