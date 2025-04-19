<!-- filepath: c:\xampp\htdocs\QAReviewer\mvc\views\Block\Footer.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Footer</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .footer {
            background-color: #333333;
            color: #ffffff;
            text-align: center;
            padding: 15px 0;
            position: relative;
            bottom: 0;
            width: 100%;
            margin-top: auto;
        }
        .footer a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }
        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="footer">
        <p>&copy; 2025 QAReviewer. All rights reserved.</p>
        <p>
            <a href="/QAReviewer/About">About Us</a> | 
            <a href="/QAReviewer/Contact">Contact</a>
        </p>
    </div>
</body>
</html>