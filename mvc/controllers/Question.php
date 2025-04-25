<?php
class Question extends Controller
{

    public function Create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $model = $this->model("QuestionAndAnswerModel");
            $question = trim($_POST['question'] ?? '');
            $tags = trim($_POST['tags'] ?? '');
            $userId = $_SESSION['userId'] ?? null;

            if (empty($question) || empty($tags) || empty($userId)) {
                throw new Exception("Vui lòng điền đầy đủ thông tin.");
            }

            $result = $model->createQuestion($question, $tags, $userId);
            if ($result === false) {
                throw new Exception("Lỗi khi tạo câu hỏi.");
            }

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Câu hỏi đã được tạo thành công.']);

        }
    }
    public function List()
    {
        $this->handleQuestionRequest('list');
    }

    public function Search()
    {
        $this->handleQuestionRequest('search');
    }

    public function Filter()
    {
        $this->handleQuestionRequest('filter');
    }

    private function handleQuestionRequest($type)
    {
        try {
            $model = $this->model("QuestionAndAnswerModel");
            $page = max(1, intval($_GET['page'] ?? 1));
            $itemsPerPage = 5;
            $offset = ($page - 1) * $itemsPerPage;
            $filters = [];

            if ($type === 'filter') {
                $filters['tag'] = trim($_GET['tag'] ?? '');
            } elseif ($type === 'search') {
                $filters['search'] = trim($_GET['input'] ?? '');
            }

            $data = $model->getQuestions($offset, $itemsPerPage, $filters);
            if ($data === false) {
                throw new Exception("Lỗi khi lấy dữ liệu từ cơ sở dữ liệu.");
            }

            $totalQuestions = $model->getTotalQuestions($filters);
            if ($totalQuestions === false) {
                throw new Exception("Lỗi khi lấy tổng số câu hỏi.");
            }

            $totalPages = max(1, ceil($totalQuestions / $itemsPerPage));
            $groupedData = $this->groupData($data);

            error_log("$type - Page: $page, Offset: $offset, Filters: " . print_r($filters, true) . ", Total Questions: $totalQuestions, Total Pages: $totalPages");

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'questions' => $groupedData,
                    'totalPages' => $totalPages,
                    'currentPage' => $page,
                    'message' => empty($groupedData) ? 'Không có câu hỏi nào.' : ''
                ]);
                exit;
            }

            $this->view("Layout/MainLayout", [
                "Page" => "Page/Question",
                "AskAndAnswerData" => json_encode($groupedData),
                "totalPages" => json_encode($totalPages),
                "currentPage" => json_encode($page)
            ]);
        } catch (Exception $e) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode(['error' => true, 'message' => 'Lỗi server: ' . $e->getMessage()]);
                exit;
            }
            throw $e;
        }
    }

    private function groupData($rows)
    {
        if (empty($rows)) return [];

        $questions = [];
        foreach ($rows as $row) {
            $qId = $row['QuestionID'];
            if (!isset($questions[$qId])) {
                $questions[$qId] = [
                    "id" => $qId,
                    "text" => $row['Question'],
                    "tags" => $row['Tags'],
                    "asker" => $row['UserName'] ?? 'Ẩn danh',
                    "createdDate" => $row['CreatedDate'],
                    "answers" => []
                ];
            }

            if (empty($row['AnswerID'])) continue;

            $aId = $row['AnswerID'];
            if (!isset($questions[$qId]['answers'][$aId])) {
                $evaluations = [];
                foreach ($rows as $r) {
                    if ($r['AnswerID'] == $aId && !empty($r['EvaluatorUserName']) && !empty($r['RateCategory'])) {
                        $evaluations[] = [
                            "evaluator" => $r['EvaluatorUserName'],
                            "rating" => $r['RateCategory']
                        ];
                    }
                }

                $numberEvaluators = count(array_unique(array_column($evaluations, 'evaluator')));
                $averageRating = $numberEvaluators ? array_sum(array_map(fn($e) => floatval(preg_replace('/[^0-9.]/', '', $e['rating'])), $evaluations)) / $numberEvaluators : 0;

                $questions[$qId]['answers'][$aId] = [
                    "id" => $aId,
                    "text" => $row['Answer'],
                    "answerer" => $row['UserName1'] ?? $row['UserName'] ?? 'Ẩn danh',
                    "createdDate" => $row['CreatedDate1'] ?? $row['CreatedDate'],
                    "averageRating" => $averageRating,
                    "numberEvaluators" => $numberEvaluators,
                    "evaluations" => array_values($evaluations)
                ];
            }
        }

        foreach ($questions as &$q) {
            $q['answers'] = array_values($q['answers']);
        }
        return array_values($questions);
    }

    public function GetAllTags()
    {
        try {
            $model = $this->model("QuestionAndAnswerModel");
            $tagRows = $model->GetAllTags();
            $allTags = [];
            foreach ($tagRows as $row) {
                foreach (explode(',', $row['Tags']) as $tag) {
                    $tag = trim($tag);
                    if ($tag && !in_array($tag, $allTags)) {
                        $allTags[] = $tag;
                    }
                }
            }
            sort($allTags);
            header('Content-Type: application/json');
            echo json_encode($allTags);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => true, 'message' => 'Lỗi server: ' . $e->getMessage()]);
            exit;
        }
    }
}
