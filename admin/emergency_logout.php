<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Logout - Orlando International Resorts</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logout-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        .logout-title {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }
        .logout-text {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            margin: 10px;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        .logout-btn:hover {
            background: #c82333;
            color: white;
            text-decoration: none;
        }
        .back-btn {
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            margin: 10px;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        .back-btn:hover {
            background: #5a6268;
            color: white;
            text-decoration: none;
        }
        .icon {
            font-size: 3rem;
            color: #dc3545;
            margin-bottom: 20px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="logout-container">
        <div class="icon">
            <i class="fas fa-sign-out-alt"></i>
        </div>
        
        <h1 class="logout-title">Emergency Logout</h1>
        
        <p class="logout-text">
            This is an emergency logout page. Use this if you're having trouble accessing the normal logout options in the admin interface.
        </p>
        
        <p class="logout-text">
            <strong>Click the button below to immediately logout and return to the login page.</strong>
        </p>
        
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            Logout Now
        </a>
        
        <a href="home.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 0.9rem; color: #999;">
            Orlando International Resorts - Admin System<br>
            Emergency Access Page
        </div>
    </div>
</body>
</html>
