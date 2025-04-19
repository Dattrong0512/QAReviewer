<!-- filepath: c:\xampp\htdocs\QAReviewer\mvc\views\Block\Header.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Header</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }
        .header {
            background-color: #007bff;
            color: #ffffff;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header .logo {
            font-size: 24px;
            font-weight: bold;
        }
        .header nav {
            display: flex;
            gap: 15px;
        }
        .header nav a {
            color: #ffffff;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
        }
        .header nav a:hover {
            text-decoration: underline;
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