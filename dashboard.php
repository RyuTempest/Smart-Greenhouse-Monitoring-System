<?php
// Dashboard configuration
$esp32_ip = "192.168.137.105"; // ESP32 IP address (updated to match database schema)
$refresh_interval = 2000; // 2 seconds
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Smart Greenhouse Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-green: #1e3a2e;
            --secondary-green: #2d5a27;
            --accent-green: #4a7c59;
            --light-green: #6b8e23;
            --bright-green: #90ee90;
            --dark-green: #0f1f15;
            --earth-brown: #8b4513;
            --rich-brown: #a0522d;
            --sky-blue: #4fc3f7;
            --deep-blue: #2196f3;
            --sun-yellow: #ffd700;
            --warm-yellow: #ffeb3b;
            --warning-orange: #ff8c00;
            --danger-red: #e53e3e;
            --success-green: #38a169;
            --text-dark: #1a202c;
            --text-medium: #4a5568;
            --text-light: #718096;
            --text-white: #ffffff;
            --card-bg: rgba(255, 255, 255, 0.85);
            --glass-bg: rgba(255, 255, 255, 0.15);
            --glass-border: rgba(255, 255, 255, 0.2);
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 8px 32px rgba(0, 0, 0, 0.12);
            --shadow-lg: 0 16px 64px rgba(0, 0, 0, 0.16);
            --shadow-xl: 0 24px 80px rgba(0, 0, 0, 0.20);
            --gradient-primary: linear-gradient(135deg, var(--primary-green) 0%, var(--secondary-green) 100%);
            --gradient-accent: linear-gradient(135deg, var(--accent-green) 0%, var(--light-green) 100%);
            --gradient-sky: linear-gradient(135deg, var(--sky-blue) 0%, var(--deep-blue) 100%);
            --gradient-sun: linear-gradient(135deg, var(--sun-yellow) 0%, var(--warm-yellow) 100%);
            --gradient-earth: linear-gradient(135deg, var(--earth-brown) 0%, var(--rich-brown) 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--secondary-green) 30%, var(--accent-green) 70%, var(--sky-blue) 100%);
            height: 100vh;
            color: var(--text-dark);
            overflow: hidden;
            position: relative;
            font-weight: 400;
            line-height: 1.4;
            width: 100%;
            max-width: 100vw;
        }

        /* Modern animated background elements */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(144, 238, 144, 0.15) 0%, transparent 60%),
                radial-gradient(circle at 80% 20%, rgba(79, 195, 247, 0.15) 0%, transparent 60%),
                radial-gradient(circle at 40% 40%, rgba(255, 215, 0, 0.08) 0%, transparent 60%),
                radial-gradient(circle at 60% 60%, rgba(107, 142, 35, 0.1) 0%, transparent 50%);
            z-index: -1;
            animation: backgroundFloat 25s ease-in-out infinite;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 10% 20%, rgba(255, 255, 255, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(255, 255, 255, 0.03) 0%, transparent 40%);
            z-index: -1;
            animation: backgroundFloat 30s ease-in-out infinite reverse;
        }

        @keyframes backgroundFloat {
            0%, 100% { 
                transform: translateY(0px) rotate(0deg) scale(1);
                opacity: 1;
            }
            33% { 
                transform: translateY(-15px) rotate(0.5deg) scale(1.02); 
                opacity: 0.8;
            }
            66% { 
                transform: translateY(-25px) rotate(-0.5deg) scale(0.98); 
                opacity: 0.9;
            }
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 16px;
            position: relative;
            z-index: 1;
            width: 100%;
            height: 100vh;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Modern header with enhanced glassmorphism */
        .header {
            background: var(--gradient-primary);
            border-radius: 20px;
            padding: 20px 24px;
            margin-bottom: 16px;
            box-shadow: var(--shadow-lg);
            text-align: center;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            flex-shrink: 0;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(255, 255, 255, 0.03) 10px,
                rgba(255, 255, 255, 0.03) 20px
            );
            animation: headerPattern 30s linear infinite;
        }

        @keyframes headerPattern {
            0% { transform: translateX(-50px) translateY(-50px); }
            100% { transform: translateX(50px) translateY(50px); }
        }

        .header-content {
            position: relative;
            z-index: 2;
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .logo i {
            font-size: 3rem;
            color: var(--light-green);
            margin-right: 20px;
            animation: logoPulse 2s ease-in-out infinite;
        }

        @keyframes logoPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-white);
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            margin-bottom: 8px;
            letter-spacing: -0.02em;
        }

        .subtitle {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 400;
            letter-spacing: 0.01em;
        }

        /* Modern status indicator */
        .status-indicator {
            display: inline-flex;
            align-items: center;
            background: var(--glass-bg);
            padding: 8px 16px;
            border-radius: 20px;
            margin-top: 12px;
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .status-indicator:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--success-green);
            margin-right: 8px;
            animation: statusBlink 2s ease-in-out infinite;
        }

        @keyframes statusBlink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Modern dashboard grid */
        .dashboard {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 16px;
            flex-shrink: 0;
        }

        /* Modern sensor cards with enhanced glassmorphism */
        .sensor-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .sensor-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: var(--gradient-accent);
            border-radius: 24px 24px 0 0;
        }

        .sensor-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: var(--shadow-xl);
        }

        .humidity-card::before { background: var(--gradient-sky); }
        .temperature-card::before { background: linear-gradient(135deg, #ff6b6b, #ff8e8e); }
        .soil-card::before { background: var(--gradient-earth); }
        .light-card::before { background: var(--gradient-sun); }

        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
        }

        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
            font-size: 1.5rem;
            color: var(--text-white);
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
        }

        .card-icon:hover {
            transform: scale(1.1) rotate(5deg);
        }

        .humidity-icon { background: var(--gradient-sky); }
        .temperature-icon { background: linear-gradient(135deg, #ff6b6b, #ff8e8e); }
        .soil-icon { background: var(--gradient-earth); }
        .light-icon { background: var(--gradient-sun); }

        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-dark);
            letter-spacing: -0.01em;
        }

        .card-value {
            font-size: 2.8rem;
            font-weight: 700;
            margin: 16px 0;
            color: var(--text-dark);
            display: flex;
            align-items: baseline;
            letter-spacing: -0.02em;
        }

        .card-unit {
            font-size: 1.2rem;
            margin-left: 6px;
            opacity: 0.6;
            font-weight: 500;
        }

        .card-label {
            font-size: 1rem;
            color: var(--text-medium);
            margin-bottom: 16px;
            font-weight: 500;
        }

        /* Modern progress bars with enhanced styling */
        .progress-container {
            margin-top: 16px;
        }

        .progress-bar {
            height: 10px;
            background: rgba(0, 0, 0, 0.08);
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .progress-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, 
                rgba(255, 255, 255, 0.3) 0%, 
                rgba(255, 255, 255, 0.6) 50%, 
                rgba(255, 255, 255, 0.3) 100%);
            transform: translateX(-100%);
            animation: progressShimmer 3s infinite;
        }

        @keyframes progressShimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .progress-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
            font-size: 0.85rem;
            color: var(--text-light);
            font-weight: 500;
        }

        /* Modern control section */
        .controls-section {
            margin-bottom: 16px;
            flex-shrink: 0;
        }

        .section-title {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--accent-green);
            position: relative;
            flex-shrink: 0;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 40px;
            height: 2px;
            background: var(--gradient-accent);
            border-radius: 1px;
        }

        .section-title i {
            font-size: 1.8rem;
            color: var(--primary-green);
            margin-right: 12px;
        }

        .section-title h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            letter-spacing: -0.01em;
        }

        .auto-refresh-indicator {
            display: flex;
            align-items: center;
            margin-left: auto;
            color: var(--text-light);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .auto-refresh-indicator i {
            margin-right: 8px;
            font-size: 0.8rem;
        }

        .controls-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .control-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .control-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: var(--gradient-accent);
            border-radius: 24px 0 0 24px;
        }

        .control-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-xl);
        }

        .control-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .control-title {
            display: flex;
            align-items: center;
            font-size: 1.2rem;
            font-weight: 600;
            letter-spacing: -0.01em;
        }

        .control-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 1.2rem;
            color: var(--text-white);
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
        }

        .control-icon:hover {
            transform: scale(1.1) rotate(5deg);
        }

        .pump-icon { background: var(--gradient-sky); }
        .light-icon { background: var(--gradient-sun); }
        .fan-icon { background: var(--gradient-accent); }

        .control-buttons {
            display: flex;
            gap: 12px;
            margin-top: 16px;
        }

        .btn {
            flex: 1;
            padding: 12px 16px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            letter-spacing: 0.01em;
        }

        .btn i {
            margin-right: 8px;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-on {
            background: linear-gradient(135deg, var(--success-green), #48bb78);
            color: var(--text-white);
            box-shadow: var(--shadow-sm);
        }

        .btn-on:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 24px rgba(56, 161, 105, 0.4);
        }

        .btn-off {
            background: linear-gradient(135deg, var(--danger-red), #f56565);
            color: var(--text-white);
            box-shadow: var(--shadow-sm);
        }

        .btn-off:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 24px rgba(229, 62, 62, 0.4);
        }

        .status {
            text-align: center;
            margin-top: 16px;
            padding: 12px;
            border-radius: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .status i {
            margin-right: 8px;
        }

        .status-on {
            background: linear-gradient(135deg, var(--success-green), #48bb78);
            color: var(--text-white);
            box-shadow: var(--shadow-sm);
        }

        .status-off {
            background: linear-gradient(135deg, var(--danger-red), #f56565);
            color: var(--text-white);
            box-shadow: var(--shadow-sm);
        }

        .status-waiting {
            background: linear-gradient(135deg, var(--text-medium), var(--text-light));
            color: var(--text-white);
            box-shadow: var(--shadow-sm);
        }

        /* Modern history section */
        .history-section {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 16px;
            box-shadow: var(--shadow-md);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            min-height: 0;
        }

        .table-wrapper {
            width: 100%;
            overflow: auto;
            margin-top: 12px;
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
            flex: 1;
            min-height: 0;
            max-height: 200px;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            table-layout: fixed;
            min-width: 500px;
        }

        .history-table th {
            background: var(--gradient-primary);
            color: var(--text-white);
            padding: 8px 6px;
            text-align: center;
            font-weight: 600;
            font-size: 0.8rem;
            letter-spacing: 0.01em;
        }

        .history-table th:nth-child(1) { width: 20%; } /* Humidity */
        .history-table th:nth-child(2) { width: 20%; } /* Temperature */
        .history-table th:nth-child(3) { width: 20%; } /* Soil */
        .history-table th:nth-child(4) { width: 20%; } /* Light */
        .history-table th:nth-child(5) { width: 20%; } /* Timestamp */

        .history-table td {
            padding: 6px 4px;
            text-align: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 500;
            font-size: 0.8rem;
        }

        .history-table tr:hover {
            background: rgba(30, 58, 46, 0.03);
            transform: scale(1.01);
            transition: all 0.2s ease;
        }

        /* Enhanced history table styles */
        .record-number {
            font-weight: 600;
            color: var(--primary-green);
            font-size: 0.9rem;
        }

        .new-badge {
            background: linear-gradient(135deg, var(--success-green), #48bb78);
            color: white;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 8px;
            margin-left: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(56, 161, 105, 0.3);
        }

        .sensor-value {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sensor-value .value {
            font-weight: 600;
            color: var(--text-dark);
        }

        .light-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .light-value {
            font-weight: 600;
            color: var(--text-dark);
        }

        .timestamp {
            text-align: center;
        }

        .timestamp .time {
            font-weight: 500;
            color: var(--text-dark);
            font-size: 0.9rem;
        }

        .timestamp .time-ago {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-top: 2px;
        }

        /* Compact footer */
        .footer {
            text-align: center;
            margin-top: 16px;
            padding: 12px;
            color: rgba(255, 255, 255, 0.9);
            background: var(--glass-bg);
            border-radius: 16px;
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-sm);
            flex-shrink: 0;
        }

        .update-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .update-indicator i {
            margin-right: 10px;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Enhanced responsive design for all devices */
        
        /* Desktop optimization for 100% viewport fit */
        @media (min-width: 1025px) {
            .container {
                padding: 8px;
            }
            
            .header {
                padding: 12px 16px;
                margin-bottom: 8px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .subtitle {
                font-size: 0.9rem;
            }
            
            .dashboard {
                margin-bottom: 8px;
            }
            
            .controls-section {
                margin-bottom: 8px;
            }
            
            .section-title {
                margin-bottom: 8px;
                padding-bottom: 6px;
            }
            
            .section-title h2 {
                font-size: 1.3rem;
            }
            
            .section-title i {
                font-size: 1.5rem;
            }
            
            .history-section {
                padding: 12px;
            }
            
            .table-wrapper {
                margin-top: 8px;
                max-height: 180px;
            }
            
            .history-table th {
                padding: 6px 4px;
                font-size: 0.75rem;
            }
            
            .history-table td {
                padding: 4px 2px;
                font-size: 0.75rem;
            }
            
            .sensor-card {
                padding: 16px;
            }
            
            .card-header {
                margin-bottom: 12px;
            }
            
            .card-icon {
                width: 40px;
                height: 40px;
                font-size: 1.3rem;
            }
            
            .card-title {
                font-size: 1.1rem;
            }
            
            .card-value {
                font-size: 2.4rem;
                margin: 12px 0;
            }
            
            .card-label {
                font-size: 0.9rem;
                margin-bottom: 12px;
            }
            
            .progress-container {
                margin-top: 12px;
            }
            
            .footer {
                margin-top: 8px;
                padding: 6px;
            }
        }
        
        /* Large tablets and small desktops */
        @media (max-width: 1024px) {
            .container {
                padding: 20px;
            }
            
            .dashboard {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 24px;
            }
            
            .controls-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 24px;
            }
            
            .header h1 {
                font-size: 3rem;
            }
            
            .card-value {
                font-size: 3.5rem;
            }
        }

        /* Tablets */
        @media (max-width: 768px) {
            .container {
                padding: 16px;
            }
            
            .dashboard, .controls-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .header {
                padding: 24px 20px;
                margin-bottom: 24px;
            }
            
            .header h1 {
                font-size: 2.5rem;
            }
            
            .subtitle {
                font-size: 1.1rem;
            }
            
            .card-value {
                font-size: 3rem;
            }
            
            .section-title {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .section-title h2 {
                font-size: 1.8rem;
            }
            
            .auto-refresh-indicator {
                margin-left: 0;
                margin-top: 8px;
            }
            
            .control-buttons {
                flex-direction: column;
                gap: 12px;
            }
            
            .sensor-card, .control-card {
                padding: 24px;
            }
            
            .history-section {
                padding: 24px;
            }
            
            .table-wrapper {
                margin-top: 20px;
            }
            
            .history-table {
                font-size: 0.9rem;
                min-width: 450px;
            }
            
            .history-table th,
            .history-table td {
                padding: 12px 8px;
            }
        }

        /* Mobile devices */
        @media (max-width: 480px) {
            .container {
                padding: 12px;
            }
            
            .header {
                padding: 20px 16px;
                margin-bottom: 20px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .subtitle {
                font-size: 1rem;
            }
            
            .card-value {
                font-size: 2.5rem;
            }
            
            .card-unit {
                font-size: 1.2rem;
            }
            
            .section-title h2 {
                font-size: 1.5rem;
            }
            
            .sensor-card, .control-card {
                padding: 20px;
            }
            
            .history-section {
                padding: 20px;
            }
            
            .table-wrapper {
                margin-top: 16px;
            }
            
            .history-table {
                font-size: 0.8rem;
                min-width: 400px;
            }
            
            .history-table th,
            .history-table td {
                padding: 10px 6px;
            }
            
            .btn {
                padding: 14px 20px;
                font-size: 1rem;
            }
            
            .card-icon {
                width: 56px;
                height: 56px;
                font-size: 1.6rem;
            }
            
            .control-icon {
                width: 48px;
                height: 48px;
                font-size: 1.3rem;
            }
        }

        /* Very small devices */
        @media (max-width: 360px) {
            .container {
                padding: 8px;
            }
            
            .header {
                padding: 16px 12px;
                margin-bottom: 16px;
            }
            
            .header h1 {
                font-size: 1.8rem;
            }
            
            .subtitle {
                font-size: 0.9rem;
            }
            
            .card-value {
                font-size: 2.2rem;
            }
            
            .sensor-card, .control-card {
                padding: 16px;
            }
            
            .history-section {
                padding: 16px;
            }
            
            .table-wrapper {
                margin-top: 12px;
            }
            
            .history-table {
                font-size: 0.75rem;
                min-width: 320px;
            }
            
            .history-table th,
            .history-table td {
                padding: 8px 4px;
            }
            
            .btn {
                padding: 12px 16px;
                font-size: 0.9rem;
            }
        }

        /* Landscape orientation adjustments */
        @media (max-height: 600px) and (orientation: landscape) {
            .header {
                padding: 16px 20px;
                margin-bottom: 16px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .subtitle {
                font-size: 1rem;
            }
            
            .dashboard {
                margin-bottom: 24px;
            }
            
            .controls-section {
                margin-bottom: 24px;
            }
            
            .footer {
                margin-top: 24px;
                padding: 20px;
            }
        }

        /* Enhanced loading animation */
        .loading {
            display: inline-block;
            width: 24px;
            height: 24px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: var(--text-white);
            animation: spin 1s ease-in-out infinite;
        }

        /* Additional modern effects */
        .sensor-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
            pointer-events: none;
            border-radius: 24px;
        }

        /* Smooth scroll behavior */
        html {
            scroll-behavior: smooth;
        }

        /* Focus states for accessibility */
        .btn:focus,
        .control-card:focus {
            outline: 2px solid var(--accent-green);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-seedling"></i>
                    <h1>Smart Greenhouse</h1>
                </div>
                <div class="subtitle">Advanced Monitoring & Control System</div>
                <div class="status-indicator">
                    <div class="status-dot"></div>
                    <span>System Online</span>
                </div>
            </div>
        </header>

        <!-- Sensor Dashboard -->
        <div class="dashboard">
            <!-- Humidity Card -->
            <div class="sensor-card humidity-card">
                <div class="card-header">
                    <div class="card-icon humidity-icon">
                        <i class="fas fa-tint"></i>
                    </div>
                    <div class="card-title">Air Humidity</div>
                </div>
                <div class="card-value" id="humidity">
                    <span class="loading"></span>
                    <span class="card-unit">%</span>
                </div>
                <div class="card-label" id="humidity-label">Relative Humidity</div>
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" id="humidity-progress" style="width: 0%; background: var(--gradient-sky);"></div>
                    </div>
                    <div class="progress-labels">
                        <span>Dry</span>
                        <span>Optimal</span>
                        <span>Humid</span>
                    </div>
                </div>
            </div>

            <!-- Temperature Card -->
            <div class="sensor-card temperature-card">
                <div class="card-header">
                    <div class="card-icon temperature-icon">
                        <i class="fas fa-thermometer-half"></i>
                    </div>
                    <div class="card-title">Temperature</div>
                </div>
                <div class="card-value" id="temperature">
                    <span class="loading"></span>
                    <span class="card-unit">°C</span>
                </div>
                <div class="card-label" id="temperature-label">Air Temperature</div>
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" id="temperature-progress" style="width: 0%; background: linear-gradient(135deg, #ff6b6b, #ff8e8e);"></div>
                    </div>
                    <div class="progress-labels">
                        <span>Cold</span>
                        <span>Optimal</span>
                        <span>Hot</span>
                    </div>
                </div>
            </div>

            <!-- Soil Moisture Card -->
            <div class="sensor-card soil-card">
                <div class="card-header">
                    <div class="card-icon soil-icon">
                        <i class="fas fa-seedling"></i>
                    </div>
                    <div class="card-title">Soil Moisture</div>
                </div>
                <div class="card-value" id="soil">
                    <span class="loading"></span>
                    <span class="card-unit">%</span>
                </div>
                <div class="card-label" id="soil-label">Soil Condition</div>
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" id="soil-progress" style="width: 0%; background: var(--gradient-earth);"></div>
                    </div>
                    <div class="progress-labels">
                        <span>Dry</span>
                        <span>Moist</span>
                        <span>Wet</span>
                    </div>
                </div>
            </div>

            <!-- Light Intensity Card -->
            <div class="sensor-card light-card">
                <div class="card-header">
                    <div class="card-icon light-icon">
                        <i class="fas fa-sun"></i>
                    </div>
                    <div class="card-title">Light Intensity</div>
                </div>
                <div class="card-value" id="light">
                    <span class="loading"></span>
                </div>
                <div class="card-label" id="light-label">Light Level</div>
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" id="light-progress" style="width: 0%; background: var(--gradient-sun);"></div>
                    </div>
                    <div class="progress-labels">
                        <span>Dark</span>
                        <span>Moderate</span>
                        <span>Bright</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Controls -->
        <div class="controls-section">
            <div class="section-title">
                <i class="fas fa-sliders-h"></i>
                <h2>System Controls</h2>
            </div>
            <div class="controls-grid">
                <!-- Water Pump Control -->
                <div class="control-card">
                    <div class="control-header">
                        <div class="control-title">
                            <div class="control-icon pump-icon">
                                <i class="fas fa-tint"></i>
                            </div>
                            Water Pump
                        </div>
                    </div>
                    <div class="control-buttons">
                        <button class="btn btn-on" onclick="toggleRelay(1, 'on')">
                            <i class="fas fa-power-off"></i> ON
                        </button>
                        <button class="btn btn-off" onclick="toggleRelay(1, 'off')">
                            <i class="fas fa-power-off"></i> OFF
                        </button>
                    </div>
                    <div class="status status-waiting" id="status1">
                        <i class="fas fa-circle-notch fa-spin"></i> Waiting...
                    </div>
                </div>

                <!-- Grow Lights Control -->
                <div class="control-card">
                    <div class="control-header">
                        <div class="control-title">
                            <div class="control-icon light-icon">
                                <i class="fas fa-lightbulb"></i>
                            </div>
                            Grow Lights
                        </div>
                    </div>
                    <div class="control-buttons">
                        <button class="btn btn-on" onclick="toggleRelay(2, 'on')">
                            <i class="fas fa-power-off"></i> ON
                        </button>
                        <button class="btn btn-off" onclick="toggleRelay(2, 'off')">
                            <i class="fas fa-power-off"></i> OFF
                        </button>
                    </div>
                    <div class="status status-waiting" id="status2">
                        <i class="fas fa-circle-notch fa-spin"></i> Waiting...
                    </div>
                </div>

                <!-- Exhaust Fan Control -->
                <div class="control-card">
                    <div class="control-header">
                        <div class="control-title">
                            <div class="control-icon fan-icon">
                                <i class="fas fa-fan"></i>
                            </div>
                            Exhaust Fan
                        </div>
                    </div>
                    <div class="control-buttons">
                        <button class="btn btn-on" onclick="toggleRelay(3, 'on')">
                            <i class="fas fa-power-off"></i> ON
                        </button>
                        <button class="btn btn-off" onclick="toggleRelay(3, 'off')">
                            <i class="fas fa-power-off"></i> OFF
                        </button>
                    </div>
                    <div class="status status-waiting" id="status3">
                        <i class="fas fa-circle-notch fa-spin"></i> Waiting...
                    </div>
                </div>
            </div>
        </div>

        <!-- History Section -->
        <div class="history-section">
            <div class="section-title">
                <i class="fas fa-history"></i>
                <h2>Recent Sensor Data</h2>
                <div class="auto-refresh-indicator">
                    <i class="fas fa-sync-alt fa-spin"></i>
                    <span>Auto-refreshing</span>
            </div>
            </div>
            <div class="table-wrapper">
            <table class="history-table" id="history-table">
                <thead>
                    <tr>
                        <th>Humidity (%)</th>
                        <th>Temperature (°C)</th>
                        <th>Soil (%)</th>
                        <th>Light</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody id="history-body">
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 20px;">
                            <div class="loading"></div>
                            Loading data...
                        </td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>

        <!-- Compact Footer -->
        <div class="footer">
            <div class="update-indicator">
                <i class="fas fa-sync-alt"></i>
                <span id="last-update">Last updated: Just now</span>
            </div>
        </div>
    </div>

    <script>
        const ESP32_IP = '<?php echo $esp32_ip; ?>';
        const REFRESH_INTERVAL = <?php echo $refresh_interval; ?>;

        // Update sensor data with improved error handling and fallback
        async function updateSensorData() {
            try {
                // First try to get data from API endpoint (which can handle both ESP32 and database)
                const apiResponse = await fetch('api.php/sensors');
                
                if (apiResponse.ok) {
                    const apiData = await apiResponse.json();
                    
                    if (apiData.status === 'success' && apiData.data) {
                        const data = apiData.data;
                        
                        // Update humidity
                        if (data.humidity !== undefined) {
                            const humidity = parseFloat(data.humidity);
                            if (!isNaN(humidity)) {
                                document.getElementById('humidity').innerHTML = humidity.toFixed(1) + ' <span class="card-unit">%</span>';
                                document.getElementById('humidity-progress').style.width = Math.min(100, Math.max(0, humidity)) + '%';
                                
                                let humidityLabel = 'Optimal';
                                if (humidity < 30) humidityLabel = 'Dry';
                                else if (humidity > 80) humidityLabel = 'Humid';
                                document.getElementById('humidity-label').textContent = humidityLabel;
                            } else {
                                showSensorError('humidity', 'Invalid Data');
                            }
                        } else {
                            showSensorOffline('humidity');
                        }
                        
                        // Update temperature
                        if (data.temperature !== undefined) {
                            const temperature = parseFloat(data.temperature);
                            if (!isNaN(temperature)) {
                                document.getElementById('temperature').innerHTML = temperature.toFixed(1) + ' <span class="card-unit">°C</span>';
                                // Temperature progress bar (15-35°C range)
                                const tempProgress = Math.min(100, Math.max(0, ((temperature - 15) / 20) * 100));
                                document.getElementById('temperature-progress').style.width = tempProgress + '%';
                                
                                let temperatureLabel = 'Optimal';
                                if (temperature < 15) temperatureLabel = 'Cold';
                                else if (temperature > 35) temperatureLabel = 'Hot';
                                else if (temperature < 20 || temperature > 30) temperatureLabel = 'Moderate';
                                document.getElementById('temperature-label').textContent = temperatureLabel;
                            } else {
                                showSensorError('temperature', 'Invalid Data');
                            }
                        } else {
                            showSensorOffline('temperature');
                        }
                        
                        // Update soil moisture
                        if (data.soil !== undefined) {
                            const soil = parseInt(data.soil);
                            if (!isNaN(soil)) {
                                document.getElementById('soil').innerHTML = soil + ' <span class="card-unit">%</span>';
                                document.getElementById('soil-label').textContent = data.soil_label || getSoilLabel(soil);
                                document.getElementById('soil-progress').style.width = soil + '%';
                            } else {
                                showSensorError('soil', 'Invalid Data');
                            }
                        } else {
                            showSensorOffline('soil');
                        }
                        
                        // Update light intensity
                        if (data.light !== undefined) {
                            const lightValue = parseInt(data.light);
                            if (!isNaN(lightValue)) {
                                const lightInfo = getLightInfo(lightValue);
                                document.getElementById('light').innerHTML = lightInfo.label;
                                document.getElementById('light-label').textContent = 'Light Level';
                                document.getElementById('light-progress').style.width = lightInfo.percentage + '%';
                            } else {
                                showSensorError('light', 'Invalid Data');
                            }
                        } else {
                            showSensorOffline('light');
                        }
                        
                        // Update timestamp
                        const now = new Date();
                        document.getElementById('last-update').textContent = 'Last updated: ' + now.toLocaleTimeString();
                        return;
                    }
                }
                
                // If API fails, try direct ESP32 connection as fallback
                await updateFromESP32();
                
            } catch (error) {
                console.error('Error updating sensor data:', error);
                // Try ESP32 direct connection as last resort
                await updateFromESP32();
            }
        }
        
        // Fallback function to get data directly from ESP32
        async function updateFromESP32() {
            try {
                // Update humidity
                try {
                    const humidityResponse = await fetch(`http://${ESP32_IP}/humidity`, { timeout: 3000 });
                    if (humidityResponse.ok) {
                        const humidity = parseFloat(await humidityResponse.text());
                        if (!isNaN(humidity)) {
                            document.getElementById('humidity').innerHTML = humidity.toFixed(1) + ' <span class="card-unit">%</span>';
                            document.getElementById('humidity-progress').style.width = Math.min(100, Math.max(0, humidity)) + '%';
                            
                            let humidityLabel = 'Optimal';
                            if (humidity < 30) humidityLabel = 'Dry';
                            else if (humidity > 80) humidityLabel = 'Humid';
                            document.getElementById('humidity-label').textContent = humidityLabel;
                        } else {
                            showSensorError('humidity', 'Invalid Data');
                        }
                    } else {
                        showSensorOffline('humidity');
                    }
                } catch (error) {
                    showSensorError('humidity', 'Connection Error');
                }

                // Update temperature
                try {
                    const temperatureResponse = await fetch(`http://${ESP32_IP}/temperature`, { timeout: 3000 });
                    if (temperatureResponse.ok) {
                        const temperature = parseFloat(await temperatureResponse.text());
                        if (!isNaN(temperature)) {
                            document.getElementById('temperature').innerHTML = temperature.toFixed(1) + ' <span class="card-unit">°C</span>';
                            // Temperature progress bar (15-35°C range)
                            const tempProgress = Math.min(100, Math.max(0, ((temperature - 15) / 20) * 100));
                            document.getElementById('temperature-progress').style.width = tempProgress + '%';
                            
                            let temperatureLabel = 'Optimal';
                            if (temperature < 15) temperatureLabel = 'Cold';
                            else if (temperature > 35) temperatureLabel = 'Hot';
                            else if (temperature < 20 || temperature > 30) temperatureLabel = 'Moderate';
                            document.getElementById('temperature-label').textContent = temperatureLabel;
                        } else {
                            showSensorError('temperature', 'Invalid Data');
                        }
                    } else {
                        showSensorOffline('temperature');
                    }
                } catch (error) {
                    showSensorError('temperature', 'Connection Error');
                }

                // Update soil moisture
                try {
                    const soilResponse = await fetch(`http://${ESP32_IP}/soil`, { timeout: 3000 });
                    if (soilResponse.ok) {
                        const soilData = await soilResponse.text();
                        const [soilValue, soilLabel] = soilData.split('|');
                        const soil = parseInt(soilValue);
                        
                        if (!isNaN(soil)) {
                            document.getElementById('soil').innerHTML = soil + ' <span class="card-unit">%</span>';
                            document.getElementById('soil-label').textContent = soilLabel || getSoilLabel(soil);
                            document.getElementById('soil-progress').style.width = soil + '%';
                        } else {
                            showSensorError('soil', 'Invalid Data');
                        }
                    } else {
                        showSensorOffline('soil');
                    }
                } catch (error) {
                    showSensorError('soil', 'Connection Error');
                }

                // Update light intensity
                try {
                    const lightResponse = await fetch(`http://${ESP32_IP}/light`, { timeout: 3000 });
                    if (lightResponse.ok) {
                        const lightValue = parseInt(await lightResponse.text());
                        
                        if (!isNaN(lightValue)) {
                            const lightInfo = getLightInfo(lightValue);
                            document.getElementById('light').innerHTML = lightInfo.label;
                            document.getElementById('light-label').textContent = 'Light Level';
                            document.getElementById('light-progress').style.width = lightInfo.percentage + '%';
                        } else {
                            showSensorError('light', 'Invalid Data');
                        }
                    } else {
                        showSensorOffline('light');
                    }
                } catch (error) {
                    showSensorError('light', 'Connection Error');
                }

                // Update timestamp
                const now = new Date();
                document.getElementById('last-update').textContent = 'Last updated: ' + now.toLocaleTimeString();

            } catch (error) {
                console.error('Error updating from ESP32:', error);
                showAllSensorsOffline();
            }
        }
        
        // Helper functions for sensor display
        function showSensorOffline(sensor) {
            document.getElementById(sensor).innerHTML = '<span style="color: #e74c3c;">Offline</span>';
            document.getElementById(sensor + '-label').textContent = 'Sensor Offline';
            document.getElementById(sensor + '-progress').style.width = '0%';
        }
        
        function showSensorError(sensor, message) {
            document.getElementById(sensor).innerHTML = '<span style="color: #e74c3c;">Error</span>';
            document.getElementById(sensor + '-label').textContent = message;
            document.getElementById(sensor + '-progress').style.width = '0%';
        }
        
        function showAllSensorsOffline() {
            showSensorOffline('humidity');
            showSensorOffline('temperature');
            showSensorOffline('soil');
            showSensorOffline('light');
        }
        
        function getSoilLabel(soil) {
            if (soil < 20) return 'Very Dry';
            if (soil < 40) return 'Dry';
            if (soil >= 40 && soil <= 70) return 'Optimal';
            return 'Wet';
        }
        
        function getLightInfo(lightValue) {
            if (lightValue > 3000) {
                return { label: 'Dark', percentage: 10 };
            } else if (lightValue > 2000) {
                return { label: 'Dim', percentage: 30 };
            } else if (lightValue > 1000) {
                return { label: 'Moderate', percentage: 60 };
            } else if (lightValue > 500) {
                return { label: 'Bright', percentage: 80 };
            } else {
                return { label: 'Very Bright', percentage: 100 };
            }
        }

        // Helper functions for history table status indicators
        function getHumidityStatus(humidity) {
            if (humidity < 30) return { status: 'low', color: '#e74c3c', icon: 'fas fa-exclamation-triangle' };
            if (humidity > 80) return { status: 'high', color: '#9b59b6', icon: 'fas fa-exclamation-triangle' };
            if (humidity >= 40 && humidity <= 70) return { status: 'optimal', color: '#27ae60', icon: 'fas fa-check-circle' };
            return { status: 'moderate', color: '#f39c12', icon: 'fas fa-info-circle' };
        }

        function getTemperatureStatus(temperature) {
            if (temperature < 15) return { status: 'cold', color: '#3498db', icon: 'fas fa-thermometer-empty' };
            if (temperature > 35) return { status: 'hot', color: '#e74c3c', icon: 'fas fa-thermometer-full' };
            if (temperature >= 20 && temperature <= 30) return { status: 'optimal', color: '#27ae60', icon: 'fas fa-thermometer-half' };
            return { status: 'moderate', color: '#f39c12', icon: 'fas fa-thermometer-quarter' };
        }

        function getSoilStatus(soil) {
            if (soil < 20) return { status: 'very_dry', color: '#e74c3c', icon: 'fas fa-exclamation-triangle' };
            if (soil < 40) return { status: 'dry', color: '#f39c12', icon: 'fas fa-exclamation-circle' };
            if (soil >= 40 && soil <= 70) return { status: 'optimal', color: '#27ae60', icon: 'fas fa-check-circle' };
            return { status: 'wet', color: '#3498db', icon: 'fas fa-info-circle' };
        }

        function getLightColor(lightValue) {
            if (lightValue > 3000) return '#2c3e50'; // Dark
            if (lightValue > 2000) return '#7f8c8d'; // Dim
            if (lightValue > 1000) return '#f39c12'; // Moderate
            if (lightValue > 500) return '#f1c40f'; // Bright
            return '#f39c12'; // Very Bright
        }

        // Toggle relay control
        async function toggleRelay(relay, state) {
            const statusElement = document.getElementById('status' + relay);
            statusElement.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Updating...';
            statusElement.className = 'status status-waiting';
            
            try {
                // First try using the API endpoint for better error handling
                const apiResponse = await fetch('api.php/control', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        relay: relay,
                        state: state
                    })
                });
                
                if (apiResponse.ok) {
                    const apiData = await apiResponse.json();
                    
                    if (apiData.status === 'success') {
                        statusElement.className = 'status';
                        if (state === 'on') {
                            statusElement.classList.add('status-on');
                            statusElement.innerHTML = '<i class="fas fa-check-circle"></i> ' + apiData.data.response;
                        } else {
                            statusElement.classList.add('status-off');
                            statusElement.innerHTML = '<i class="fas fa-times-circle"></i> ' + apiData.data.response;
                        }
                        return;
                    } else {
                        throw new Error(apiData.message || 'API Error');
                    }
                } else {
                    throw new Error(`API Error: ${apiResponse.status}`);
                }
            } catch (apiError) {
                console.warn('API control failed, trying direct ESP32 connection:', apiError);
                
                // Fallback to direct ESP32 connection
                try {
                    const response = await fetch(`http://${ESP32_IP}/control?relay=${relay}&state=${state}`, {
                        timeout: 5000
                    });
                    
                    if (response.ok) {
                        const message = await response.text();
                        
                        statusElement.className = 'status';
                        if (state === 'on') {
                            statusElement.classList.add('status-on');
                            statusElement.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
                        } else {
                            statusElement.classList.add('status-off');
                            statusElement.innerHTML = '<i class="fas fa-times-circle"></i> ' + message;
                        }
                    } else {
                        throw new Error(`ESP32 Error: ${response.status}`);
                    }
                } catch (esp32Error) {
                    console.error('Both API and ESP32 connections failed:', esp32Error);
                    statusElement.className = 'status status-off';
                    statusElement.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Connection Error - Check ESP32';
                }
            }
        }

        // Update history table with only 5 recent records (already sorted by server with latest first)
        async function updateHistory() {
            try {
                const response = await fetch('history.php?limit=5');
                const result = await response.json();
                
                const tbody = document.getElementById('history-body');
                tbody.innerHTML = '';
                
                // Handle the new response format from history.php
                if (result.status === 'error') {
                    tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 20px; color: red;">${result.message}</td></tr>`;
                    return;
                }
                
                const data = result.data || [];
                
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 20px;">No data available</td></tr>';
                    return;
                }
                
                // Data is already sorted by server with latest records first (created_at DESC)
                data.forEach((row, index) => {
                    const tr = document.createElement('tr');
                    tr.style.opacity = '0';
                    tr.style.transform = 'translateY(20px)';
                    
                    // Add status indicators with colors
                    const humidityStatus = getHumidityStatus(row.humidity);
                    const temperatureStatus = getTemperatureStatus(row.temperature);
                    const soilStatus = getSoilStatus(row.soil);
                    
                    
                    tr.innerHTML = `
                        <td>
                            <div class="sensor-value">
                                <span class="value">${row.humidity}%</span>
                                <i class="${humidityStatus.icon}" style="color: ${humidityStatus.color}; margin-left: 8px;"></i>
                            </div>
                        </td>
                        <td>
                            <div class="sensor-value">
                                <span class="value">${row.temperature}°C</span>
                                <i class="${temperatureStatus.icon}" style="color: ${temperatureStatus.color}; margin-left: 8px;"></i>
                            </div>
                        </td>
                        <td>
                            <div class="sensor-value">
                                <span class="value">${row.soil}%</span>
                                <i class="${soilStatus.icon}" style="color: ${soilStatus.color}; margin-left: 8px;"></i>
                            </div>
                        </td>
                        <td>
                            <div class="light-indicator">
                                <span class="light-value">${row.light}</span>
                                <i class="fas fa-sun" style="color: ${getLightColor(row.light_value || row.light)}; margin-left: 8px;"></i>
                            </div>
                        </td>
                        <td>
                            <div class="timestamp">
                                <div class="time">${row.formatted_time || row.created_at}</div>
                                <div class="time-ago">${row.time_ago || 'just now'}</div>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                    
                    // Animate row appearance
                    setTimeout(() => {
                        tr.style.transition = 'all 0.3s ease';
                        tr.style.opacity = '1';
                        tr.style.transform = 'translateY(0)';
                    }, index * 100);
                });
            } catch (error) {
                console.error('Error updating history:', error);
                document.getElementById('history-body').innerHTML = 
                    '<tr><td colspan="5" style="text-align: center; padding: 20px; color: red;">Error loading data: ' + error.message + '</td></tr>';
            }
        }

        // Check system connectivity status
        async function checkSystemStatus() {
            try {
                const response = await fetch('api.php/status');
                if (response.ok) {
                    const data = await response.json();
                    if (data.status === 'success') {
                        const esp32Status = data.data.esp32.status;
                        const statusIndicator = document.querySelector('.status-indicator');
                        const statusDot = document.querySelector('.status-dot');
                        const statusText = statusIndicator.querySelector('span:last-child');
                        
                        if (esp32Status === 'online') {
                            statusDot.style.background = 'var(--success-green)';
                            statusText.textContent = 'System Online';
                        } else {
                            statusDot.style.background = 'var(--warning-orange)';
                            statusText.textContent = 'ESP32 Offline';
                        }
                    }
                }
            } catch (error) {
                console.warn('Could not check system status:', error);
            }
        }

        // Initialize and start auto-refresh
        document.addEventListener('DOMContentLoaded', function() {
            updateSensorData();
            updateHistory();
            checkSystemStatus();
            
            setInterval(updateSensorData, REFRESH_INTERVAL);
            setInterval(updateHistory, REFRESH_INTERVAL); // Update history with same frequency as sensor data
            setInterval(checkSystemStatus, REFRESH_INTERVAL * 5); // Check status every 10 seconds
        });

        // Button click animations
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('mousedown', function() {
                this.style.transform = 'scale(0.95)';
            });
            button.addEventListener('mouseup', function() {
                this.style.transform = '';
            });
            button.addEventListener('mouseleave', function() {
                this.style.transform = '';
            });
        });
    </script>
</body>
</html>
