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

    private function normalizeRating($rating)
    {
        // Chuẩn hóa giá trị rating (5STAR -> 5, 4STAR -> 4, v.v.)
        $rating = trim($rating);
        $normalized = floatval(preg_replace('/[^0-9.]/', '', $rating));
        
        // Đảm bảo giá trị nằm trong khoảng 1-5
        if ($normalized < 1 || $normalized > 5) {
            return 0; // Trả về 0 nếu giá trị không hợp lệ
        }
        return $normalized;
    }

    private function groupQuestionDetails($rows)
    {
        if (empty($rows)) return [];

        $questions = [];
        foreach ($rows as $row) {
            $qId = $row['QuestionID'];
            if (!isset($questions[$qId])) {
                $questions[$qId] = [
                    "QuestionID" => $qId,
                    "Question" => $row['Question'],
                    "Tags" => $row['Tags'],
                    "UserName" => $row['UserName'] ?? 'Ẩn danh',
                    "CreatedDate" => $row['CreatedDate'],
                    "Answers" => []
                ];
            }

            if (empty($row['AnswerID'])) continue;

            $aId = $row['AnswerID'];
            if (!isset($questions[$qId]['Answers'][$aId])) {
                // Thu thập tất cả đánh giá cho câu trả lời này
                $evaluations = [];
                foreach ($rows as $r) {
                    if ($r['AnswerID'] == $aId && !empty($r['EvaluatorUserName']) && !empty($r['RateCategory'])) {
                        $normalizedRating = $this->normalizeRating($r['RateCategory']);
                        if ($normalizedRating > 0) { // Chỉ thêm nếu rating hợp lệ
                            $evaluations[] = [
                                "EvaluatorUserName" => $r['EvaluatorUserName'],
                                "RateCategory" => $normalizedRating
                            ];
                        }
                    }
                }

                // Gom nhóm đánh giá theo EvaluatorUserName và tính trung bình cho mỗi người
                $evaluatorRatings = [];
                foreach ($evaluations as $eval) {
                    $evaluator = $eval['EvaluatorUserName'];
                    if (!isset($evaluatorRatings[$evaluator])) {
                        $evaluatorRatings[$evaluator] = [];
                    }
                    $evaluatorRatings[$evaluator][] = $eval['RateCategory'];
                }

                // Tính trung bình cho từng evaluator
                $averagePerEvaluator = [];
                foreach ($evaluatorRatings as $evaluator => $ratings) {
                    $average = array_sum($ratings) / count($ratings);
                    $averagePerEvaluator[] = [
                        "EvaluatorUserName" => $evaluator,
                        "RateCategory" => $average
                    ];
                }

                // Tính số lượng người đánh giá duy nhất
                $numberEvaluators = count($averagePerEvaluator);

                // Tính trung bình tổng từ các trung bình của từng evaluator
                $averageRating = $numberEvaluators
                    ? array_sum(array_column($averagePerEvaluator, 'RateCategory')) / $numberEvaluators
                    : 0;

                $questions[$qId]['Answers'][$aId] = [
                    "AnswerID" => $aId,
                    "Answer" => $row['Answer'],
                    "UserName1" => $row['UserName1'] ?? $row['UserName'] ?? 'Ẩn danh',
                    "CreatedDate1" => $row['CreatedDate1'] ?? $row['CreatedDate'],
                    "AverageRating" => round($averageRating, 2),
                    "NumberEvaluators" => $numberEvaluators,
                    "Evaluations" => array_values($averagePerEvaluator)
                ];
            }
        }

        foreach ($questions as &$q) {
            $q['Answers'] = array_values($q['Answers']);
        }
        return array_values($questions);
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

        // Xử lý dữ liệu thô thành định dạng mong muốn
        $groupedQuestions = $this->groupQuestionDetails($questions);

        $this->sendResponse(true, 'Lấy dữ liệu thành công', ['questions' => $groupedQuestions]);
    }
}