<?php
include 'db_con/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketId = $_POST['ticket_id'];
    $technician_id = $_POST['technician_id'];
    $rated_by = $_POST['rated_by'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'] ?? null;

    // Fetch ticket details to calculate resolution time
    $stmt = $pdo->prepare("SELECT created_at,updated_at FROM tickets WHERE id = ?");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ticket) {

        $createdAt = new DateTime($ticket['created_at']);
        $closedAt = new DateTime($ticket['updated_at']);
        $resolutionTime = $closedAt->getTimestamp() - $createdAt->getTimestamp();
        $resolutionHours = round($resolutionTime / 3600, 2);

        // Update the ticket status to 'closed'
        $updateTicket = $pdo->prepare("UPDATE tickets SET status_id = 4 WHERE id = ?");
        $updateTicket->execute([$ticketId]);

        // Insert into closed tickets table
        $insertClosed = $pdo->prepare("INSERT INTO closed_tickets (ticket_id, closed_by, resolution_time) VALUES (?, ?, ?)");
        $insertClosed->execute([$ticketId, $technician_id, $resolutionHours]);

            // Insert the rating and comment
            $stmt = $pdo->prepare("INSERT INTO technician_ratings (`technician_id`, `rated_by`, `ticket_id`, `rating`, `comment`, `rated_at`) 
                                VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$technician_id, $rated_by, $ticketId, $rating, $comment]);

            if ($stmt->rowCount()) {
                echo "Rating submitted successfully!";
            } else {
                echo "Failed to submit rating.";
            }

        echo "Ticket closed successfully!";
    } else {
        echo "Ticket not found!";
    }
    header("location: dashboard.php");
}
?>