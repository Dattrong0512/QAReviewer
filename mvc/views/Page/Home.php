<!-- filepath: c:\xampp\htdocs\QAReviewer\mvc\views\Page\Home.php -->
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ - QAReviewer</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .hero {
            background-color: #007bff;
            color: #ffffff;
            padding: 50px 20px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .hero h1 {
            font-size: 36px;
            margin: 0;
        }
        .hero p {
            font-size: 18px;
            margin: 10px 0 0;
        }
        .navigation {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .nav-link {
            background: #ffffff;
            border: 1px solid #dddddd;
            border-radius: 8px;
            padding: 15px 20px;
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s ease;
        }
        .nav-link:hover {
            background-color: #f0f0f0;
        }
        .features {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 40px;
        }
        .feature {
            background: #ffffff;
            border: 1px solid #dddddd;
            border-radius: 8px;
            padding: 20px;
            flex: 1;
            min-width: 250px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .feature h3 {
            font-size: 20px;
            margin-bottom: 10px;
        }
        .feature p {
            font-size: 14px;
            color: #555555;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="hero">
            <h1>Chào mừng đến với QAReviewer</h1>
            <p>Diễn đàn hỏi đáp dành cho mọi người</p>
        </div>

        <!-- Menu điều hướng theo vai trò -->
        <div class="navigation">
            <?php
            // Kiểm tra vai trò người dùng từ session
            if (isset($_SESSION['role'])) {
                $role = $_SESSION['role'];
                if ($role === 'Admin') {
                    echo '<a href="/QAReviewer/Questions/Ask" class="nav-link">Đặt câu hỏi</a>';
                    echo '<a href="/QAReviewer/Questions/List" class="nav-link">Danh sách câu hỏi</a>';
                    echo '<a href="/QAReviewer/Answers/Latest" class="nav-link">Danh sách câu trả lời mới nhất</a>';
                    echo '<a href="/QAReviewer/Evaluations/Latest" class="nav-link">Danh sách đánh giá mới nhất</a>';
                    echo '<a href="/QAReviewer/Role/Change" class="nav-link">Đổi vai trò</a>';
                } elseif ($role === 'Questioner') {
                    echo '<a href="/QAReviewer/Questions/Ask" class="nav-link">Đặt câu hỏi</a>';
                    echo '<a href="/QAReviewer/Questions/List" class="nav-link">Danh sách câu hỏi</a>';
                } elseif ($role === 'Answerer') {
                    echo '<a href="/QAReviewer/Questions/List" class="nav-link">Danh sách câu hỏi</a>';
                } elseif ($role === 'Evaluater') {
                    echo '<a href="/QAReviewer/Questions/List" class="nav-link">Danh sách câu hỏi</a>';
                    echo '<a href="/QAReviewer/Answers/Latest" class="nav-link">Danh sách câu trả lời mới nhất</a>';
                    echo '<a href="/QAReviewer/Evaluations/Latest" class="nav-link">Danh sách đánh giá mới nhất</a>';
                }
            } else {
                // Viewer (không đăng nhập)
                echo '<a href="/QAReviewer/Questions/List" class="nav-link">Danh sách câu hỏi</a>';
                echo '<a href="/QAReviewer/Answers/Latest" class="nav-link">Danh sách câu trả lời mới nhất</a>';
                echo '<a href="/QAReviewer/Evaluations/Latest" class="nav-link">Danh sách đánh giá mới nhất</a>';
            }
            ?>
        </div>

        <!-- Thông tin bổ sung -->
        <div class="features">
            <div class="feature">
                <h3>Tính năng chính</h3>
                <p>Xem và tương tác với các câu hỏi, câu trả lời từ cộng đồng.</p>
            </div>
            <div class="feature">
                <h3>Tham gia ngay</h3>
                <p>Đăng ký để đặt câu hỏi, trả lời và đánh giá nội dung.</p>
            </div>
        </div>
    </div>
</body>
</html>