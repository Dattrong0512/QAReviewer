<?php

class Question extends Controller
{
    public function List()
    {
        $model = $this->model("AskAndAnswerModel");

        // Lấy tham số page từ query string, mặc định là 1
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $itemsPerPage = 5; // Số câu hỏi mỗi trang

        // Tính offset
        $offset = ($page - 1) * $itemsPerPage;

        // Lấy dữ liệu phân trang từ model
        $data = $model->GetAskAndAnswerWithPagination($offset, $itemsPerPage);
        $totalQuestions = $model->GetTotalQuestions();
        $totalPages = max(1, ceil($totalQuestions / $itemsPerPage)); // Đảm bảo ít nhất 1 trang

        // Log để kiểm tra dữ liệu
        error_log("Page: $page, Offset: $offset, Total Questions: $totalQuestions, Total Pages: $totalPages, Data: " . print_r($data, true));

        // Nhóm dữ liệu thành danh sách câu hỏi
        $groupedData = $this->groupData($data);

        // Log dữ liệu đã nhóm
        error_log("Grouped Data: " . print_r($groupedData, true));

        // Xử lý yêu cầu AJAX
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            $response = [
                'questions' => $groupedData,
                'totalPages' => $totalPages,
                'currentPage' => $page,
                'message' => empty($groupedData) ? 'Không có câu hỏi nào cho trang này.' : ''
            ];
            echo json_encode($response);
            exit;
        }

        // Trả về view nếu không phải yêu cầu AJAX
        $this->view("Layout/MainLayout", [
            "Page" => "Page/Home",
            "AskAndAnswerData" => json_encode($groupedData),
            "TotalPages" => $totalPages,
            "CurrentPage" => $page
        ]);
    }

    private function groupData($rows)
    {
        // Nếu không có dữ liệu, trả về mảng rỗng
        if (empty($rows)) {
            return [];
        }

        $questions = [];

        // Nhóm dữ liệu theo QuestionID và AnswerID
        foreach ($rows as $row) {
            $qId = $row['QuestionID'];
            $aId = $row['AnswerID'];

            // Khởi tạo thông tin câu hỏi nếu chưa có
            if (!isset($questions[$qId])) {
                $questions[$qId] = [
                    "id" => $qId,
                    "text" => $row['Question'],
                    "asker" => $row['UserName'],
                    "createdDate" => $row['CreatedDate'],
                    "answers" => []
                ];
            }

            // Khởi tạo thông tin câu trả lời nếu chưa có
            if (!isset($questions[$qId]['answers'][$aId])) {
                $questions[$qId]['answers'][$aId] = [
                    "id" => $aId,
                    "text" => $row['Answer'],
                    "answerer" => $row['UserName1'] ?? $row['UserName'],
                    "createdDate" => $row['CreatedDate1'] ?? $row['CreatedDate'],
                    "averageRating" => floatval($row['AverageRating'] ?? 0),
                    "numberEvaluators" => $row['NumberEvaluaters'] ?? 0,
                    "evaluations" => []
                ];
            }

            // Thêm đánh giá nếu có
            if (!empty($row['EvaluatorUserName']) && !empty($row['RateCategory'])) {
                $questions[$qId]['answers'][$aId]['evaluations'][] = [
                    "evaluator" => $row['EvaluatorUserName'],
                    "rating" => $row['RateCategory']
                ];
            }
        }

        // Chuyển mảng answers từ associative sang indexed
        foreach ($questions as &$q) {
            $q['answers'] = array_values($q['answers']);
        }

        // Chuyển mảng questions thành indexed array
        return array_values($questions);
    }
}
