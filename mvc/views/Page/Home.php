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
            <h1 data-text="Chào mừng đến với QAReviewer">Chào mừng đến với QAReviewer</h1>
            <p data-text="Diễn đàn hỏi đáp dành cho mọi người">Diễn đàn hỏi đáp dành cho mọi người</p>
            <div class="action-buttons">
                <?php
                $actionLinks = [
                    ['text' => 'Danh sách câu hỏi', 'href' => '/QAReviewer/Question/List'],
                    ['text' => 'Trả lời mới', 'href' => '/QAReviewer/Answer/ListAnswerNewest'],
                    ['text' => 'Đánh giá mới', 'href' => '/QAReviewer/Review/New']
                ];
                if (isset($_SESSION['role'])) {
                    if ($_SESSION['role'] === 'Questioner' || $_SESSION['role'] === 'Admin') {
                        array_unshift($actionLinks, ['text' => 'Đặt câu hỏi', 'href' => '/QAReviewer/Question/Create']);
                    }
                }
                
                foreach ($actionLinks as $link): ?>
                    <a href="<?php echo htmlspecialchars($link['href']); ?>" class="action-button">
                        <?php echo htmlspecialchars($link['text']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>