<?php
// API endpoints for Smart Greenhouse System
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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

// ESP32 configuration
$esp32_ip = "192.168.137.105"; // Update this to your ESP32's IP

// Create DB connection
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed',
        'error' => $conn->connect_error
    ]);
    exit;
}

$conn->set_charset("utf8");

// Get the requested endpoint
$endpoint = '';
$method = $_SERVER['REQUEST_METHOD'];

// Check if endpoint is passed via GET parameter (from .htaccess rewrite)
if (isset($_GET['endpoint'])) {
    $endpoint = $_GET['endpoint'];
} else {
    // Fallback: parse from REQUEST_URI
    $request_uri = $_SERVER['REQUEST_URI'];
    $path = parse_url($request_uri, PHP_URL_PATH);
    $path_parts = explode('/', trim($path, '/'));
    
    // Remove 'greenhouse' and 'api.php' from path parts
    $path_parts = array_filter($path_parts, function($part) {
        return $part !== 'api.php' && $part !== 'greenhouse';
    });
    
    // Re-index the array after filtering
    $path_parts = array_values($path_parts);
    $endpoint = isset($path_parts[0]) ? $path_parts[0] : '';
}

// Route the request
switch ($endpoint) {
    case 'sensors':
        handleSensors($method, $conn, $esp32_ip);
        break;
    case 'control':
        handleControl($method, $conn, $esp32_ip);
        break;
    case 'status':
        handleStatus($method, $conn, $esp32_ip);
        break;
    case 'history':
        handleHistory($method, $conn);
        break;
    case 'analytics':
        handleAnalytics($method, $conn);
        break;
    default:
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Endpoint not found',
            'available_endpoints' => [
                'GET /api.php/sensors' => 'Get current sensor readings',
                'POST /api.php/control' => 'Control relays',
                'GET /api.php/status' => 'Get system status',
                'GET /api.php/history' => 'Get sensor history',
                'GET /api.php/analytics' => 'Get analytics data'
            ]
        ]);
        break;
}

$conn->close();

// Handle sensor data requests
function handleSensors($method, $conn, $esp32_ip) {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        return;
    }

    try {
        // Get current sensor data from ESP32 with database fallback
        $sensor_data = getCurrentSensorData($esp32_ip, $conn);
        
        if ($sensor_data) {
            // Add database timestamp
            $sensor_data['database_timestamp'] = date('Y-m-d H:i:s');
            $sensor_data['server_time'] = time();
            
            // Add analysis
            $sensor_data['analysis'] = [
                'humidity' => getHumidityStatus($sensor_data['humidity']),
                'temperature' => getTemperatureStatus($sensor_data['temperature']),
                'soil' => getSoilStatus($sensor_data['soil']),
                'light' => getLightStatus($sensor_data['light'])
            ];
            
            echo json_encode([
                'status' => 'success',
                'data' => $sensor_data,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } else {
            http_response_code(503);
            echo json_encode([
                'status' => 'error',
                'message' => 'Unable to fetch sensor data from ESP32',
                'esp32_ip' => $esp32_ip
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to get sensor data',
            'error' => $e->getMessage()
        ]);
    }
}

// Handle relay control requests
function handleControl($method, $conn, $esp32_ip) {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['relay']) || !isset($input['state'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing required parameters',
            'required' => ['relay', 'state']
        ]);
        return;
    }

    $relay = intval($input['relay']);
    $state = $input['state'];
    
    if ($relay < 1 || $relay > 3) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid relay number. Must be 1, 2, or 3'
        ]);
        return;
    }
    
    if (!in_array($state, ['on', 'off'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid state. Must be "on" or "off"'
        ]);
        return;
    }

    try {
        // Send control command to ESP32
        $result = sendControlCommand($esp32_ip, $relay, $state);
        
        if ($result) {
            // Log the control action
            logControlAction($conn, $relay, $state, $result);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Control command sent successfully',
                'data' => [
                    'relay' => $relay,
                    'state' => $state,
                    'response' => $result,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            http_response_code(503);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to send control command to ESP32'
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Control command failed',
            'error' => $e->getMessage()
        ]);
    }
}

// Handle system status requests
function handleStatus($method, $conn, $esp32_ip) {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        return;
    }

    try {
        // Get system status
        $status = [
            'system' => [
                'status' => 'online',
                'uptime' => getSystemUptime(),
                'server_time' => date('Y-m-d H:i:s'),
                'timezone' => date_default_timezone_get()
            ],
            'database' => [
                'status' => 'connected',
                'last_reading' => getLastReading($conn)
            ],
            'esp32' => [
                'ip' => $esp32_ip,
                'status' => checkESP32Status($esp32_ip),
                'last_communication' => getLastESP32Communication($conn)
            ],
            'sensors' => getCurrentSensorData($esp32_ip, $conn),
            'relays' => getRelayStatus($esp32_ip)
        ];
        
        echo json_encode([
            'status' => 'success',
            'data' => $status,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to get system status',
            'error' => $e->getMessage()
        ]);
    }
}

// Handle history requests
function handleHistory($method, $conn) {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        return;
    }

    $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
    $date_from = isset($_GET['from']) ? $_GET['from'] : null;
    $date_to = isset($_GET['to']) ? $_GET['to'] : null;

    try {
        $sql = "SELECT id, humidity, temperature, soil, light, created_at FROM readings";
        $where_conditions = [];
        $params = [];
        $types = "";

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

        $sql .= " ORDER BY id DESC LIMIT ?";
        $params[] = $limit;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $row['formatted_time'] = date('M j, Y g:i A', strtotime($row['created_at']));
            $row['time_ago'] = timeAgo($row['created_at']);
            $rows[] = $row;
        }

        echo json_encode([
            'status' => 'success',
            'data' => $rows,
            'pagination' => [
                'limit' => $limit,
                'returned' => count($rows)
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to get history',
            'error' => $e->getMessage()
        ]);
    }
}

// Handle analytics requests
function handleAnalytics($method, $conn) {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        return;
    }

    try {
        $analytics = [
            'summary' => getSummaryStats($conn),
            'trends' => getTrends($conn),
            'alerts' => getAlerts($conn),
            'performance' => getPerformanceMetrics($conn)
        ];

        echo json_encode([
            'status' => 'success',
            'data' => $analytics,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to get analytics',
            'error' => $e->getMessage()
        ]);
    }
}

// Helper functions
function getCurrentSensorData($esp32_ip, $conn = null) {
    $sensors = ['humidity', 'temperature', 'soil', 'light'];
    $data = [];
    $esp32_available = true;
    
    // Try to get data from ESP32 first
    foreach ($sensors as $sensor) {
        $url = "http://{$esp32_ip}/{$sensor}";
        $response = @file_get_contents($url, false, stream_context_create([
            'http' => ['timeout' => 3]
        ]));
        
        if ($response !== false) {
            if ($sensor === 'soil') {
                $parts = explode('|', $response);
                $data[$sensor] = intval($parts[0]);
                $data[$sensor . '_label'] = isset($parts[1]) ? $parts[1] : 'Unknown';
            } else {
                $data[$sensor] = ($sensor === 'humidity' || $sensor === 'temperature') ? floatval($response) : intval($response);
            }
        } else {
            $esp32_available = false;
            break;
        }
    }
    
    // If ESP32 is not available, try to get latest data from database
    if (!$esp32_available && $conn) {
        $result = $conn->query("SELECT humidity, temperature, soil, light FROM readings ORDER BY id DESC LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $data['humidity'] = floatval($row['humidity']);
            $data['temperature'] = floatval($row['temperature']);
            $data['soil'] = intval($row['soil']);
            $data['soil_label'] = getSoilLabel($row['soil']);
            $data['light'] = intval($row['light']);
            $data['source'] = 'database';
            $data['esp32_status'] = 'offline';
        } else {
            return false;
        }
    } else if ($esp32_available) {
        $data['source'] = 'esp32';
        $data['esp32_status'] = 'online';
    }
    
    return $data;
}

function getSoilLabel($soil) {
    if ($soil < 20) return 'Very Dry';
    if ($soil < 40) return 'Dry';
    if ($soil >= 40 && $soil <= 70) return 'Optimal';
    return 'Wet';
}

function sendControlCommand($esp32_ip, $relay, $state) {
    $url = "http://{$esp32_ip}/control?relay={$relay}&state={$state}";
    $response = @file_get_contents($url, false, stream_context_create([
        'http' => ['timeout' => 5]
    ]));
    
    return $response !== false ? $response : false;
}

function checkESP32Status($esp32_ip) {
    $url = "http://{$esp32_ip}/";
    $response = @file_get_contents($url, false, stream_context_create([
        'http' => ['timeout' => 3]
    ]));
    
    return $response !== false ? 'online' : 'offline';
}

function getRelayStatus($esp32_ip) {
    // This would need to be implemented on the ESP32 side
    // For now, return placeholder data
    return [
        'pump' => 'unknown',
        'light' => 'unknown',
        'fan' => 'unknown'
    ];
}

function getLastReading($conn) {
    $result = $conn->query("SELECT created_at FROM readings ORDER BY id DESC LIMIT 1");
    return $result && $result->num_rows > 0 ? $result->fetch_assoc()['created_at'] : null;
}

function getLastESP32Communication($conn) {
    $result = $conn->query("SELECT created_at FROM readings ORDER BY id DESC LIMIT 1");
    return $result && $result->num_rows > 0 ? $result->fetch_assoc()['created_at'] : null;
}

function getSystemUptime() {
    if (function_exists('sys_getloadavg')) {
        $uptime = shell_exec('uptime');
        return trim($uptime);
    }
    return 'Unknown';
}

function logControlAction($conn, $relay, $state, $response) {
    $relay_names = [1 => 'pump', 2 => 'light', 3 => 'fan'];
    $relay_name = isset($relay_names[$relay]) ? $relay_names[$relay] : "relay_{$relay}";
    
    $stmt = $conn->prepare("INSERT INTO control_log (relay, state, response, timestamp) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $relay, $state, $response);
    $stmt->execute();
    $stmt->close();
}

function getSummaryStats($conn) {
    $stats = [];
    
        // Get latest readings
        $result = $conn->query("SELECT humidity, temperature, soil, light FROM readings ORDER BY id DESC LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $stats['latest'] = $result->fetch_assoc();
        }
    
    // Get averages for last 24 hours
    $result = $conn->query("
        SELECT 
            AVG(humidity) as avg_humidity,
            AVG(temperature) as avg_temperature,
            AVG(soil) as avg_soil,
            AVG(light) as avg_light,
            COUNT(*) as total_readings
        FROM readings 
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    if ($result && $result->num_rows > 0) {
        $stats['last_24h'] = $result->fetch_assoc();
    }
    
    return $stats;
}

function getTrends($conn) {
    $result = $conn->query("
        SELECT 
            DATE(created_at) as date,
            AVG(humidity) as avg_humidity,
            AVG(temperature) as avg_temperature,
            AVG(soil) as avg_soil,
            AVG(light) as avg_light
        FROM readings 
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    
    $trends = [];
    while ($row = $result->fetch_assoc()) {
        $trends[] = $row;
    }
    
    return $trends;
}

function getAlerts($conn) {
    // Get readings that might need attention
    $result = $conn->query("
        SELECT id, humidity, temperature, soil, light, created_at
        FROM readings 
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        AND (humidity < 30 OR humidity > 80 OR temperature < 15 OR temperature > 35 OR soil < 20 OR soil > 80)
        ORDER BY created_at DESC
        LIMIT 10
    ");
    
    $alerts = [];
    while ($row = $result->fetch_assoc()) {
        $alerts[] = $row;
    }
    
    return $alerts;
}

function getPerformanceMetrics($conn) {
    $result = $conn->query("
        SELECT 
            COUNT(*) as total_readings,
            MIN(created_at) as first_reading,
            MAX(created_at) as last_reading
        FROM readings
    ");
    
    return $result && $result->num_rows > 0 ? $result->fetch_assoc() : [];
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}

function getHumidityStatus($humidity) {
    if ($humidity < 30) return ['status' => 'low', 'level' => 'warning'];
    if ($humidity > 80) return ['status' => 'high', 'level' => 'warning'];
    if ($humidity >= 40 && $humidity <= 70) return ['status' => 'optimal', 'level' => 'good'];
    return ['status' => 'moderate', 'level' => 'info'];
}

function getTemperatureStatus($temperature) {
    if ($temperature < 15) return ['status' => 'cold', 'level' => 'warning'];
    if ($temperature > 35) return ['status' => 'hot', 'level' => 'warning'];
    if ($temperature >= 20 && $temperature <= 30) return ['status' => 'optimal', 'level' => 'good'];
    return ['status' => 'moderate', 'level' => 'info'];
}

function getSoilStatus($soil) {
    if ($soil < 20) return ['status' => 'very_dry', 'level' => 'critical'];
    if ($soil < 40) return ['status' => 'dry', 'level' => 'warning'];
    if ($soil >= 40 && $soil <= 70) return ['status' => 'optimal', 'level' => 'good'];
    return ['status' => 'wet', 'level' => 'info'];
}

function getLightStatus($light) {
    if ($light > 3000) return ['status' => 'dark', 'level' => 'warning'];
    if ($light > 2000) return ['status' => 'dim', 'level' => 'info'];
    if ($light > 1000) return ['status' => 'moderate', 'level' => 'good'];
    if ($light > 500) return ['status' => 'bright', 'level' => 'good'];
    return ['status' => 'very_bright', 'level' => 'warning'];
}
?>
