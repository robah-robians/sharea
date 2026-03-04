<?php
function send_mock_email($to, $subject, $body)
{
    $logFile = __DIR__ . '/../logs/emails.json';

    // Ensure logs directory exists
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }

    $emails = [];
    if (file_exists($logFile)) {
        $json = file_get_contents($logFile);
        $emails = json_decode($json, true) ?? [];
    }

    $email = [
        'id' => uniqid(),
        'to' => $to,
        'subject' => $subject,
        'body' => $body,
        'date' => date('Y-m-d H:i:s')
    ];

    array_unshift($emails, $email); // Add newest to the top

    file_put_contents($logFile, json_encode($emails, JSON_PRETTY_PRINT));
    return true;
}
