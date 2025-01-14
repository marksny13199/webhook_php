<?php

set_time_limit(300);

// Define your repository directory and branch
$repoDir = "C:/xampp/htdocs/{$_GET['folderName']}";
$branch = $_GET['branch'];
$username = $GET['username'];
$pat = $_GET['token'];
$html = $_GET['html'];
$secret = 'somethingtheheonlyknow';

$logfilename = 'webhook_logs/log_'.date("Y-m-d").'.txt';
$directory = dirname($logfilename);

// Get the headers and payload
$headers = getallheaders();
$payload = file_get_contents('php://input');

// Validate GitHub secret
if (isset($headers['X-Hub-Signature-256'])) {
    $signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    if (!hash_equals($signature, $headers['X-Hub-Signature-256'])) {
        http_response_code(403);
        echo 'Invalid secret';
        exit;
    }
}

$contentType = $headers['Content-Type'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? null;

if ($contentType === 'application/x-www-form-urlencoded') {
    parse_str($payload, $parsedData);
    $payload = $parsedData['payload'] ?? null;
}

// Decode the JSON payload
$data = json_decode($payload, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true); // Create directory with proper permissions
    }
    file_put_contents($logfilename, 'JSON Decode Error: ' . json_last_error_msg() . "\n", FILE_APPEND);
    http_response_code(400);
    echo "Invalid JSON payload";
    exit;
}

// Check if the event is a push and the branch matches
if ($headers['X-Github-Event'] === 'push' && strpos($data['ref'], $branch) !== false) {
    // Execute the Git pull command
    $output = [];
    $returnVar = 0;

    $gitCommand = "cd $repoDir && git pull https://$username:$pat@$html $branch 2>&1";
    exec($gitCommand, $output, $returnVar);

    // Log the output and return status
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true); // Create directory with proper permissions
    }

    file_put_contents($logfilename, implode("\n", $output) . "\n", FILE_APPEND);
    
    if ($returnVar === 0) {
        http_response_code(200);
        echo "Repository updated successfully";
    } else {
        http_response_code(500);
        echo "Git pull failed: " . implode("\n", $output);
    }
} else {
    if($headers['X-Github-Event'] === 'ping'){
        http_response_code(200);
        echo "Pinged successfully";
    } else {
        http_response_code(400);
        echo "Invalid event or branch";
    }
}
?>
