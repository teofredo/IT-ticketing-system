<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require 'db_con/db_conn.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try 
    {
        $subject = $_POST['subject'];
        $description = $_POST['description'];
        $category_id = $_POST['category_id'];
        $subcategory_id = $_POST['subcategory_id'];
        $priority_id = $_POST['priority_id'];
        $created_by = $_SESSION['user_id'];
        $status_id = 1;

        $priority = ($priority_id == 1) ? 'Low' : 
        (($priority_id == 2) ? 'Medium' : 
        (($priority_id == 3) ? 'High' : 
        (($priority_id == 4) ? 'Critical' : 'Unknown')));

        $stmt = $pdo->prepare("INSERT INTO tickets (subject, description, category_id, subcategory_id, priority_id, status_id, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$subject, $description, $category_id, $subcategory_id, $priority_id, $status_id, $created_by]);
       
        // Get ticket ID
        $ticketId = $pdo->lastInsertId();

        

        // If the ticket was created, send emails
        if ($ticketId) 
        {
            // 🛜 Fetch all technician and admin emails
                $techAdminEmails = [];
                $result = $pdo->query("SELECT email FROM users WHERE role IN ('technician', 'admin')");

                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    if (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                        continue; 
                    }
                
                    $techAdminEmails[] = $row['email'];
                }

                $techAdminEmailsString = implode(",", $techAdminEmails);

                $requestorEmail="";
                $requestorName="";
                $result = $pdo->query("SELECT name,email FROM users WHERE id = $created_by");
                $row = $result->fetch(PDO::FETCH_ASSOC);
                
                    $requestorEmail = $row['email'];
                    $requestorName = $row['name'];
                $mail = new PHPMailer(true);
                    try {
                        // SMTP Settings
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com'; // Change this to your mail server
                        $mail->SMTPAuth = true;
                        $mail->Username = ''; // SMTP username
                        $mail->Password = ''; // SMTP password
                        $mail->SMTPSecure = 'tls';
                        $mail->Port = 587;

                        $mail->setFrom('', 'ITTRV2');

                        // 📩 **1. Notify Technicians & Admins**
                            foreach ($techAdminEmails as $email) {
                                $mail->addAddress($email);
                            }

                        $mail->isHTML(true);
                        $mail->Subject = "New Ticket Created - #$ticketId";
                        $mail->Body = "
                            <h3>A new ticket has been created:</h3>
                            <b>Subject:</b> $subject <br>
                            <b>Description:</b> $description <br>
                            <b>Priority:</b> $priority <br>
                            <b>Link:</b> <a href='http://localhost/ittrv2'>ittrv2</a><br>
                        ";

                        $mail->send();
                        $mail->clearAddresses(); // Clear previous addresses

                        // 📩 **2. Send Confirmation to Requestor**
                        $mail->addAddress($requestorEmail);
                        $mail->Subject = "Your Support Ticket Has Been Submitted - #$ticketId";
                        $mail->Body = "
                            <h3>Hi $requestorName,</h3>
                            <p>Your ticket has been successfully submitted!</p>
                            <b>Ticket ID:</b> $ticketId <br>
                            <b>Subject:</b> $subject <br>
                            <b>Description:</b> $description <br>
                            <b>Priority:</b> $priority <br>
                            <p>Our support team will get back to you soon.</p>
                        ";

                        $mail->send();
                        echo json_encode(['success' => true, 'message' => 'Ticket created successfully']);
                    } 
                    catch (Exception $e) 
                    {
                        echo json_encode(['success' => false, 'message' => "Ticket created but email failed: {$mail->ErrorInfo}"]);
        
                    }
        }
    } catch (Exception $e) 
    {
        echo json_encode(['success' => false, 'message' => 'Failed to create ticket: ' . $e->getMessage()]);
    }
                        
           
}
?>
