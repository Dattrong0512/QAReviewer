<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ - QAReviewer</title>
    <link rel="stylesheet" href="/QAReviewer/public/css/Home.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
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
            ?>
        </div>
        <div class="search-container">
                <input type="text" id="searchInput" placeholder="Tìm kiếm câu hỏi..." class="search-box">
            </div>
        <!-- Phần hiển thị câu hỏi và câu trả lời -->
        <div class="qa-section">
           
            <div class="question-list-wrapper">
                <div class="question-list" id="questionList">
                    <!-- JS sẽ render ở đây -->
                </div>
                <!-- Phân trang -->
                <div class="pagination">
                    <?php
                    $totalPages = $data['TotalPages'];
                    $currentPage = $data['CurrentPage'];
                    for ($i = 1; $i <= $totalPages; $i++) {
                        $activeClass = $i === $currentPage ? 'active' : '';
                        echo "<button class='page-btn $activeClass' data-page='$i'>$i</button>";
                    }
                    ?>
                </div>
            </div>
        </div>

        <script>
            // Inject dữ liệu từ PHP vào JS
            window.questions = <?= $data["AskAndAnswerData"] ?>;
        </script>
        <script type="text/javascript" language="javascript" src="/QAReviewer/public/js/Home.js"></script>
    </div>
</body>
</html>