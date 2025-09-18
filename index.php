<?php
// Smart Greenhouse System - Main Entry Point
// This file provides multiple access methods to the dashboard

// Get the current server information
$server_ip = $_SERVER['SERVER_ADDR'] ?? 'localhost';
$server_name = $_SERVER['SERVER_NAME'] ?? 'localhost';
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';

// Check if this is a 404 error or direct access to index.php
$is_404 = http_response_code() === 404;
$is_direct_access = strpos($request_uri, '/greenhouse/') === false && strpos($request_uri, '/greenhouse') === false;

// If accessing the greenhouse directory directly, redirect to dashboard
if (!$is_404 && !$is_direct_access) {
    header('Location: dashboard.php');
    exit;
}

// Show the information page for root access or 404 errors
if ($is_direct_access || $is_404) {
    // We're at the root, show information page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Smart Greenhouse System</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #2d5a27 0%, #4a7c59 50%, #87ceeb 100%);
                margin: 0;
                padding: 20px;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .container {
                background: rgba(255, 255, 255, 0.95);
                border-radius: 20px;
                padding: 40px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
                text-align: center;
                max-width: 600px;
            }
            h1 {
                color: #2d5a27;
                margin-bottom: 20px;
                font-size: 2.5rem;
            }
            .info {
                background: #f8f9fa;
                border-radius: 10px;
                padding: 20px;
                margin: 20px 0;
                border-left: 4px solid #2d5a27;
            }
            .url-list {
                text-align: left;
                margin: 20px 0;
            }
            .url-list li {
                margin: 10px 0;
                padding: 10px;
                background: #e8f5e8;
                border-radius: 5px;
                font-family: monospace;
            }
            .btn {
                display: inline-block;
                background: linear-gradient(135deg, #2d5a27, #4a7c59);
                color: white;
                padding: 15px 30px;
                text-decoration: none;
                border-radius: 10px;
                margin: 10px;
                font-weight: bold;
                transition: transform 0.3s ease;
            }
            .btn:hover {
                transform: translateY(-2px);
            }
            .warning {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                color: #856404;
                padding: 15px;
                border-radius: 10px;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🌱 Smart Greenhouse System</h1>
            
            <div class="info">
                <h3>System Status: ✅ Online</h3>
                <p>Your Smart Greenhouse monitoring system is running successfully!</p>
            </div>

            <?php if ($is_404): ?>
            <div class="warning" style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24;">
                <strong>⚠️ Page Not Found:</strong> The requested URL was not found. Please use one of the links below to access the system.
            </div>
            <?php else: ?>
            <div class="warning">
                <strong>⚠️ Important:</strong> To access the dashboard, you need to use the correct URL path.
            </div>
            <?php endif; ?>

            <h3>Access URLs:</h3>
            <ul class="url-list">
                <li><strong>Dashboard:</strong> <a href="/greenhouse/dashboard.php">http://<?php echo $server_name; ?>/greenhouse/dashboard.php</a></li>
                <li><strong>History:</strong> <a href="/greenhouse/history.php">http://<?php echo $server_name; ?>/greenhouse/history.php</a></li>
                <li><strong>API:</strong> <a href="/greenhouse/api.php">http://<?php echo $server_name; ?>/greenhouse/api.php</a></li>
                <li><strong>View Data:</strong> <a href="/greenhouse/view.php">http://<?php echo $server_name; ?>/greenhouse/view.php</a></li>
            </ul>

            <div>
                <a href="/greenhouse/dashboard.php" class="btn">🚀 Go to Dashboard</a>
                <a href="/greenhouse/view.php" class="btn">📊 View Data</a>
            </div>

            <div class="info">
                <h4>System Information:</h4>
                <p><strong>Server IP:</strong> <?php echo $server_ip; ?></p>
                <p><strong>Server Name:</strong> <?php echo $server_name; ?></p>
                <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
                <p><strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>
