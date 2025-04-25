<?php
class Evaluater extends Controller
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
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            $this->sendResponse(false, 'Yêu cầu không hợp lệ.');
            return;
        }
    
        ob_start();
        session_start();
    
        try {
            $model = $this->model("AnswerEvaluaterModel");
            $answerId = trim($_POST['answerId'] ?? '');
            $rating = trim($_POST['rating'] ?? '');
    
            if (empty($answerId) || empty($rating)) {
                ob_end_clean();
                $this->sendResponse(false, 'Yêu cầu không hợp lệ: Thiếu thông tin cần thiết');
                return;
            }
    
            $result = $model->AddAnswerForQuestion($answerId, $rating);
            if ($result === false) {
                ob_end_clean();
                $this->sendResponse(false, 'Lỗi khi thêm đánh giá vào cơ sở dữ liệu.');
                return;
            }
    
            ob_end_clean();
            $this->sendResponse(true, 'Đánh giá thành công');
        } catch (Exception $e) {
            ob_end_clean();
            $this->sendResponse(false, 'Lỗi khi455 đánh giá câu trả lời: ' . $e->getMessage());
        }
    }
}
