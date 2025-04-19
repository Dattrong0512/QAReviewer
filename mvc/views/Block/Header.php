<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Header</title>
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
            font-size: 16px;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 20px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .header nav a:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">QAReviewer</div>
        <nav>
            <a href="/QAReviewer/Home">Home</a>
            <a href="/QAReviewer/About">About</a>
            <?php if (isset($_SESSION['username'])): ?>
                <a href="/QAReviewer/Auth/Logout">Logout</a>
            <?php else: ?>
                <a href="/QAReviewer/Auth/Register">Register</a>
                <a href="/QAReviewer/Auth/Login">Login</a>
            <?php endif; ?>
        </nav>
    </div>
</body>
</html>