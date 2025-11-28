<?php
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - Student Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .logout-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .logout-card {
            background: white;
            padding: 60px 50px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 500px;
            width: 90%;
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logout-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: wave 1s ease-in-out infinite;
        }

        @keyframes wave {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-10deg); }
            75% { transform: rotate(10deg); }
        }

        .logout-title {
            color: #2D3748;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .logout-message {
            color: #718096;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .logout-btn {
            display: inline-block;
            background: linear-gradient(135deg, #FFB84D 0%, #F2994A 100%);
            color: white;
            padding: 15px 40px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(255, 184, 77, 0.4);
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 184, 77, 0.5);
        }

        .features-list {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #E2E8F0;
            text-align: left;
        }

        .features-list h3 {
            color: #4A5568;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            text-align: center;
        }

        .features-list ul {
            list-style: none;
            padding: 0;
        }

        .features-list li {
            color: #718096;
            font-size: 14px;
            margin-bottom: 10px;
            padding-left: 25px;
            position: relative;
        }

        .features-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #48BB78;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="logout-container">
    <div class="logout-card">
        <div class="logout-icon">👋</div>
        <h1 class="logout-title">Goodbye!</h1>
        <p class="logout-message">
            You have been successfully logged out from the Student Management System.
            <br>Thank you for using our platform!
        </p>
        
        <a href="index.php" class="logout-btn">
            🏠 Return to Dashboard
        </a>

        <div class="features-list">
            <h3>System Features</h3>
            <ul>
                <li>Student Management</li>
                <li>Attendance Tracking</li>
                <li>Session Management</li>
                <li>Reports & Analytics</li>
            </ul>
        </div>

        <p style="margin-top: 30px; color: #A0AEC0; font-size: 12px;">
            © <?php echo date('Y'); ?> Student Dashboard — Advanced Web Programming
        </p>
    </div>
</div>

</body>
</html>