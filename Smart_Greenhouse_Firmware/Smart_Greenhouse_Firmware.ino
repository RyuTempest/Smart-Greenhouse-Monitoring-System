#include <WiFi.h>
#include <WebServer.h>
#include <DHT.h>
#include <HTTPClient.h>

// ====== WiFi Credentials ======
const char* ssid = "RYUU";
const char* password = "123456789";

// ====== MySQL Database API ======
// e.g. "http://192.168.246.168/greenhouse/insert.php"
const char* serverName = "http://192.168.137.1/greenhouse/insert.php"; 

// ====== DHT Sensor Setup ======
#define DHTPIN 4
#define DHTTYPE DHT11
DHT dht(DHTPIN, DHTTYPE);

// ====== Sensor Pins ======
#define SOIL_SENSOR_PIN 34
#define LDR_PIN 35

// ====== Relay Pins (Active LOW) ======
#define RELAY_PUMP 26
#define RELAY_LIGHT 27
#define RELAY_FAN 25

WebServer server(80);

// ====== Database Logging Interval ======
unsigned long lastSendTime = 0;
const unsigned long sendInterval = 5000; // 5 seconds

// ----------------------------------------------------------------------------
// Sends sensor data (humidity, temperature, soil, light) to your local PHP script for MySQL
// ----------------------------------------------------------------------------
void sendToDatabase(float humidity, float temperature, int soil, int light) {
    if (WiFi.status() == WL_CONNECTED) {
        HTTPClient http;

        // e.g. http://192.168.246.168/insert.php?humidity=45.2&temperature=25.3&soil=60&light=1234
        String url = String(serverName);
        url += "?humidity=" + String(humidity);
        url += "&temperature=" + String(temperature);
        url += "&soil=" + String(soil);
        url += "&light=" + String(light);

        http.begin(url);
        int httpCode = http.GET();

        if (httpCode > 0) {
            Serial.println("[MySQL] Sent: " + url);
            String response = http.getString();
            Serial.println("[MySQL] Response: " + response);
        } else {
            Serial.println("[MySQL] Failed to connect to server.");
        }
        http.end();
    }
}

// ----------------------------------------------------------------------------
// Simple Status Page (Redirects to PHP Dashboard)
// ----------------------------------------------------------------------------
void handleRoot() {
    String html = R"rawliteral(
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Smart Greenhouse - ESP32</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                text-align: center; 
                padding: 50px; 
                background: linear-gradient(135deg, #2d5a27, #4a7c59);
                color: white;
                margin: 0;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                background: rgba(255,255,255,0.1);
                padding: 40px;
                border-radius: 20px;
                backdrop-filter: blur(10px);
            }
            h1 { font-size: 2.5rem; margin-bottom: 20px; }
            .status { font-size: 1.2rem; margin: 20px 0; }
            .btn {
                display: inline-block;
                padding: 15px 30px;
                background: #27ae60;
                color: white;
                text-decoration: none;
                border-radius: 10px;
                font-size: 1.1rem;
                margin: 10px;
                transition: all 0.3s;
            }
            .btn:hover { background: #2ecc71; transform: translateY(-2px); }
            .info { margin: 20px 0; font-size: 0.9rem; opacity: 0.8; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🌱 Smart Greenhouse</h1>
            <div class="status">ESP32 System Online</div>
            <p>This is the ESP32 sensor interface. For the full dashboard experience, please use the web interface.</p>
            
            <a href="http://192.168.137.1/greenhouse/dashboard.php" class="btn">Open Dashboard</a>
            <a href="http://192.168.137.1/greenhouse/history.php" class="btn">View History</a>
            
            <div class="info">
                <p><strong>System Status:</strong> All sensors operational</p>
                <p><strong>Last Update:</strong> )rawliteral" + String(millis() / 1000) + R"rawliteral( seconds ago</p>
            </div>
        </div>
    </body>
    </html>
    )rawliteral";

    server.send(200, "text/html", html);
}

// ----------------------------------------------------------------------------
// Relay Controls
// ----------------------------------------------------------------------------
void handleRelayControl() {
    String relay = server.arg("relay");
    String state = server.arg("state");

    if (relay == "1") {
        digitalWrite(RELAY_PUMP, (state == "on") ? LOW : HIGH);
        server.send(200, "text/plain", "Pump " + state);
    } else if (relay == "2") {
        digitalWrite(RELAY_LIGHT, (state == "on") ? LOW : HIGH);
        server.send(200, "text/plain", "Light " + state);
    } else if (relay == "3") {
        digitalWrite(RELAY_FAN, (state == "on") ? LOW : HIGH);
        server.send(200, "text/plain", "Fan " + state);
    } else {
        server.send(400, "text/plain", "Invalid Relay");
    }
}

// ----------------------------------------------------------------------------
// Sensor Endpoints
// ----------------------------------------------------------------------------
void handleHumidity() {
    float humidity = dht.readHumidity();
    if (isnan(humidity)) humidity = -1;
    server.send(200, "text/plain", String(humidity));
}

void handleTemperature() {
    float temperature = dht.readTemperature();
    if (isnan(temperature)) temperature = -1;
    server.send(200, "text/plain", String(temperature));
}

void handleSoilMoisture() {
    int soilRaw = analogRead(SOIL_SENSOR_PIN);
    int wet = 1000, dry = 3200;
    int percent = map(soilRaw, dry, wet, 0, 100);
    percent = constrain(percent, 0, 100);

    String label;
    if (percent < 20) label = "Very Dry";
    else if (percent < 40) label = "Dry";
    else if (percent < 70) label = "Moist";
    else label = "Wet";

    server.send(200, "text/plain", String(percent) + "|" + label);
}

void handleLightIntensity() {
    int light = analogRead(LDR_PIN);
    server.send(200, "text/plain", String(light));
}

// ----------------------------------------------------------------------------
// JSON Status Endpoint
// ----------------------------------------------------------------------------
void handleStatus() {
    float humidity = dht.readHumidity();
    if (isnan(humidity)) humidity = -1;
    
    float temperature = dht.readTemperature();
    if (isnan(temperature)) temperature = -1;
    
    int soilRaw = analogRead(SOIL_SENSOR_PIN);
    int soilPercent = map(soilRaw, 3200, 1000, 0, 100);
    soilPercent = constrain(soilPercent, 0, 100);
    
    int light = analogRead(LDR_PIN);
    
    String json = "{";
    json += "\"status\":\"online\",";
    json += "\"uptime\":" + String(millis() / 1000) + ",";
    json += "\"sensors\":{";
    json += "\"humidity\":" + String(humidity) + ",";
    json += "\"temperature\":" + String(temperature) + ",";
    json += "\"soil\":" + String(soilPercent) + ",";
    json += "\"light\":" + String(light);
    json += "},";
    json += "\"relays\":{";
    json += "\"pump\":" + String(digitalRead(RELAY_PUMP) == LOW ? "on" : "off") + ",";
    json += "\"light\":" + String(digitalRead(RELAY_LIGHT) == LOW ? "on" : "off") + ",";
    json += "\"fan\":" + String(digitalRead(RELAY_FAN) == LOW ? "on" : "off");
    json += "},";
    json += "\"wifi\":{";
    json += "\"ssid\":\"" + String(WiFi.SSID()) + "\",";
    json += "\"rssi\":" + String(WiFi.RSSI()) + ",";
    json += "\"ip\":\"" + WiFi.localIP().toString() + "\"";
    json += "},";
    json += "\"timestamp\":" + String(millis());
    json += "}";
    
    server.send(200, "application/json", json);
}

// ----------------------------------------------------------------------------
// WiFi Reconnect
// ----------------------------------------------------------------------------
void checkWiFi() {
    if (WiFi.status() != WL_CONNECTED) {
        WiFi.disconnect();
        WiFi.reconnect();
    }
}

// ----------------------------------------------------------------------------
// Setup
// ----------------------------------------------------------------------------
void setup() {
    Serial.begin(115200);
    dht.begin();

    pinMode(RELAY_PUMP, OUTPUT);
    pinMode(RELAY_LIGHT, OUTPUT);
    pinMode(RELAY_FAN, OUTPUT);

    // Default relay states = OFF
    digitalWrite(RELAY_PUMP, HIGH);
    digitalWrite(RELAY_LIGHT, HIGH);
    digitalWrite(RELAY_FAN, HIGH);

    // WiFi Connect
    WiFi.begin(ssid, password);
    Serial.print("Connecting to WiFi...");
    while (WiFi.status() != WL_CONNECTED) {
        delay(500);
        Serial.print(".");
    }
    Serial.println("\nWiFi connected!");
    Serial.println(WiFi.localIP());

    // Routes
    server.on("/", handleRoot);
    server.on("/control", handleRelayControl);
    server.on("/humidity", handleHumidity);
    server.on("/temperature", handleTemperature);
    server.on("/soil", handleSoilMoisture);
    server.on("/light", handleLightIntensity);
    server.on("/status", handleStatus);

    server.begin();
}

// ----------------------------------------------------------------------------
// Main Loop
// ----------------------------------------------------------------------------
void loop() {
    server.handleClient();
    checkWiFi();

    // Every 5 seconds, insert data to MySQL
    if (millis() - lastSendTime > sendInterval) {
        float humidity = dht.readHumidity();
        float temperature = dht.readTemperature();
        if (!isnan(humidity) && !isnan(temperature)) {
            int soilRaw = analogRead(SOIL_SENSOR_PIN);
            int soilPercent = map(soilRaw, 3200, 1000, 0, 100);
            soilPercent = constrain(soilPercent, 0, 100);

            int lightValue = analogRead(LDR_PIN);

            // Save to MySQL
            sendToDatabase(humidity, temperature, soilPercent, lightValue);
        }
        lastSendTime = millis();
    }
}
