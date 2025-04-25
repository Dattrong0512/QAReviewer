
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách trả lời mới nhất</title>
    <link rel="stylesheet" href="/QAReviewer/public/css/Answer.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Truyền dữ liệu từ PHP sang JavaScript
        window.answers = <?php echo json_encode($data["answers"]) ?>;
        window.totalPages = <?php echo json_encode($data["totalPages"]); ?>;
        window.currentPage = <?php echo json_encode($data["currentPage"]); ?>;
        window.role = <?php echo json_encode($data["role"]); ?>;
        window.userID = <?php echo json_encode($data["userID"]); ?>;
        window.username = <?php echo json_encode($data["username"]); ?>;
    </script>
</head>
<body>
    <div class="container">
        <h1>Danh sách trả lời mới nhất</h1>
        <div class="qa-section">
            <div class="answer-list-wrapper">
                <div id="answerList" class="answer-list"></div>
                <div class="pagination"></div>
            </div>
            <div id="questionDetails" class="question-details">
                <h2>Chi tiết câu hỏi</h2>
                <div id="questionContent"></div>
            </div>
        </div>
    </div>
    <script src="/QAReviewer/public/js/Answer.js"></script>
</body>
</html>
