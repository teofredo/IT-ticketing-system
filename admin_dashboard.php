<?php
require 'db_con/db_conn.php';
session_start();

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'technician') {
    header('Location: dashboard.php');
    exit();
}

// Fetch summary data
$totalTickets = $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
$openTickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status_id = 1")->fetchColumn();
$closedTickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status_id = 3")->fetchColumn();
$pendingTickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status_id = 2")->fetchColumn();
// Fetch tickets handled per technician
$stmtTechTickets = $pdo->query("
    SELECT users.name AS technician, COUNT(tickets.id) AS ticket_count
    FROM users
    LEFT JOIN tickets ON users.id = tickets.assigned_to
    WHERE users.role = 'technician' OR users.role = 'admin'
    GROUP BY users.id
");
$techTickets = $stmtTechTickets->fetchAll(PDO::FETCH_ASSOC);
// Fetch all tickets with related data
$stmt = $pdo->prepare("
    SELECT tickets.id, tickets.subject, tickets.status_id, tickets.description, tickets.created_at, 
           categories.name AS category, priorities.name AS priority, 
           statuses.name AS status, 
           creator.name AS created_by, 
           assignee.name AS assigned_to,
           assignee.id AS assigned_id
    FROM tickets
    JOIN categories ON tickets.category_id = categories.id
    JOIN priorities ON tickets.priority_id = priorities.id
    JOIN statuses ON tickets.status_id = statuses.id
    JOIN users AS creator ON tickets.created_by = creator.id
    LEFT JOIN users AS assignee ON tickets.assigned_to = assignee.id
    ORDER BY tickets.created_at DESC
");
$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch (both admins and technicians)
    $stmtUsers = $pdo->query("
    SELECT id, name, role 
    FROM users
    WHERE role IN ('admin', 'technician')
    ORDER BY role, name
    ");
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

// Fetch (both admins and technicians)
    $stmtPriorities = $pdo->query("
    SELECT priorities.id, name, level, count(tickets.id) AS level_count 
    FROM priorities 
    LEFT JOIN tickets ON tickets.priority_id = priorities.id 
    GROUP BY priorities.id 
    ORDER BY priorities.id DESC
    ");
    $priorities = $stmtPriorities->fetchAll(PDO::FETCH_ASSOC);    
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - IT Ticketing System</title>

    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Bootstrap CSS -->
<!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> -->

<!-- Bootstrap JS (for modals) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="assets/css/admin_style.css">
</head>
<body>

<div class="sidebar collapse">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="admin_users.php"><i class="fas fa-users"></i> Manage Users</a>
    <a href="admin_categories.php"><i class="fas fa-tags"></i> Manage Categories</a>
    <a href="#"><i class="fas fa-ticket-alt"></i> Reports</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main-content">
    <div class="header">
        <h1>Admin Dashboard</h1>
    </div>

    <h2>Welcome, <?= $_SESSION['name'] ?>!</h2>

    <div class="dashboard-cards">
        <div class="card"><h3>Total Tickets</h3><p><?= $totalTickets ?></p></div>
        <div class="card"><h3>Open Tickets</h3><p><?= $openTickets ?></p></div>
        <div class="card"><h3>Pending Tickets</h3><p><?= $pendingTickets ?></p></div>
        <div class="card"><h3>Closed Tickets</h3><p><?= $closedTickets ?></p></div>
    </div>

    <h2>Tickets Level Count</h2>
    <div class="dashboard-cards">       
            <?php foreach ($priorities as $prio) : ?> 
                <div class="card">
                    <div class="card"><h3><?= $prio['name'] ?></h3><p><?= $prio['level_count'] ?></p></div>          
                </div>
            <?php endforeach; ?>        
    </div>

    <h2>Tickets Handled by Technicians</h2>
    <div class="dashboard-cards">
        <?php foreach ($techTickets as $tech) : ?>
            <div class="card">
                <h3><?= $tech['technician'] ?></h3>
                <p><?= $tech['ticket_count'] ?> Tickets Handled</p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="table-responsive">
        
    <h2>All Ticket Requests</h2>
    <table class="table">
        <tr class="table-header">
            <th>ID</th>
            <th>Subject</th>
            <th>Description</th>
            <th>Category</th>
            <th>Priority</th>
            <th>Status</th>
            <th>Created By</th>
            <th>Assigned To</th>
            <th class="actions">Actions</th>
        </tr>

        <?php foreach ($tickets as $ticket): ?>
            <tr>
                <td><?= $ticket['id'] ?></td>
                <td><?= $ticket['subject'] ?></td>
                <td><?= $ticket['description'] ?></td>
                <td><?= $ticket['category'] ?></td>
                <td><?= $ticket['priority'] ?></td>
                <td><?= $ticket['status'] ?></td>
                <td><?= $ticket['created_by'] ?></td>
                <td><?= $ticket['assigned_to'] ?? 'Unassigned' ?></td>
                <td class="actions">
                    <?php if ($_SESSION['role'] === 'admin') : ?>
                        <!-- Admin can always assign, accept, and update -->
                        <button class="btn btn-assign" data-ticket-id="<?= $ticket['id'] ?>">Assign</button>
                        <button class="btn btn-accept" data-ticket-id="<?= $ticket['id'] ?>">Accept</button>
                        <button class="btn btn-update" data-ticket-id="<?= $ticket['id'] ?>">Update</button>

                    <?php elseif ($_SESSION['role'] === 'technician') : ?>
                        <!-- Technician role -->
                        <button class="btn btn-assign" disabled>Assign</button>
                        
                        <!-- Accept only if no technician is assigned -->
                        <button 
                            class="btn btn-accept" 
                            data-ticket-id="<?= $ticket['id'] ?>" 
                            <?= $ticket['assigned_to'] ? 'disabled' : '' ?>
                        >
                            Accept
                        </button>

                        <!-- Update only if the ticket is assigned to the logged-in technician -->
                        <button 
                            class="btn btn-update" 
                            data-ticket-id="<?= $ticket['id'] ?>" 
                            <?= ($ticket['assigned_id'] === $_SESSION['user_id'] && $ticket['status_id']==2) ? '' : 'disabled' ?>
                        >
                            Update
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>
<!-- Assign Ticket Modal -->
<div class="modal fade" id="assignTicketModal" tabindex="-1" aria-labelledby="assignTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignTicketModalLabel">Assign Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
            </div>
            <div class="modal-body">
            <form id="assignTicketForm">
                <input type="hidden" id="assignTicketId">
                <div class="mb-3">
                    <label for="assignee" class="form-label">Select Assignee</label>
                    <select id="assignee" class="form-select">
                            <option value="">Select a User</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>">
                                    <?= $user['name'] ?> (<?= ucfirst($user['role']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                </div>
                <button type="button" class="btn btn-primary" onclick="submitAssign()">Assign</button>
            </form>
            </div>
        </div>
    </div>
</div>

<!-- Accept Ticket Modal -->
<div class="modal fade" id="acceptTicketModal" tabindex="-1" aria-labelledby="acceptTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="acceptTicketModalLabel">Accept Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to accept this ticket?</p>
                <button type="button" class="btn btn-success" onclick="submitAccept()">Yes, Accept</button>
            </div>
        </div>
    </div>
</div>

<!-- Update Ticket Modal -->
<div class="modal fade" id="updateTicketModal" tabindex="-1" aria-labelledby="updateTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateTicketModalLabel">Update Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
            </div>
            <div class="modal-body">
                <form id="updateTicketForm">
                    <input type="hidden" id="updateTicketId">
                    <div class="mb-3">
                        <label for="status" class="form-label">Update Status</label>
                        <select id="status" class="form-select">
                            <?php 
                            if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin') : ?>
                            <option value="2">In Progress</option>
                            <option value="3">Resolved</option>
                            <option value="4">Closed</option>
                            <?php
    	                    elseif(isset($_SESSION['user_id']) && $_SESSION['user_id'] === $ticket['assigned_id']) : ?>
                            <option value="3">Resolved</option>
                            <?php
                            endif;
                            ?>

                        </select>
                    </div>
                    <button type="button" class="btn btn-info" onclick="submitUpdate()">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
   // Handle Assign Ticket action
function submitAssign() {
    const ticketId = document.getElementById('assignTicketId').value;
    const assignee = document.getElementById('assignee').value;

    fetch('admin_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'assign', ticketId, assignee })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire('Assigned!', data.message, 'success');
            document.getElementById('assignTicketForm').reset();
            bootstrap.Modal.getInstance(document.getElementById('assignTicketModal')).hide();
            location.reload(); // Refresh the page to show updated data
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    });
}

// Handle Accept Ticket action
function submitAccept() {
    const ticketId = document.getElementById('assignTicketId').value;
    console.log(ticketId);
    fetch('admin_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'accept', ticketId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire('Accepted!', data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('acceptTicketModal')).hide();
            location.reload();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    });
}

// Handle Update Ticket action
function submitUpdate() {
    const ticketId = document.getElementById('updateTicketId').value;
    const status = document.getElementById('status').value;

    fetch('admin_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'update', ticketId, status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire('Updated!', data.message, 'info');
            bootstrap.Modal.getInstance(document.getElementById('updateTicketModal')).hide();
            location.reload();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    });
}


document.addEventListener("DOMContentLoaded", function () {
    // Show modal function
    function showModal(modalId) {
        const modal = new bootstrap.Modal(document.getElementById(modalId));
        modal.show();
    }

    // Handle Assign button clicks
    document.querySelectorAll('.btn-assign').forEach((button) => {
        button.addEventListener('click', function () {
            const ticketId = this.getAttribute('data-ticket-id');
            document.getElementById('assignTicketId').value = ticketId;
            showModal('assignTicketModal');
        });
    });

    // Handle Accept button clicks
    document.querySelectorAll('.btn-accept').forEach((button) => {
        button.addEventListener('click', function () {
            const ticketId = this.getAttribute('data-ticket-id');
            document.getElementById('assignTicketId').value = ticketId;
            showModal('acceptTicketModal');
        });
    });

    // Handle Update button clicks
    document.querySelectorAll('.btn-update').forEach((button) => {
        button.addEventListener('click', function () {
            const ticketId = this.getAttribute('data-ticket-id');
            document.getElementById('updateTicketId').value = ticketId;
            showModal('updateTicketModal');
        });
    });
});


</script>

</body>
</html>
