<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Header</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f5f7fa;
        }
        .header {
            background: linear-gradient(90deg, #007bff, #00c4ff);
            color: #ffffff;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            width: 100%;
            box-sizing: border-box; /* Đảm bảo padding không làm tăng chiều rộng */
        }
        .header .logo {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .header nav {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .header nav a {
            color: #ffffff;
            text-decoration: none;
            font-size: 18px;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 20px;
            font-family: 'Segoe UI', Arial, sans-serif;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .header nav a:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        /* Styling cho user info */
        .user-info {
            position: relative;
            display: inline-flex;
            align-items: center;
            padding: 8px;
            border-radius: 50%;
            transition: background-color 0.3s ease;
        }
        .user-info:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        .user-info .fa-user {
            font-size: 20px;
            color: #ffffff;
        }
        .user-info .info-box {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: #ffffff;
            color: #333;
            padding: 10px 15px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: opacity 0.3s ease, transform 0.3s ease, visibility 0.3s ease;
            min-width: 150px;
            text-align: left;
            z-index: 100;
            font-family: 'Roboto', 'Arial', sans-serif;
            font-size: 16px;
            font-weight: 600;
        }
        .user-info:hover .info-box {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .user-info .info-box p {
            margin: 5px 0;
        }
        .user-info .info-box .label {
            color: #555;
            font-weight: 700;
        }
        .user-info .info-box .value {
            color: #6495ED;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">QAReviewer</div>
        <nav>
            <a href="/QAReviewer/Home">Home</a>

            <?php if (isset($_SESSION['username'])): ?>          
                <a href="/QAReviewer/Auth/Logout">Logout</a>
                <div class="user-info">
                    <i class="fa fa-user"></i>
                    <div class="info-box">
                        <p>
                            <span class="label">User: </span>
                            <span class="value"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        </p>
                        <p>
                            <span class="label">Role: </span>
                            <span class="value"><?php echo htmlspecialchars($_SESSION['role'] ?? 'User'); ?></span>
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <a href="/QAReviewer/Auth/Login">Login</a>               
            <?php endif; ?>
        </nav>
    </div>
</body>
</html>