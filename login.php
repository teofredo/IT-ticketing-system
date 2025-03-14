<?php
session_start();
require 'db_con/db_conn.php';

// login.php - User login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];  // Get the username
    $password = $_POST['password'];

    // Query the database using the username
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if the user exists and the password is correct
    if ($user && password_verify($password, $user['password'])) {
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        header('Location: dashboard.php');
        exit();
    } else {
        $error = "Invalid username or password.";
    }

    
    
}
$success_reg = (isset($_SESSION['success_reg'])) ? $_SESSION['success'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Ticketing System</title>
    <link rel="stylesheet" href="assets/css/login_style.css">
   
</head>
<body>
<div class="bg-image-container">
<h1 class="welcome">Welcome to the IT Ticketing System</h1>

<div class="container">
    <div class="card" id="flipCard">

        <!-- Login Form -->
        <div class="card-front">
            <h2>Login</h2>
            <?php if (isset($error)) : ?>
                <p style="color: red;"><?php echo $error; ?></p>
            <?php endif; ?>
            <?php if (isset($success_reg)) : ?>
                <p style="color: green;"><?php echo $success_reg; ?></p>
            <?php endif; ?>

            <form method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>

            <p>Forgot your password? <a href="forget_password.php">Reset it</a></p>
            <p>Don't have an account? <a href="#" id="showRegister">Create Account</a></p>
        </div>

        <!-- Registration Form -->
        <div class="card-back">
            <h2>Create Account</h2>
            <form action="register.php" method="POST">
                <input type="text" name="name" placeholder="Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="text" name="role" value="employee" hidden>
                <select name="department_id">
                    <option value="">Select Department</option>
                    <?php
                    require 'db_con/db_conn.php';
                    $stmt = $pdo->query("SELECT id, name FROM departments");
                    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($departments as $department) {
                        echo "<option value='{$department['id']}'>{$department['name']}</option>";
                    }
                    ?>
                </select>

                <button type="submit">Register</button>
                <p>Already have an account? <a href="#" id="showLogin">Login</a></p>
            </form>
        </div>

    </div>
</div>
</div>


<script>
    const card = document.getElementById('flipCard');
    const showRegister = document.getElementById('showRegister');
    const showLogin = document.getElementById('showLogin');

    showRegister.addEventListener('click', () => {
        card.classList.add('flipped');
    });

    showLogin.addEventListener('click', () => {
        card.classList.remove('flipped');
    });
</script>

</body>
</html>
