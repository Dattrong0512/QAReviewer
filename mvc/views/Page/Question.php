<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ - QAReviewer</title>
    <link rel="stylesheet" href="/QAReviewer/public/css/Question.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>

<body>
    <div class="container">
        <!-- Menu điều hướng -->
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
                    $totalPages = json_decode($data['totalPages']); // Parse từ JSON
                    $currentPage = json_decode($data['currentPage']); // Parse từ JSON
                    for ($i = 1; $i <= $totalPages; $i++) {
                        $activeClass = $i === $currentPage ? 'active' : '';
                        echo "<button class='page-btn $activeClass' data-page='$i'>$i</button>";
                    }
                    ?>
                </div>
            </div>
            <div class="tag-list" id="tagList">
                <!-- Danh sách tag sẽ được render bởi JS -->
            </div>
        </div>

        <script>
            // Inject dữ liệu từ PHP vào JS
            window.questions = <?php echo $data["AskAndAnswerData"] ?? json_encode([]); ?>;
            window.currentPage = <?php echo $data['currentPage'] ?? json_encode(1); ?>;
            window.totalPages = <?php echo $data['totalPages'] ?? json_encode(1); ?>;
            window.role = <?php echo json_encode($_SESSION['role'] ?? null); ?>;
            window.userID = <?php echo json_encode($_SESSION['userID'] ?? null); ?>;
            window.username = <?php echo json_encode($_SESSION['username'] ?? null); ?>;
        </script>
        <script type="text/javascript" language="javascript" src="/QAReviewer/public/js/Question.js"></script>
    </div>
</body>

</html>