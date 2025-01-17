<?php 
    set_time_limit(300);
    $startTime = microtime(true);
    
    // Get credentials from request
    $username = $_GET['username'];
    $pat = $_GET['token'];
    $html = $_GET['html'];
    $folderName = $_GET['folderName'];
    $branch = $_GET['branch'];
    $cmd = $_GET['command'];

    // Execute the Git pull command with credentials
    $repoDir = "C:/xampp/htdocs/$folderName";
    $output = [];
    $returnVar = 0;
    $logfilename = 'webhook_logs/log_'.date("Y-m-d").'.txt';
    $directory = dirname($logfilename);

    // Construct the Git command with credentials
    if($cmd == 'clone') {
        $gitCommand = "cd C:/xampp/htdocs && git clone https://$username:$pat@$html 2>&1";
    } else if($cmd == 'pull') {
        $gitCommand = "cd $repoDir && git pull https://$username:$pat@$html $branch 2>&1";
    } else {
        $gitCommand = $cmd;    
    }

    exec($gitCommand, $output, $returnVar);

    // Log the output and return status
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true); // Create directory with proper permissions
    }

    $executionTime = microtime(true) - $startTime;
    $logContent = "Execution Time: " . $executionTime . " seconds\n";
    $logContent .= implode("\n", $output) . "\n";

    file_put_contents($logfilename, $logContent, FILE_APPEND);
    
    if ($returnVar === 0) {
        http_response_code(200);
        echo "Result: " . implode("\n", $output);
    } else {
        http_response_code(500);
        echo "Result: " . implode("\n", $output);
    }
?>