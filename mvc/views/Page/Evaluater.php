<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách đánh giá mới nhất</title>
    <link rel="stylesheet" href="/QAReviewer/public/css/Evaluation.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        window.evaluations = <?php echo json_encode($data["evaluations"]); ?>;
        window.totalPages = <?php echo json_encode($data["totalPages"]); ?>;
        window.currentPage = <?php echo json_encode($data["currentPage"]); ?>;
        window.role = <?php echo json_encode($data["role"]); ?>;
        window.userID = <?php echo json_encode($data["userID"]); ?>;
        window.username = <?php echo json_encode($data["username"]); ?>;
        window.userEvaluations = <?php echo json_encode($data["userEvaluations"] ?? []); ?>;
    </script>
</head>
<body>
    <div class="container">
        <div class="header-section">
            <h1>Danh sách đánh giá mới nhất</h1>
            <?php if ($data["role"] === 'Evaluater' || $data["role"] === 'Admin'): ?>
                <button id="viewUserEvaluationsBtn" class="view-user-evaluations-btn">Xem câu trả lời đã đánh giá</button>
            <?php endif; ?>
        </div>
        <div class="qa-section">
            <div class="evaluation-list-wrapper">
                <div id="evaluationList" class="evaluation-list"></div>
                <div class="pagination"></div>
            </div>
            <?php if ($data["role"] === 'Evaluater' || $data["role"] === 'Admin'): ?>
                <div class="user-evaluations-wrapper" id="userEvaluationsWrapper" style="display: none;">
                    <h2>Đánh giá của bạn</h2>
                    <div id="userEvaluations" class="user-evaluations"></div>
                </div>
            <?php endif; ?>
            <div id="questionDetails" class="question-details" style="display: none;">
                <h2>Chi tiết câu hỏi</h2>
                <div id="questionContent"></div>
            </div>
        </div>
    </div>
    <script src="/QAReviewer/public/js/CommonUtils.js"></script>
    <script src="/QAReviewer/public/js/Evaluation.js"></script>
</body>
</html>