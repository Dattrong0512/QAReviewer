<?php
class Answer extends Controller
{
    public function sendResponse($success, $message = '')
    {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message
        ]);
        exit;
    }
    public function Create()
    {
        ob_start();
        session_start();

        // Log session để debug
        error_log("Session role: " . ($_SESSION['role'] ?? 'Không có'));
        error_log("Session userID: " . ($_SESSION['userID'] ?? 'Không có'));

        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            ob_end_clean();
            $this->sendResponse(false, 'Yêu cầu không hợp lệ: Chỉ hỗ trợ AJAX');
        }

        $questionId = $_POST['questionId'] ?? null;
        $answer = $_POST['answerText'] ?? null;
        $userId = $_SESSION['userID'] ?? null;

        if (empty($questionId) || empty($answer) || empty($userId)) {
            ob_end_clean();
            $this->sendResponse(false, 'Yêu cầu không hợp lệ: Thiếu thông tin cần thiết');
        }

        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Answerer'])) {
            ob_end_clean();
            $this->sendResponse(false, 'Bạn không có quyền thực hiện hành động này.');
        }

        $model = $this->model('QuestionAndAnswerModel');
        try {
            $result = $model->AddAnswerForQuestion($questionId, $answer, $userId);
            if ($result) {
                ob_end_clean();
                $this->sendResponse(true, 'Câu trả lời đã được thêm thành công!');
            } else {
                ob_end_clean();
                $this->sendResponse(false, 'Lỗi khi thêm câu trả lời vào cơ sở dữ liệu.');
            }
        } catch (Exception $e) {
            ob_end_clean();
            error_log("Lỗi trong Create: " . $e->getMessage());
            $this->sendResponse(false, 'Lỗi không xác định: ' . $e->getMessage());
        }
    }
}
