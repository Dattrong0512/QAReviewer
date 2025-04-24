<?php
class Answer extends Controller
{
    public function create()
    {
        // Đảm bảo session đã được khởi tạo
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Khởi tạo model
        $model = $this->model("QuestionAndAnswerModel");

        // Kiểm tra người dùng đã đăng nhập và có vai trò phù hợp
        if (!isset($_SESSION['Role']) || !in_array($_SESSION['Role'], ['Answerer', 'Admin'])) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Bạn không có quyền trả lời. Vui lòng đăng nhập với vai trò Answerer hoặc Admin.'
                ]);
                exit;
            }
            $this->view("Layout/MainLayout", [
                "Page" => "Page/Error",
                "ErrorMessage" => "Bạn không có quyền trả lời."
            ]);
            return;
        }

        // Kiểm tra userID và userName trong session
        if (!isset($_SESSION['userID']) || !isset($_SESSION['userName'])) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Thông tin người dùng không hợp lệ. Vui lòng đăng nhập lại.'
                ]);
                exit;
            }
        }

        // Lấy dữ liệu từ request (POST)
        $input = json_decode(file_get_contents('php://input'), true);
        $questionId = $input['questionId'] ?? null;
        $answerText = $input['text'] ?? null;

        // Log dữ liệu đầu vào
        error_log("Answer Create - Input: " . print_r($input, true));

        // Kiểm tra dữ liệu đầu vào
        if (!$questionId || !$answerText) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ. Vui lòng cung cấp QuestionID và nội dung câu trả lời.'
                ]);
                exit;
            }
        }

        // Chuẩn bị dữ liệu
        $userId = $_SESSION['userID'];
        $userName = $_SESSION['userName'];

        try {
            // Gọi model để chèn câu trả lời
            $newAnswerID = $model->AddAnswerForQuestion($questionId, $answerText, $userId);

            // Log kết quả
            error_log("Answer Create - New AnswerID: $newAnswerID, QuestionID: $questionId, UserID: $userId");

            // Chuẩn bị dữ liệu phản hồi
            $response = [
                'success' => true,
                'answer' => [
                    'id' => $newAnswerID,
                    'questionId' => $questionId,
                    'text' => $answerText,
                    'reference' => 'Source 1',
                    'answerer' => $userName,
                    'userId' => $userId,
                    'createdDate' => date('Y-m-d H:i:s'),
                    'numberEvaluators' => 0,
                    'averageRating' => 0.0,
                    'evaluations' => []
                ]
            ];

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;
            }

            $this->view("Layout/MainLayout", [
                "Page" => "Page/Question",
                "Message" => "Câu trả lời đã được gửi thành công.",
                "AskAndAnswerData" => json_encode($response),
                "TotalPages" => 1,
                "CurrentPage" => 1
            ]);
        } catch (Exception $e) {
            error_log("Answer Create - Error: " . $e->getMessage());

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Lỗi khi lưu câu trả lời: ' . $e->getMessage()
                ]);
                exit;
            }
        }
    }
}
