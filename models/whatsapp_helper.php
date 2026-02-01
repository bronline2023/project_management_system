<?php
/**
 * models/whatsapp_helper.php
 *
 * This file contains helper functions for sending WhatsApp messages via the official Meta WhatsApp Business Cloud API.
 * FINAL & COMPLETE: This version uses cURL to send a POST request with a JSON payload and Bearer token authentication.
 */

if (!defined('ROOT_PATH')) {
    require_once __DIR__ . '/../config.php';
}
if (!function_exists('connectDB')) {
    require_once MODELS_PATH . 'db.php';
}

/**
 * Sends a WhatsApp message using the Meta Business Cloud API.
 *
 * SETUP INSTRUCTIONS for Meta API:
 * 1. Go to Meta for Developers (developers.facebook.com) and create an App.
 * 2. From the App Dashboard, set up the "WhatsApp" product.
 * 3. In the "API Setup" section, you will find:
 * - A temporary Access Token.
 * - A Phone Number ID.
 * - A Test Phone Number (for sending messages to).
 * 4. For a live application, you will need to get a Permanent Access Token.
 * 5. Go to your software's Admin Panel -> Settings -> WhatsApp API Settings.
 * 6. Paste your "Phone Number ID" into the 'WhatsApp Business Phone Number ID' field.
 * 7. Paste your "Access Token" (temporary or permanent) into the 'WhatsApp Access Token' field.
 * 8. Save settings.
 *
 * @param string $client_phone The client's phone number (with country code, e.g., 919876543210).
 * @param array $task_details An associative array containing task details.
 * @return bool True on success, false on failure.
 */
function sendWhatsAppCompletionMessage($client_phone, $task_details) {
    $pdo = connectDB();
    
    try {
        // Renamed settings to match Meta API fields
        $settings = fetchOne($pdo, "SELECT app_name, whatsapp_business_number, whatsapp_api_key FROM settings WHERE id = 1 LIMIT 1");

        if (!$settings || empty($settings['whatsapp_business_number']) || empty($settings['whatsapp_api_key'])) {
            error_log("WhatsApp Meta API Error: Phone Number ID or Access Token is not configured in settings.");
            return false;
        }

        $phone_number_id = $settings['whatsapp_business_number']; // This is your "Phone Number ID" from Meta
        $access_token = $settings['whatsapp_api_key'];           // This is your "Access Token" from Meta
        $recipient_number = preg_replace('/[^0-9]/', '', $client_phone);

        if(empty($recipient_number)){
            error_log("WhatsApp Meta API Error: Client phone number is invalid for Task ID " . ($task_details['id'] ?? 'N/A'));
            return false;
        }

    } catch (PDOException $e) {
        error_log("WhatsApp DB Error: " . $e->getMessage());
        return false;
    }
    
    // The API endpoint for the Meta Graph API
    $api_url = "https://graph.facebook.com/v19.0/{$phone_number_id}/messages";

    $appName = $settings['app_name'] ?? 'Our Services';
    $clientName = $task_details['client_name'] ?? 'Valued Client';
    $taskId = $task_details['id'] ?? 'N/A';

    // Construct a simple text message
    $message = "Dear {$clientName},\n\nWe are pleased to inform you that your task (ID: #{$taskId}) has been successfully completed.\n\nThank you for choosing *{$appName}*!";

    // Create the JSON payload as required by Meta API
    $payload = json_encode([
        "messaging_product" => "whatsapp",
        "to" => $recipient_number,
        "type" => "text",
        "text" => [
            "body" => $message
        ]
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        error_log("WhatsApp Meta API cURL Error for Task ID {$taskId}: " . $curl_error);
        return false;
    }
    
    $response_data = json_decode($response, true);

    if ($http_code >= 200 && $http_code < 300 && !isset($response_data['error'])) {
        error_log("WhatsApp Meta API message sent successfully for Task ID {$taskId}. Response: " . $response);
        return true;
    } else {
        $error_message = $response_data['error']['message'] ?? $response;
        error_log("WhatsApp Meta API failed for Task ID {$taskId} with HTTP code {$http_code}. Error: " . $error_message);
        return false;
    }
}
?>