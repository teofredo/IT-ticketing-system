<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require 'db_con/db_conn.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
        exit();
    }

    $action = $data['action'];
    $ticketId = $data['ticketId'];

    if ($action === 'assign' && isset($data['assignee'])) {
        $assignee = $data['assignee'];
        $stmt = $pdo->prepare("UPDATE tickets SET assigned_to = :assignee, status_id = 2 WHERE id = :ticketId");
        $stmt->execute(['assignee' => $assignee, 'ticketId' => $ticketId]);

        $requestorEmail="";
        $requestorName="";
        $result = $pdo->query("SELECT name,email FROM users LEFT JOIN tickets ON tickets.created_by = users.id WHERE tickets.id = $ticketId");
                $row = $result->fetch(PDO::FETCH_ASSOC);
                    
                $requestorEmail = $row['email'];
                $requestorName = $row['name'];
        
        
        $result = $pdo->query("SELECT name,email FROM users WHERE users.id = $assignee");
                $row = $result->fetch(PDO::FETCH_ASSOC);
                    
                $assignedTechEmail = $row['email'];
                $assignedTech = $row['name'];
                
                
        $mail = new PHPMailer(true);
        //
        // After assigning a ticket
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Change this to your mail server
            $mail->SMTPAuth = true;
            $mail->Username = ''; // SMTP username
            $mail->Password = ''; // SMTP password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
    
            // Sender and recipients
            $mail->setFrom('', 'ITTRV2');
            $mail->addAddress($requestorEmail); // Requestor's email
            
            // Add CC recipients
            $result = $pdo->query("SELECT email FROM users WHERE role IN ('technician', 'admin')");
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                if (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                    continue; 
                }
            
                $techAdminEmails[] = $row['email'];
            }
            foreach ($techAdminEmails as $ccEmail) {
                $mail->addCC($ccEmail);
            }
    
            // Email content
            $mail->isHTML(true);
            $mail->Subject = "Your Support Ticket #$ticketId Has Been Assigned";
            $mail->Body = "
                <h2>Your Support Ticket Has Been Assigned</h2>
                <p>Dear $requestorName,</p>
                <p>Your ticket (ID: <strong>$ticketId</strong>) has been assigned to <strong>$assignedTech</strong>.</p>
                <p>Our technician will contact you soon to assist with your request.</p>
                <p>Thank you!</p>
                <hr>
                <p>Best regards,<br>IT Support Team</p>
            ";
    
            // Send the email
            $mail->send();
            //

            echo json_encode(['status' => 'success', 'message' => "Ticket #$ticketId assigned to user ID $assignee"]);
            exit();
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $mail->ErrorInfo]);
            exit();
        }
    }

    if ($action === 'accept') {
        $stmt = $pdo->prepare("UPDATE tickets SET status_id = 2, assigned_to = :user_id WHERE id = :ticketId");
        $stmt->execute(['user_id' => $_SESSION['user_id'] ,'ticketId' => $ticketId]);
        $requestorEmail="";
        $requestorName="";
        $result = $pdo->query("SELECT name,email FROM users LEFT JOIN tickets ON tickets.created_by = users.id WHERE tickets.id = $ticketId");
                $row = $result->fetch(PDO::FETCH_ASSOC);
                    
                $requestorEmail = $row['email'];
                $requestorName = $row['name'];
        
        
        $result = $pdo->query("SELECT name,email FROM users WHERE users.id =  '".$_SESSION['user_id'] ."'");
                $row = $result->fetch(PDO::FETCH_ASSOC);
                    
                $assignedTechEmail = $row['email'];
                $assignedTech = $row['name'];
                
                
        $mail = new PHPMailer(true);
        //
        // After assigning a ticket
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Change this to your mail server
            $mail->SMTPAuth = true;
            $mail->Username = ''; // SMTP username
            $mail->Password = ''; // SMTP password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
    
            // Sender and recipients
            $mail->setFrom('', 'ITTRV2');
            $mail->addAddress($requestorEmail); // Requestor's email
            
            // Add CC recipients
            $result = $pdo->query("SELECT email FROM users WHERE role IN ('technician', 'admin')");
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                if (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                    continue; 
                }
            
                $techAdminEmails[] = $row['email'];
            }
            foreach ($techAdminEmails as $ccEmail) {
                $mail->addCC($ccEmail);
            }
    
            // Email content
            $mail->isHTML(true);
            $mail->Subject = "Your Support Ticket #$ticketId Has Been Assigned";
            $mail->Body = "
                <h2>Your Support Ticket Has Been Assigned</h2>
                <p>Dear $requestorName,</p>
                <p>Your ticket (ID: <strong>$ticketId</strong>) has been assigned to <strong>$assignedTech</strong>.</p>
                <p>Our technician will contact you soon to assist with your request.</p>
                <p>Thank you!</p>
                <hr>
                <p>Best regards,<br>IT Support Team</p>
            ";
    
            // Send the email
            $mail->send();
            //
            
            echo json_encode(['status' => 'success', 'message' => "Ticket #$ticketId accepted"]);
            exit();
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $mail->ErrorInfo]);
            exit();
        }
    }

    if ($action === 'update' && isset($data['status'])) {
        $status = $data['status'];
        $stmt = $pdo->prepare("UPDATE tickets SET status_id = :status WHERE id = :ticketId");
        $stmt->execute(['status' => $status, 'ticketId' => $ticketId]);

        $requestorEmail="";
        $requestorName="";
        $result = $pdo->query("SELECT name,email FROM users LEFT JOIN tickets ON tickets.created_by = users.id WHERE tickets.id = $ticketId");
                $row = $result->fetch(PDO::FETCH_ASSOC);
                    
                $requestorEmail = $row['email'];
                $requestorName = $row['name'];
        
        
        $result = $pdo->query("SELECT name,email FROM users WHERE users.id =  '".$_SESSION['user_id'] ."'");
                $row = $result->fetch(PDO::FETCH_ASSOC);
                    
                $assignedTechEmail = $row['email'];
                $assignedTech = $row['name'];

                $status_ = ($status == 1) ? 'Open' : 
                (($status == 2) ? 'In Progress' : 
                (($status == 3) ? 'Resolved' : 
                (($status == 4) ? 'Closed' : 'Unknown')));        
                
        $mail = new PHPMailer(true);
        //
        // After assigning a ticket
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Change this to your mail server
            $mail->SMTPAuth = true;
            $mail->Username = ''; // SMTP username
            $mail->Password = ''; // SMTP password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
    
            // Sender and recipients
            $mail->setFrom('', 'ITTRV2');
            $mail->addAddress($requestorEmail); // Requestor's email
            
            // Add CC recipients
            $result = $pdo->query("SELECT email FROM users WHERE role IN ('technician', 'admin')");
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                if (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                    continue; 
                }
            
                $techAdminEmails[] = $row['email'];
            }
            foreach ($techAdminEmails as $ccEmail) {
                $mail->addCC($ccEmail);
            }
    
            // Email content
            $mail->isHTML(true);
            $mail->Subject = "Your Support Ticket #$ticketId Has Been Updated";
            $mail->Body = "
                <h2>Your Support Ticket Has Been Updated</h2>
                <p>Dear $requestorName,</p>
                <p>Your ticket (ID: <strong>$ticketId</strong>) has been Updated to <strong>$status_</strong>.</p>
                <p>Please accept the closure of your request and rate my performance thru <a href='http://localhost/ittrv2'>ittrv2</a>.</p>
                <p>Thank you!</p>
                <hr>
                <p>Best regards,<br>IT Support Team</p>
            ";
    
            // Send the email
            $mail->send();
            //
            
        echo json_encode(['status' => 'success', 'message' => "Ticket #$ticketId status updated"]);
            exit();
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $mail->ErrorInfo]);
            exit();
        }
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>
