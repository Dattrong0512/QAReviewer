<?php
class Answer extends Controller
{
    public function sendResponse($success, $message = '', $data = [])
    {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }

    public function Create()
    {
        ob_start();
        session_start();

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

    public function ListAnswerNewest()
    {

        $model = $this->model('QuestionAndAnswerModel');

        $itemsPerPage = 10;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $itemsPerPage;

        $answers = $model->GetAllNewestAnswer($offset, $itemsPerPage);
        $totalAnswers = $model->GetTotalAnswers();
        $totalPages = ceil($totalAnswers / $itemsPerPage);

        $this->view("Layout/MainLayout", [
            "Page" => "Page/Answer",
            "answers" => $answers,
            "totalPages" => $totalPages,
            "currentPage" => $page,
            "role" => $_SESSION['role'] ?? null,
            "userID" => $_SESSION['userID'] ?? null,
            "username" => $_SESSION['username'] ?? null
        ]);
    }

    public function GetNewestAnswersJson()
    {
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            $this->sendResponse(false, 'Yêu cầu không hợp lệ: Chỉ hỗ trợ AJAX');
        }

        $itemsPerPage = 10;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $itemsPerPage;

        $model = $this->model('QuestionAndAnswerModel');
        $answers = $model->GetAllNewestAnswer($offset, $itemsPerPage);
        $totalAnswers = $model->GetTotalAnswers();
        $totalPages = ceil($totalAnswers / $itemsPerPage);

        $this->sendResponse(true, 'Lấy dữ liệu thành công', [
            'answers' => $answers,
            'totalPages' => $totalPages,
            'currentPage' => $page
        ]);
    }

    public function GetQuestionDetails()
    {
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            $this->sendResponse(false, 'Yêu cầu không hợp lệ: Chỉ hỗ trợ AJAX');
        }

        $questionId = $_GET['questionId'] ?? null;
        if (empty($questionId)) {
            $this->sendResponse(false, 'Thiếu QuestionID');
        }

        $model = $this->model('QuestionAndAnswerModel');
        $questions = $model->GetQuestionById($questionId);

        if (empty($questions)) {
            $this->sendResponse(false, 'Không tìm thấy câu hỏi');
        }

        $this->sendResponse(true, 'Lấy dữ liệu thành công', ['questions' => $questions]);
    }
}
