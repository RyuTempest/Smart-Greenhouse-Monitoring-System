<?php
// Error handling page for the Smart Greenhouse System
$error_code = http_response_code();
$error_message = '';

switch ($error_code) {
    case 404:
        $error_message = 'Page Not Found';
        break;
    case 500:
        $error_message = 'Internal Server Error';
        break;
    case 403:
        $error_message = 'Access Forbidden';
        break;
    default:
        $error_message = 'An Error Occurred';
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?php echo $error_code; ?> - Smart Greenhouse System</title>
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
        .error-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 600px;
        }
        .error-code {
            font-size: 6rem;
            font-weight: bold;
            color: #dc143c;
            margin-bottom: 20px;
        }
        .error-message {
            font-size: 1.5rem;
            color: #2d5a27;
            margin-bottom: 30px;
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
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code"><?php echo $error_code; ?></div>
        <div class="error-message"><?php echo $error_message; ?></div>
        <p>The page you're looking for doesn't exist or there was an error processing your request.</p>
        
        <div>
            <a href="dashboard.php" class="btn">🏠 Go to Dashboard</a>
            <a href="index.php" class="btn">📋 System Info</a>
        </div>
        
        <div style="margin-top: 30px; font-size: 0.9rem; color: #666;">
            <p>Smart Greenhouse System - Error Handler</p>
            <p>Time: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>
