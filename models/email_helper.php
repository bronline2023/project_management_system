<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'db.php';
require_once __DIR__ . '/../vendor/autoload.php';

function getEmailSettings() {
    $pdo = connectDB();
    return fetchOne($pdo, "SELECT * FROM settings WHERE id = 1 LIMIT 1");
}

function sendEmail($to, $subject, $body, $altBody = '', $attachmentPath = null) {
    $mailSettings = getEmailSettings();
    if (empty($mailSettings['smtp_host']) || empty($mailSettings['smtp_username'])) {
        error_log("SMTP settings are not configured. Email to $to not sent.");
        return false;
    }
    
    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host = $mailSettings['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $mailSettings['smtp_username'];
        $mail->Password = $mailSettings['smtp_password'];
        $mail->SMTPSecure = $mailSettings['smtp_encryption'];
        $mail->Port = $mailSettings['smtp_port'];

        //Recipients
        $mail->setFrom($mailSettings['smtp_from_email'], $mailSettings['smtp_from_name']);
        $mail->addAddress($to);

        // Attachments
        if ($attachmentPath && file_exists(ROOT_PATH . $attachmentPath)) {
            $mail->addAttachment(ROOT_PATH . $attachmentPath);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed to $to. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

function sendAppointmentConfirmationEmail($appointment) {
    $settings = getEmailSettings();
    $subject = "Appointment Confirmation: {$appointment['client_name']}";
    $template = file_get_contents(__DIR__ . '/email_template_appointment.html');
    
    $appointment_id = $appointment['id'];
    $client_name = htmlspecialchars($appointment['client_name'] ?? '');
    $service_name = htmlspecialchars($appointment['category_name'] ?? '');
    $appointment_date = date('d M, Y', strtotime($appointment['appointment_date']));
    $appointment_time = date('h:i A', strtotime($appointment['appointment_time']));
    $person_to_meet = htmlspecialchars($appointment['user_name'] ?? '');
    $notes = htmlspecialchars($appointment['notes'] ?? '');

    $required_docs_html = '';
    if (!empty($appointment['required_documents'])) {
        $docs = explode(PHP_EOL, $appointment['required_documents']);
        $required_docs_html .= '<h4>Required Documents:</h4><ul>';
        foreach ($docs as $doc) {
            $doc = trim($doc);
            if (!empty($doc)) {
                $required_docs_html .= '<li>' . htmlspecialchars($doc) . '</li>';
            }
        }
        $required_docs_html .= '</ul>';
    }

    $placeholders = [
        '{{subject}}' => $subject,
        '{{app_logo_url}}' => $settings['app_logo_url'],
        '{{app_name}}' => $settings['app_name'],
        '{{office_address}}' => $settings['office_address'],
        '{{helpline_number}}' => $settings['helpline_number'],
        '{{client_name}}' => $client_name,
        '{{appointment_id}}' => $appointment_id,
        '{{service_name}}' => $service_name,
        '{{appointment_date}}' => $appointment_date,
        '{{appointment_time}}' => $appointment_time,
        '{{person_to_meet}}' => $person_to_meet,
        '{{notes}}' => $notes,
        '{{required_documents_html}}' => $required_docs_html,
        '{{message_content}}' => "Your appointment has been successfully booked with us. Below are the details:",
        '{{closing_note}}' => "We look forward to seeing you. If you need to change or cancel your appointment, please contact us."
    ];

    $body = str_replace(
        array_keys($placeholders),
        array_values($placeholders),
        $template
    );
    
    return sendEmail($appointment['client_email'], $subject, $body, 'Your appointment has been booked successfully.');
}

// NEW FUNCTION: Send Appointment Status Update Email
function sendAppointmentStatusUpdateEmail($appointment) {
    $settings = getEmailSettings();
    $subject = "Appointment Status Updated: #{$appointment['id']}";
    $template = file_get_contents(__DIR__ . '/email_template_appointment.html');

    $status_map = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];
    $status_text = $status_map[$appointment['status']] ?? 'Updated';
    $statusColor = '';
    switch ($appointment['status']) {
        case 'confirmed': $statusColor = '#28a745'; break;
        case 'completed': $statusColor = '#6c757d'; break;
        case 'cancelled': $statusColor = '#dc3545'; break;
        default: $statusColor = '#ffc107'; break;
    }
    
    $message_content = "The status of your appointment (ID: #{$appointment['id']}) has been updated to <span style='color: {$statusColor}; font-weight: bold;'>{$status_text}</span>.";

    $placeholders = [
        '{{subject}}' => $subject,
        '{{app_logo_url}}' => $settings['app_logo_url'],
        '{{app_name}}' => $settings['app_name'],
        '{{office_address}}' => $settings['office_address'],
        '{{helpline_number}}' => $settings['helpline_number'],
        '{{client_name}}' => htmlspecialchars($appointment['client_name'] ?? ''),
        '{{appointment_id}}' => $appointment['id'],
        '{{service_name}}' => htmlspecialchars($appointment['category_name'] ?? ''),
        '{{appointment_date}}' => date('d M, Y', strtotime($appointment['appointment_date'])),
        '{{appointment_time}}' => date('h:i A', strtotime($appointment['appointment_time'])),
        '{{person_to_meet}}' => htmlspecialchars($appointment['user_name'] ?? ''),
        '{{notes}}' => htmlspecialchars($appointment['notes'] ?? ''),
        '{{required_documents_html}}' => '', // This is not needed for a status update
        '{{message_content}}' => $message_content,
        '{{closing_note}}' => "Thank you for your business."
    ];

    $body = str_replace(
        array_keys($placeholders),
        array_values($placeholders),
        $template
    );
    
    return sendEmail($appointment['client_email'], $subject, $body, "Your appointment status has been updated to {$status_text}.");
}

function sendTaskStatusUpdateEmail($task) {
    $settings = getEmailSettings();
    $subject = "Task Status Updated: #{$task['id']}";
    $template = file_get_contents(__DIR__ . '/email_template_task.html');

    $status_map = [
        'pending' => 'Pending',
        'in_process' => 'In Process',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'pending_verification' => 'Pending Verification',
        'verified_completed' => 'Verified and Completed',
        'returned' => 'Returned to Admin'
    ];
    $status_text = $status_map[$task['status']] ?? 'Updated';
    
    $message_content = "The status of your task (ID: #{$task['id']}) has been updated to: <strong>{$status_text}</strong>.";
    $closing_note = "You can contact us using the task ID for any updates.";
    $attachment_path = null;

    if ($task['status'] === 'completed' || $task['status'] === 'verified_completed') {
         $message_content = "We are pleased to inform you that your task (ID: #{$task['id']}) has been successfully completed.";
         $closing_note = "Attached is the final receipt for your records. Thank you for choosing our services.";
         // Set attachment path if the task is completed and receipt exists
         if (!empty($task['completion_receipt_path'])) {
             $attachment_path = $task['completion_receipt_path'];
         }
    } elseif ($task['status'] === 'in_process') {
         $message_content = "Your task (ID: #{$task['id']}) is now **in process**.";
    }

    $placeholders = [
        '{{subject}}' => $subject,
        '{{app_logo_url}}' => $settings['app_logo_url'],
        '{{app_name}}' => $settings['app_name'],
        '{{office_address}}' => $settings['office_address'],
        '{{helpline_number}}' => $settings['helpline_number'],
        '{{client_name}}' => htmlspecialchars($task['customer_name'] ?? ''),
        '{{task_id}}' => $task['id'],
        '{{service_name}}' => htmlspecialchars(($task['category_name'] ?? '') . ' - ' . ($task['subcategory_name'] ?? '')),
        '{{work_description}}' => htmlspecialchars($task['work_description'] ?? ''),
        '{{current_status}}' => $status_text,
        '{{message_content}}' => $message_content,
        '{{closing_note}}' => $closing_note
    ];

    $body = str_replace(
        array_keys($placeholders),
        array_values($placeholders),
        $template
    );
    
    return sendEmail($task['customer_email'], $subject, $body, "Your task #{$task['id']} status has been updated to: {$status_text}.", $attachment_path);
}