<?php

class Question extends Controller
{
    public function List()
    {
        try {
            $model = $this->model("QuestionAndAnswerModel");

            // Lấy tham số page từ query string, mặc định là 1
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $itemsPerPage = 5; // Số câu hỏi mỗi trang

            // Tính offset
            $offset = ($page - 1) * $itemsPerPage;

            // Lấy dữ liệu phân trang từ model
            $data = $model->GetAskAndAnswerWithPagination($offset, $itemsPerPage);
            if ($data === false) {
                throw new Exception("Lỗi khi lấy dữ liệu từ cơ sở dữ liệu.");
            }

            $totalQuestions = $model->GetTotalQuestions();
            if ($totalQuestions === false) {
                throw new Exception("Lỗi khi lấy tổng số câu hỏi.");
            }

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
                "Page" => "Page/Question",
                "AskAndAnswerData" => json_encode($groupedData),
                "TotalPages" => $totalPages,
                "CurrentPage" => $page
            ]);
        } catch (Exception $e) {
            // Xử lý lỗi và trả về JSON
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode([
                    'error' => true,
                    'message' => 'Lỗi server: ' . $e->getMessage()
                ]);
                exit;
            }
            // Nếu không phải AJAX, ném lỗi để hiển thị trên giao diện
            throw $e;
        }
    }

    public function Search()
    {
        try {
            $model = $this->model("QuestionAndAnswerModel");

            // Lấy tham số query và page từ query string
            $query = isset($_GET['query']) ? trim($_GET['query']) : '';
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $itemsPerPage = 5; // Số câu hỏi mỗi trang

            // Tính offset
            $offset = ($page - 1) * $itemsPerPage;

            // Lấy dữ liệu phân trang từ model, tìm kiếm theo query
            $data = $model->SearchQuestions($offset, $itemsPerPage, $query);
            if ($data === false) {
                throw new Exception("Lỗi khi tìm kiếm dữ liệu.");
            }

            $totalQuestions = $model->GetTotalQuestionsBySearch($query);
            if ($totalQuestions === false) {
                throw new Exception("Lỗi khi lấy tổng số câu hỏi tìm kiếm.");
            }

            $totalPages = max(1, ceil($totalQuestions / $itemsPerPage)); // Đảm bảo ít nhất 1 trang

            // Log để kiểm tra dữ liệu
            error_log("Search - Query: $query, Page: $page, Offset: $offset, Total Questions: $totalQuestions, Total Pages: $totalPages, Data: " . print_r($data, true));

            // Nhóm dữ liệu thành danh sách câu hỏi
            $groupedData = $this->groupData($data);

            // Log dữ liệu đã nhóm
            error_log("Search - Grouped Data: " . print_r($groupedData, true));

            // Xử lý yêu cầu AJAX
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                $response = [
                    'questions' => $groupedData,
                    'totalPages' => $totalPages,
                    'currentPage' => $page,
                    'message' => empty($groupedData) ? 'Không tìm thấy câu hỏi nào phù hợp.' : ''
                ];
                echo json_encode($response);
                exit;
            }

            // Trả về view nếu không phải yêu cầu AJAX
            $this->view("Layout/MainLayout", [
                "Page" => "Page/Question",
                "AskAndAnswerData" => json_encode($groupedData),
                "TotalPages" => $totalPages,
                "CurrentPage" => $page
            ]);
        } catch (Exception $e) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode([
                    'error' => true,
                    'message' => 'Lỗi server: ' . $e->getMessage()
                ]);
                exit;
            }
            throw $e;
        }
    }

    private function groupData($rows)
    {
        // Nếu không có dữ liệu, trả về mảng rỗng
        if (empty($rows)) {
            return [];
        }

        $questions = [];

        // Nhóm dữ liệu theo QuestionID
        foreach ($rows as $row) {
            $qId = $row['QuestionID'];

            // Khởi tạo thông tin câu hỏi nếu chưa có
            if (!isset($questions[$qId])) {
                $questions[$qId] = [
                    "id" => $qId,
                    "text" => $row['Question'],
                    "tags" => $row['Tags'],
                    "asker" => $row['UserName'],
                    "createdDate" => $row['CreatedDate'],
                    "answers" => []
                ];
            }

            // Nếu không có AnswerID, bỏ qua (trường hợp câu hỏi không có câu trả lời)
            if (empty($row['AnswerID'])) {
                continue;
            }

            $aId = $row['AnswerID'];

            // Khởi tạo thông tin câu trả lời nếu chưa có
            if (!isset($questions[$qId]['answers'][$aId])) {
                $evaluations = [];
                $numberEvaluators = 0;
                $averageRating = floatval($row['AverageRating'] ?? 0);

                // Tính số lượng đánh giá và danh sách đánh giá
                foreach ($rows as $r) {
                    if ($r['AnswerID'] == $aId && !empty($r['EvaluatorUserName']) && !empty($r['RateCategory'])) {
                        // Kiểm tra trùng lặp đánh giá để tránh thêm nhiều lần
                        $evalExists = false;
                        foreach ($evaluations as $existingEval) {
                            if ($existingEval['evaluator'] === $r['EvaluatorUserName'] && $existingEval['rating'] === $r['RateCategory']) {
                                $evalExists = true;
                                break;
                            }
                        }
                        if (!$evalExists) {
                            $evaluations[] = [
                                "evaluator" => $r['EvaluatorUserName'],
                                "rating" => $r['RateCategory']
                            ];
                        }
                    }
                }

                $numberEvaluators = count($evaluations);

                // Tính lại averageRating dựa trên evaluations nếu có
                if ($numberEvaluators > 0) {
                    $totalRating = 0;
                    foreach ($evaluations as $eval) {
                        // Chuyển rating từ dạng "nSTAR" thành số (ví dụ: "4STAR" -> 4)
                        $ratingValue = floatval(preg_replace('/[^0-9.]/', '', $eval['rating']));
                        $totalRating += $ratingValue;
                    }
                    $averageRating = $totalRating / $numberEvaluators;
                } else {
                    $averageRating = 0; // Nếu không có đánh giá, đặt averageRating về 0
                }

                $questions[$qId]['answers'][$aId] = [
                    "id" => $aId,
                    "text" => $row['Answer'],
                    "answerer" => $row['UserName1'] ?? $row['UserName'],
                    "createdDate" => $row['CreatedDate1'] ?? $row['CreatedDate'],
                    "averageRating" => $averageRating,
                    "numberEvaluators" => $numberEvaluators,
                    "evaluations" => $evaluations
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

    public function Filter()
    {
        try {
            $model = $this->model("QuestionAndAnswerModel");

            // Lấy tham số page và tag từ query string
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';
            $itemsPerPage = 5; // Số câu hỏi mỗi trang

            // Tính offset
            $offset = ($page - 1) * $itemsPerPage;

            // Lấy dữ liệu phân trang từ model, lọc theo tag
            $data = $model->GetAskAndAnswerWithPaginationAndTag($offset, $itemsPerPage, $tag);
            if ($data === false) {
                throw new Exception("Lỗi khi lọc dữ liệu theo tag.");
            }

            $totalQuestions = $model->GetTotalQuestionsByTag($tag);
            if ($totalQuestions === false) {
                throw new Exception("Lỗi khi lấy tổng số câu hỏi theo tag.");
            }

            $totalPages = max(1, ceil($totalQuestions / $itemsPerPage)); // Đảm bảo ít nhất 1 trang

            // Log để kiểm tra dữ liệu
            error_log("Filter - Page: $page, Offset: $offset, Tag: $tag, Total Questions: $totalQuestions, Total Pages: $totalPages, Data: " . print_r($data, true));

            // Nhóm dữ liệu thành danh sách câu hỏi
            $groupedData = $this->groupData($data);

            // Log dữ liệu đã nhóm
            error_log("Filter - Grouped Data: " . print_r($groupedData, true));

            // Xử lý yêu cầu AJAX
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                $response = [
                    'questions' => $groupedData,
                    'totalPages' => $totalPages,
                    'currentPage' => $page,
                    'message' => empty($groupedData) ? 'Không có câu hỏi nào cho tag này.' : ''
                ];
                echo json_encode($response);
                exit;
            }

            // Trả về view nếu không phải yêu cầu AJAX
            $this->view("Layout/MainLayout", [
                "Page" => "Page/Question",
                "AskAndAnswerData" => json_encode($groupedData),
                "TotalPages" => $totalPages,
                "CurrentPage" => $page
            ]);
        } catch (Exception $e) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode([
                    'error' => true,
                    'message' => 'Lỗi server: ' . $e->getMessage()
                ]);
                exit;
            }
            throw $e;
        }
    }

    public function GetAllTags()
    {
        try {
            $model = $this->model("QuestionAndAnswerModel");
            $tagRows = $model->GetAllTags();
            if ($tagRows === false) {
                throw new Exception("Lỗi khi lấy danh sách tags.");
            }

            // Chuyển đổi danh sách tags từ dạng chuỗi thành mảng các tag riêng lẻ
            $allTags = [];
            foreach ($tagRows as $row) {
                $tags = explode(',', $row['Tags']);
                foreach ($tags as $tag) {
                    $tag = trim($tag);
                    if (!empty($tag) && !in_array($tag, $allTags)) {
                        $allTags[] = $tag;
                    }
                }
            }

            // Sắp xếp tags theo thứ tự alphabet để dễ đọc
            sort($allTags);

            // Trả về dưới dạng JSON
            header('Content-Type: application/json');
            echo json_encode($allTags);
            exit();
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => 'Lỗi server: ' . $e->getMessage()
            ]);
            exit();
        }
    }
}