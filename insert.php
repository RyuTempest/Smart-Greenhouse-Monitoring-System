<?php
// Set headers for better API response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database configuration
$host = "localhost";
$user = "root";
$pass = "";
$db   = "greenhouse";

// Create DB connection
$conn = new mysqli($host, $user, $pass, $db);

// Check for DB connection error
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed',
        'error' => $conn->connect_error,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Set charset to utf8
$conn->set_charset("utf8");

// Get sensor values from URL parameters or POST data
$humidity = null;
$temperature = null;
$soil = null;
$light = null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $humidity = isset($_GET['humidity']) ? floatval($_GET['humidity']) : null;
    $temperature = isset($_GET['temperature']) ? floatval($_GET['temperature']) : null;
    $soil = isset($_GET['soil']) ? intval($_GET['soil']) : null;
    $light = isset($_GET['light']) ? intval($_GET['light']) : null;
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        $humidity = isset($input['humidity']) ? floatval($input['humidity']) : null;
        $temperature = isset($input['temperature']) ? floatval($input['temperature']) : null;
        $soil = isset($input['soil']) ? intval($input['soil']) : null;
        $light = isset($input['light']) ? intval($input['light']) : null;
    } else {
        $humidity = isset($_POST['humidity']) ? floatval($_POST['humidity']) : null;
        $temperature = isset($_POST['temperature']) ? floatval($_POST['temperature']) : null;
        $soil = isset($_POST['soil']) ? intval($_POST['soil']) : null;
        $light = isset($_POST['light']) ? intval($_POST['light']) : null;
    }
}

// Validate the data
if ($humidity === null || $temperature === null || $soil === null || $light === null) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required parameters',
        'required' => ['humidity', 'temperature', 'soil', 'light'],
        'received' => [
            'humidity' => $humidity,
            'temperature' => $temperature,
            'soil' => $soil,
            'light' => $light
        ],
        'method' => $_SERVER['REQUEST_METHOD'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Enhanced data validation with detailed error messages
$validation_errors = [];

if ($humidity < 0 || $humidity > 100) {
    $validation_errors[] = [
        'field' => 'humidity',
        'value' => $humidity,
        'message' => 'Humidity must be between 0-100%',
        'range' => '0-100'
    ];
}

if ($temperature < -40 || $temperature > 80) {
    $validation_errors[] = [
        'field' => 'temperature',
        'value' => $temperature,
        'message' => 'Temperature must be between -40°C to 80°C',
        'range' => '-40 to 80'
    ];
}

if ($soil < 0 || $soil > 100) {
    $validation_errors[] = [
        'field' => 'soil',
        'value' => $soil,
        'message' => 'Soil moisture must be between 0-100%',
        'range' => '0-100'
    ];
}

if ($light < 0 || $light > 4095) {
    $validation_errors[] = [
        'field' => 'light',
        'value' => $light,
        'message' => 'Light intensity must be between 0-4095',
        'range' => '0-4095'
    ];
}

if (!empty($validation_errors)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Data validation failed',
        'validation_errors' => $validation_errors,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Check for duplicate data (same values within last 30 seconds)
$duplicate_check = $conn->prepare("
    SELECT id, created_at 
    FROM readings 
    WHERE humidity = ? AND temperature = ? AND soil = ? AND light = ? 
    AND created_at > DATE_SUB(NOW(), INTERVAL 30 SECOND)
    ORDER BY created_at DESC 
    LIMIT 1
");
$duplicate_check->bind_param("ddii", $humidity, $temperature, $soil, $light);
$duplicate_check->execute();
$duplicate_result = $duplicate_check->get_result();

if ($duplicate_result->num_rows > 0) {
    $duplicate = $duplicate_result->fetch_assoc();
    http_response_code(409);
    echo json_encode([
        'status' => 'warning',
        'message' => 'Duplicate data detected',
        'data' => [
            'id' => $duplicate['id'],
            'humidity' => $humidity,
            'temperature' => $temperature,
            'soil' => $soil,
            'light' => $light,
            'created_at' => $duplicate['created_at']
        ],
        'note' => 'Same sensor values received within 30 seconds',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    $duplicate_check->close();
    $conn->close();
    exit;
}
$duplicate_check->close();

try {
    // Start transaction for data integrity
    $conn->autocommit(false);
    
    // Prepare and execute the insert statement
    $stmt = $conn->prepare("INSERT INTO readings (humidity, temperature, soil, light) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ddii", $humidity, $temperature, $soil, $light);

    if ($stmt->execute()) {
        $insertId = $conn->insert_id;
        
        // Get the inserted record for response
        $result_stmt = $conn->prepare("SELECT * FROM readings WHERE id = ?");
        $result_stmt->bind_param("i", $insertId);
        $result_stmt->execute();
        $result = $result_stmt->get_result();
        $record = $result->fetch_assoc();
        $result_stmt->close();
        
        // Add status analysis
        $status_analysis = [
            'humidity' => getHumidityStatus($humidity),
            'temperature' => getTemperatureStatus($temperature),
            'soil' => getSoilStatus($soil),
            'light' => getLightStatus($light)
        ];
        
        // Commit transaction
        $conn->commit();
        
        http_response_code(201);
        echo json_encode([
            'status' => 'success',
            'message' => 'Data inserted successfully',
            'data' => [
                'id' => $insertId,
                'humidity' => $humidity,
                'temperature' => $temperature,
                'soil' => $soil,
                'light' => $light,
                'created_at' => $record['created_at']
            ],
            'analysis' => $status_analysis,
            'timestamp' => date('Y-m-d H:i:s'),
            'server_time' => time()
        ]);
    } else {
        $conn->rollback();
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to insert data',
            'error' => $stmt->error,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    $stmt->close();
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database operation failed',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} finally {
    $conn->autocommit(true);
}

$conn->close();

// Helper function to get humidity status
function getHumidityStatus($humidity) {
    if ($humidity < 30) {
        return ['status' => 'low', 'level' => 'warning', 'message' => 'Humidity too low', 'recommendation' => 'Increase humidity'];
    } elseif ($humidity > 80) {
        return ['status' => 'high', 'level' => 'warning', 'message' => 'Humidity too high', 'recommendation' => 'Improve ventilation'];
    } elseif ($humidity >= 40 && $humidity <= 70) {
        return ['status' => 'optimal', 'level' => 'good', 'message' => 'Humidity optimal', 'recommendation' => 'Maintain current conditions'];
    } else {
        return ['status' => 'moderate', 'level' => 'info', 'message' => 'Humidity acceptable', 'recommendation' => 'Monitor closely'];
    }
}

// Helper function to get temperature status
function getTemperatureStatus($temperature) {
    if ($temperature < 15) {
        return ['status' => 'cold', 'level' => 'warning', 'message' => 'Temperature too cold', 'recommendation' => 'Increase heating'];
    } elseif ($temperature > 35) {
        return ['status' => 'hot', 'level' => 'warning', 'message' => 'Temperature too hot', 'recommendation' => 'Improve cooling/ventilation'];
    } elseif ($temperature >= 20 && $temperature <= 30) {
        return ['status' => 'optimal', 'level' => 'good', 'message' => 'Temperature optimal', 'recommendation' => 'Maintain current conditions'];
    } else {
        return ['status' => 'moderate', 'level' => 'info', 'message' => 'Temperature acceptable', 'recommendation' => 'Monitor closely'];
    }
}

// Helper function to get soil status
function getSoilStatus($soil) {
    if ($soil < 20) {
        return ['status' => 'very_dry', 'level' => 'critical', 'message' => 'Soil very dry', 'recommendation' => 'Water immediately'];
    } elseif ($soil < 40) {
        return ['status' => 'dry', 'level' => 'warning', 'message' => 'Soil dry', 'recommendation' => 'Water soon'];
    } elseif ($soil >= 40 && $soil <= 70) {
        return ['status' => 'optimal', 'level' => 'good', 'message' => 'Soil moisture optimal', 'recommendation' => 'Maintain current watering'];
    } else {
        return ['status' => 'wet', 'level' => 'info', 'message' => 'Soil wet', 'recommendation' => 'Reduce watering'];
    }
}

// Helper function to get light status
function getLightStatus($light) {
    if ($light > 3000) {
        return ['status' => 'dark', 'level' => 'warning', 'message' => 'Very dark', 'recommendation' => 'Increase lighting'];
    } elseif ($light > 2000) {
        return ['status' => 'dim', 'level' => 'info', 'message' => 'Dim lighting', 'recommendation' => 'Consider additional lighting'];
    } elseif ($light > 1000) {
        return ['status' => 'moderate', 'level' => 'good', 'message' => 'Moderate lighting', 'recommendation' => 'Good for most plants'];
    } elseif ($light > 500) {
        return ['status' => 'bright', 'level' => 'good', 'message' => 'Bright lighting', 'recommendation' => 'Excellent for growth'];
    } else {
        return ['status' => 'very_bright', 'level' => 'warning', 'message' => 'Very bright', 'recommendation' => 'May need shading'];
    }
}
?>
