<?php
require 'db_con/db_conn.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket_id = $_POST['ticket_id'];
    $status_id = $_POST['status_id'];

    $stmt = $pdo->prepare("UPDATE tickets SET status_id = ? WHERE id = ?");
    $stmt->execute([$status_id, $ticket_id]);

    echo "Ticket status updated successfully!";
    header("Location: manage_tickets.php");
    exit();
}

// Fetch statuses
$statusStmt = $pdo->query("SELECT id, name FROM statuses");
$statuses = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Update Ticket Status</h2>
<form method="POST">
    <input type="hidden" name="ticket_id" value="<?php echo $_GET['id']; ?>">

    <label for="status_id">Status:</label>
    <select name="status_id" required>
        <?php foreach ($statuses as $status): ?>
            <option value="<?php echo $status['id']; ?>"><?php echo $status['name']; ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Update Status</button>
</form>
