<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
$host = "localhost"; 
$user = "root"; 
$pass = "";
$db   = "greenhouse";

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed',
        'error' => $conn->connect_error
    ]);
    exit;
}

// Get limit parameter (default 20, max 100)
$limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;

// Get date range parameters
$date_from = isset($_GET['from']) ? $_GET['from'] : null;
$date_to = isset($_GET['to']) ? $_GET['to'] : null;

// Build SQL query
$sql = "SELECT id, humidity, temperature, soil, light, created_at 
        FROM readings";

$where_conditions = [];
$params = [];
$types = "";

// Add date range filters if provided
if ($date_from) {
    $where_conditions[] = "created_at >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if ($date_to) {
    $where_conditions[] = "created_at <= ?";
    $params[] = $date_to . " 23:59:59";
    $types .= "s";
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

// Order by most recent timestamp first, then by ID as secondary sort
$sql .= " ORDER BY created_at DESC, id DESC LIMIT ?";
$params[] = $limit;
$types .= "i";

try {
    // Prepare and execute statement
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $rows = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Convert numeric light to descriptive text
            $light_value = intval($row['light']);
            if ($light_value > 3000) {
                $row['light'] = "Dark";
                $row['light_value'] = $light_value;
            } else if ($light_value > 2000) {
                $row['light'] = "Dim";
                $row['light_value'] = $light_value;
            } else if ($light_value > 1000) {
                $row['light'] = "Moderate";
                $row['light_value'] = $light_value;
            } else if ($light_value > 500) {
                $row['light'] = "Bright";
                $row['light_value'] = $light_value;
            } else {
                $row['light'] = "Very Bright";
                $row['light_value'] = $light_value;
            }
            
            // Format timestamp
            $row['formatted_time'] = date('M j, Y g:i A', strtotime($row['created_at']));
            $row['time_ago'] = timeAgo($row['created_at']);
            
            // Add status indicators
            $row['humidity_status'] = getHumidityStatus($row['humidity']);
            $row['temperature_status'] = getTemperatureStatus($row['temperature']);
            $row['soil_status'] = getSoilStatus($row['soil']);
            
            $rows[] = $row;
        }
    }
    
    // Get total count for pagination info
    $count_sql = "SELECT COUNT(*) as total FROM readings";
    if (!empty($where_conditions)) {
        $count_sql .= " WHERE " . implode(" AND ", array_slice($where_conditions, 0, -1));
    }
    
    $count_result = $conn->query($count_sql);
    $total_count = $count_result ? $count_result->fetch_assoc()['total'] : 0;
    
    // Return enhanced response
    echo json_encode([
        'status' => 'success',
        'data' => $rows,
        'pagination' => [
            'limit' => $limit,
            'total' => intval($total_count),
            'returned' => count($rows)
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database query failed',
        'error' => $e->getMessage()
    ]);
}

$conn->close();

// Helper function to get time ago
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}

// Helper function to get humidity status
function getHumidityStatus($humidity) {
    if ($humidity < 30) return ['status' => 'low', 'color' => '#e74c3c', 'icon' => 'fas fa-exclamation-triangle'];
    if ($humidity > 80) return ['status' => 'high', 'color' => '#9b59b6', 'icon' => 'fas fa-exclamation-triangle'];
    if ($humidity >= 40 && $humidity <= 70) return ['status' => 'optimal', 'color' => '#27ae60', 'icon' => 'fas fa-check-circle'];
    return ['status' => 'moderate', 'color' => '#f39c12', 'icon' => 'fas fa-info-circle'];
}

// Helper function to get temperature status
function getTemperatureStatus($temperature) {
    if ($temperature < 15) return ['status' => 'cold', 'color' => '#3498db', 'icon' => 'fas fa-thermometer-empty'];
    if ($temperature > 35) return ['status' => 'hot', 'color' => '#e74c3c', 'icon' => 'fas fa-thermometer-full'];
    if ($temperature >= 20 && $temperature <= 30) return ['status' => 'optimal', 'color' => '#27ae60', 'icon' => 'fas fa-thermometer-half'];
    return ['status' => 'moderate', 'color' => '#f39c12', 'icon' => 'fas fa-thermometer-quarter'];
}

// Helper function to get soil status
function getSoilStatus($soil) {
    if ($soil < 20) return ['status' => 'very_dry', 'color' => '#e74c3c', 'icon' => 'fas fa-exclamation-triangle'];
    if ($soil < 40) return ['status' => 'dry', 'color' => '#f39c12', 'icon' => 'fas fa-exclamation-circle'];
    if ($soil >= 40 && $soil <= 70) return ['status' => 'optimal', 'color' => '#27ae60', 'icon' => 'fas fa-check-circle'];
    return ['status' => 'wet', 'color' => '#3498db', 'icon' => 'fas fa-info-circle'];
}
?>
