<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ - QAReviewer</title>
    <link rel="stylesheet" href="/QAReviewer/public/css/Home.css">
</head>
<body>
    <div class="container">
        <div class="hero">
            <h1>Chào mừng đến với QAReviewer</h1>
            <p>Diễn đàn hỏi đáp dành cho mọi người</p>
        </div>

        <!-- Menu điều hướng -->
        <div class="navigation">
            <?php
            echo '<a href="/QAReviewer/Questions/List" class="nav-link">Các câu hỏi phổ biến</a>';
            echo '<a href="/QAReviewer/Answers/Latest" class="nav-link">Các câu hỏi liên quan</a>';
            if (isset($_SESSION['role'])) {
                $role = $_SESSION['role'];
                if ($role === 'Admin' || $role === 'Questioner' || $role === 'Evaluater' || $role === 'Answerer') {
                }
            }
            ?>
        </div>

        <!-- Phần hiển thị câu hỏi và câu trả lời -->
        <div class="qa-section">
            <div class="question-list" id="questionList">
                <!-- JS sẽ render ở đây -->
            </div>
        </div>

        <script>
            // Inject dữ liệu từ PHP vào JS
            window.questions = <?= $data["AskAndAnswerData"] ?>;
        </script>
        
            <script src="/QAReviewer/public/js/Home.js"></script>
    </div>


</body>
</html>