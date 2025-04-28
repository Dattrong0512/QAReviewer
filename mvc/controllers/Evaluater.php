<?php
class Evaluater extends Controller
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
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            $this->sendResponse(false, 'Yêu cầu không hợp lệ.');
            return;
        }

        ob_start();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            $model = $this->model("AnswerEvaluaterModel");
            $answerId = trim($_POST['answerId'] ?? '');
            $rating = trim($_POST['rating'] ?? '');

            if (empty($answerId) || empty($rating)) {
                ob_end_clean();
                $this->sendResponse(false, 'Yêu cầu không hợp lệ: Thiếu thông tin cần thiết');
                return;
            }

            $result = $model->AddEvaluatorForAnswer($answerId, $rating);
            if ($result === false) {
                ob_end_clean();
                $this->sendResponse(false, 'Lỗi khi thêm đánh giá vào cơ sở dữ liệu.');
                return;
            }

            ob_end_clean();
            $this->sendResponse(true, 'Đánh giá thành công');
        } catch (Exception $e) {
            ob_end_clean();
            $this->sendResponse(false, 'Lỗi khi đánh giá câu trả lời: ' . $e->getMessage());
        }
    }

    public function ListNewestEvaluate()
    {
        $modelEvaluate = $this->model("AnswerEvaluaterModel");
        $modelAnswer = $this->model('QuestionAndAnswerModel');

        $itemsPerPage = 10;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $itemsPerPage;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }


        $evaluations = $modelEvaluate->GetAllEvaluateAnswer($offset, $itemsPerPage);
        $totalEvaluations = $modelEvaluate->GetTotalEvaluations();
        $totalPages = ceil($totalEvaluations / $itemsPerPage);

        $userEvaluations = [];
        if (isset($_SESSION['role']) && ($_SESSION['role'] === 'Evaluater' || $_SESSION['role'] === 'Admin')) {
            $userEvaluations = $modelEvaluate->GetUserEvaluations($_SESSION['userID']);
        }

        $this->view("Layout/MainLayout", [
            "Page" => "Page/Evaluater",
            "evaluations" => $evaluations,
            "totalPages" => $totalPages,
            "currentPage" => $page,
            "role" => $_SESSION['role'] ?? null,
            "userID" => $_SESSION['userID'] ?? null,
            "username" => $_SESSION['username'] ?? null,
            "userEvaluations" => $userEvaluations
        ]);
    }

    public function GetNewestEvaluateJson()
    {
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            $this->sendResponse(false, 'Yêu cầu không hợp lệ: Chỉ hỗ trợ AJAX');
        }

        $itemsPerPage = 10;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $itemsPerPage;

        $modelEvaluate = $this->model("AnswerEvaluaterModel");
        $evaluations = $modelEvaluate->GetAllEvaluateAnswer($offset, $itemsPerPage);
        $totalEvaluations = $modelEvaluate->GetTotalEvaluations();
        $totalPages = ceil($totalEvaluations / $itemsPerPage);

        $this->sendResponse(true, 'Lấy dữ liệu thành công', [
            'evaluations' => $evaluations,
            'totalPages' => $totalPages,
            'currentPage' => $page
        ]);
    }

    public function GetUserEvaluationsJson()
    {
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            $this->sendResponse(false, 'Yêu cầu không hợp lệ: Chỉ hỗ trợ AJAX');
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Evaluater', 'Admin'])) {
            $this->sendResponse(false, 'Bạn không có quyền truy cập');
        }

        $modelEvaluate = $this->model("AnswerEvaluaterModel");
        $userEvaluations = $modelEvaluate->GetUserEvaluations($_SESSION['userID']);

        $this->sendResponse(true, 'Lấy dữ liệu thành công', [
            'userEvaluations' => $userEvaluations
        ]);
    }

    private function normalizeRating($rating)
    {
        $rating = trim($rating);
        $normalized = floatval(preg_replace('/[^0-9.]/', '', $rating));
        if ($normalized < 1 || $normalized > 5) {
            return 0;
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
                $evaluations = [];
                foreach ($rows as $r) {
                    if ($r['AnswerID'] == $aId && !empty($r['EvaluatorUserName']) && !empty($r['RateCategory'])) {
                        $normalizedRating = $this->normalizeRating($r['RateCategory']);
                        if ($normalizedRating > 0) {
                            $evaluations[] = [
                                "EvaluatorUserName" => $r['EvaluatorUserName'],
                                "RateCategory" => $normalizedRating
                            ];
                        }
                    }
                }

                $evaluatorRatings = [];
                foreach ($evaluations as $eval) {
                    $evaluator = $eval['EvaluatorUserName'];
                    if (!isset($evaluatorRatings[$evaluator])) {
                        $evaluatorRatings[$evaluator] = [];
                    }
                    $evaluatorRatings[$evaluator][] = $eval['RateCategory'];
                }

                $averagePerEvaluator = [];
                foreach ($evaluatorRatings as $evaluator => $ratings) {
                    $average = array_sum($ratings) / count($ratings);
                    $averagePerEvaluator[] = [
                        "EvaluatorUserName" => $evaluator,
                        "RateCategory" => $average
                    ];
                }

                $numberEvaluators = count($averagePerEvaluator);
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

        $groupedQuestions = $this->groupQuestionDetails($questions);
        $this->sendResponse(true, 'Lấy dữ liệu thành công', ['questions' => $groupedQuestions]);
    }
}
?>