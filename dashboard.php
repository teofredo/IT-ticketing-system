    <?php
    require 'db_con/db_conn.php';
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
    // Fetch categories and priorities for dropdowns
        $categories = $pdo->query("SELECT id, name FROM categories")->fetchAll(PDO::FETCH_ASSOC);
        $priorities = $pdo->query("SELECT id, name FROM priorities")->fetchAll(PDO::FETCH_ASSOC);

    // Fetch tickets requested by the logged-in user
    $stmt = $pdo->prepare("
        SELECT tickets.id, tickets.subject, tickets.description, tickets.created_at, 
            categories.name AS category, subcategories.name AS subcategory,  priorities.name AS priority, 
            statuses.name AS status, 
            assignee.name AS assigned_to,
            tickets.updated_at,
            tickets.status_id,
            tickets.assigned_to AS assigned_id
        FROM tickets
        JOIN categories ON tickets.category_id = categories.id
        JOIN subcategories ON tickets.subcategory_id = subcategories.id
        JOIN priorities ON tickets.priority_id = priorities.id
        JOIN statuses ON tickets.status_id = statuses.id
        LEFT JOIN users AS assignee ON tickets.assigned_to = assignee.id
        WHERE tickets.created_by = ?
        ORDER BY tickets.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>My Tickets - IT Ticketing System</title>
         <!-- SweetAlert & CSS -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

        <link rel="stylesheet" href="assets/css/style.css">
    </head>
    <body>

    <div class="header">
        <h1> IT Ticketing System</h1>
    </div>

    <div class="dashboard-container">
        <h2>Welcome to your dashboard <span style="font-style: italic; font-weight: bolder;"><?php echo $_SESSION['name']; ?>!</span></h2>

        <a href="logout.php" class="btn btn-logout">Logout</a>
        <button class="btn" onclick="openModal()">Create Ticket</button>
        <?php
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' || $_SESSION['role'] === 'technician') {
                echo '<a href="admin_dashboard.php" class="btn">Admin Dashboard</a>';
            }
        ?>

        <h2>My Ticket Queue</h2>

        <table>
            <tr>
                <th>ID</th>
                <th>Subject</th>
                <th>Description</th>
                <th>Category</th>
                <th>Subcategory</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Assigned To</th>
                <th>Created At</th>
                <th>Updated At</th>
                <th>Action</th>
            </tr>

            <?php if (count($tickets) > 0): ?>
                <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td><?php echo $ticket['id']; ?></td>
                        <td><?php echo $ticket['subject']; ?></td>
                        <td><?php echo $ticket['description']; ?></td>
                        <td><?php echo $ticket['category']; ?></td>
                        <td><?php echo $ticket['subcategory']; ?></td>
                        <td><?php echo $ticket['priority']; ?></td>
                        <td>
                            <span class="status-<?php echo strtolower(str_replace(' ', '-', $ticket['status'])); ?>">
                                <?php echo $ticket['status']; ?>
                            </span>
                        </td>
                        <td><?php echo $ticket['assigned_to'] ?? 'Unassigned'; ?></td>
                        <td><?php echo $ticket['created_at']; ?></td>
                        <td><?php echo $ticket['updated_at']; ?></td>
                        <td><?php 
                        
                        $stmt = $pdo->prepare('SELECT * FROM closed_tickets WHERE ticket_id = ?');
                        $stmt->execute([$ticket['id']]);
                        $closed_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        $stmt = $pdo->prepare('SELECT * FROM technician_ratings WHERE ticket_id = ?');
                        $stmt->execute([$ticket['id']]);
                        $rated = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if(!$closed_tickets && $ticket['status_id']==3){ ?>
                            <button class="btn" data-ticket-id="<?= $ticket['id'] ?>" onclick="openRating()">Close</button>
                            <?php } ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">No tickets found.</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
    <div id="ticketModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Create New Ticket</h2>
            <form method="POST" id="createTicketForm" onsubmit="disableForm()">
                <input type="text" name="subject" placeholder="Subject" required>
                <textarea name="description" placeholder="Description" rows="5" required></textarea>
                
                <!-- Category Dropdown -->
                <select name="category_id" id="categorySelect"required>
                    <option value="" disabled selected>Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>">
                            <?php echo $category['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <!-- SubCategory -->
                <select name="subcategory_id" id="subcategorySelect" required>
                    <option value="">Select Subcategory</option>
                </select>

                <!-- Priority Dropdown -->
                <select name="priority_id" required>
                    <option value="" disabled selected>Select Priority</option>
                    <?php foreach ($priorities as $priority): ?>
                        <option value="<?php echo $priority['id']; ?>">
                            <?php echo $priority['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" id="submitBtn" class="btn submit-btn">Create Ticket
                <span id="spinner" class="spinner"></span>
                </button>
            </form>
        </div>
    </div>

    <!-- modal for rating and closure -->
    <div id="closeTicket" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModalRating()">&times;</span>
            <h2>Rate Your Technician</h2>
            <form action="close_ticket.php" method="POST" id="createTicketForm" onsubmit="return validateForm()">
                <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                <input type="hidden" name="technician_id" value="<?= $ticket['assigned_id'] ?>">
                <input type="hidden" name="rated_by" value="<?= $_SESSION['user_id'] ?>">
                <input type="hidden" id="rating_value" name="rating"  required>

                <label for="rating">Rating:</label>
                <div class="rating-meter">
                    <div class="rating" onclick="selectRating(this, 'poor')" data-rating="poor">
                        <span class="emoji">😡</span> POOR
                    </div>
                    <div class="rating" onclick="selectRating(this, 'excellent')" data-rating="excellent">
                        <span class="emoji">😲</span> EXCELLENT
                    </div>
                </div>

                <label for="comment">Leave a comment (optional):</label>
                <textarea name="comment" placeholder="Share your experience..."></textarea>

                <button type="submit">Submit Rating</button>
            </form>
        </div>
    </div>


<script>
 function selectRating(element, rating) {
        // Clear previous selections
        document.querySelectorAll('.rating').forEach((ratingElement) => {
            ratingElement.classList.remove('selected');
        });

        // Add "selected" to the clicked element
        element.classList.add('selected');

        // Set the hidden input value
        document.getElementById('rating_value').value = rating;


    }
    function validateForm() {
        const selectedRating = document.getElementById('rating_value').value;
        if (!selectedRating) {
            alert("Please select a rating before submitting.");
            return false;
        }
        return true;
    }

    function disableForm() {
    const form = document.getElementById('createTicketForm');
    const submitButton = document.getElementById('submitBtn');
    const spinner = document.getElementById('spinner');

    // Disable the submit button and show spinner
    submitButton.disabled = true;
    spinner.style.display = 'inline-block';
    submitButton.innerText = "Submitting...";

}

    // Modal handling
    function openModal() {
        document.getElementById('ticketModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('ticketModal').style.display = 'none';
    }

    
    function openRating() {
        document.getElementById('closeTicket').style.display = 'block';
    }

    function closeModalRating() {
        document.getElementById('closeTicket').style.display = 'none';
    }

    // Close the modal if clicked outside
    window.onclick = function(event) {
        const modal = document.getElementById('ticketModal');
        const modalRating = document.getElementById('closeRating');
        if (event.target === modal || event.target ===modalRating) {
            modal.style.display = 'none';
        }
    }
</script>         
<script>
    const form = document.getElementById('createTicketForm');

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(form);

        fetch('create_ticket.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            Swal.fire({
                icon: result.success ? 'success' : 'error',
                title: result.success ? 'Ticket Created!' : 'Failed to Create Ticket',
                text: result.message,
            }).then(() => {
                if (result.success) {
                    location.reload();
                }
            });
        });
    });

    // Dynamic subcategory loading
    const categorySelect = document.getElementById('categorySelect');
    const subcategorySelect = document.getElementById('subcategorySelect');

    categorySelect.addEventListener('change', function() {
        const categoryId = this.value;

        if (categoryId) {
            fetch(`subcategories.php?category_id=${categoryId}`)
                .then(response => response.json())
                .then(data => {
                    subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
                    data.forEach(subcategory => {
                        subcategorySelect.innerHTML += `<option value="${subcategory.id}">${subcategory.name}</option>`;
                    });
                });
        } else {
            subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
        }
    });
</script>   
    </body>
    </html>
