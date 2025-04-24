<?php
class QuestionAndAnswerModel extends DB
{
    /**
     * Lấy câu hỏi với phân trang và điều kiện lọc
     * @param int $offset
     * @param int $itemsPerPage
     * @param array $filters (tag, search)
     * @return array
     */
    public function getQuestions($offset, $itemsPerPage, $filters = [])
    {
        $conditions = [];
        $params = [];
        $paramTypes = '';

        // Xây dựng điều kiện WHERE
        if (!empty($filters['tag'])) {
            $conditions[] = 'qs.Tags LIKE ?';
            $params[] = '%' . $filters['tag'] . '%';
            $paramTypes .= 's';
        }
        if (!empty($filters['search'])) {
            $searchPattern = '%' . $filters['search'] . '%';
            $conditions[] = '(qs.Tags LIKE ? OR qs.Question LIKE ? OR us_question.UserName LIKE ?)';
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $paramTypes .= 'sss';
        }

        // Truy vấn QuestionID
        $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $subQuery = "SELECT DISTINCT qs.QuestionID 
                     FROM questions qs
                     LEFT JOIN users us_question ON qs.UserID = us_question.UserID
                     $whereClause
                     ORDER BY qs.CreatedDate DESC 
                     LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $itemsPerPage;
        $paramTypes .= 'ii';

        $stmt = $this->conn->prepare($subQuery);
        if ($paramTypes) {
            $stmt->bind_param($paramTypes, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $questionIds = [];
        while ($row = $result->fetch_assoc()) {
            $questionIds[] = $row['QuestionID'];
        }
        $stmt->close();

        if (empty($questionIds)) {
            return [];
        }

        // Truy vấn chi tiết
        $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
        $query = "SELECT 
                     qs.QuestionID, qs.Question, qs.Tags, qs.CreatedDate, qs.NumberAnswerers,
                     us_question.UserID, us_question.UserName, us_question.Role,
                     aw.AnswerID, aw.Answer, aw.CreatedDate AS CreatedDate1, aw.NumberEvaluaters,
                     us_answer.UserID AS UserID1, us_answer.UserName AS UserName1, us_answer.Role AS Role1,
                     awv.UserID AS EvaluatorUserID, us_evaluator.UserName AS EvaluatorUserName, awv.RateCategory,
                     (SELECT AVG(CAST(SUBSTRING(awv2.RateCategory, 1, LENGTH(awv2.RateCategory) - 4) AS DECIMAL))
                      FROM answer_evaluates awv2 WHERE awv2.AnswerID = aw.AnswerID) AS AverageRating
                  FROM questions qs
                  LEFT JOIN answers aw ON qs.QuestionID = aw.QuestionID
                  LEFT JOIN users us_question ON qs.UserID = us_question.UserID
                  LEFT JOIN users us_answer ON aw.UserID = us_answer.UserID
                  LEFT JOIN answer_evaluates awv ON aw.AnswerID = awv.AnswerID
                  LEFT JOIN users us_evaluator ON awv.UserID = us_evaluator.UserID
                  WHERE qs.QuestionID IN ($placeholders)
                  ORDER BY qs.QuestionID, qs.CreatedDate DESC, aw.CreatedDate, aw.AnswerID";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(str_repeat('i', count($questionIds)), ...$questionIds);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Đếm tổng số câu hỏi theo điều kiện
     * @param array $filters (tag, search)
     * @return int
     */
    public function getTotalQuestions($filters = [])
    {
        $conditions = [];
        $params = [];
        $paramTypes = '';

        if (!empty($filters['tag'])) {
            $conditions[] = 'Tags LIKE ?';
            $params[] = '%' . $filters['tag'] . '%';
            $paramTypes .= 's';
        }
        if (!empty($filters['search'])) {
            $searchPattern = '%' . $filters['search'] . '%';
            $conditions[] = '(Tags LIKE ? OR Question LIKE ? OR UserID IN (SELECT UserID FROM users WHERE UserName LIKE ?))';
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $paramTypes .= 'sss';
        }

        $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $query = "SELECT COUNT(DISTINCT QuestionID) AS total FROM questions $whereClause";
        $stmt = $this->conn->prepare($query);
        if ($paramTypes) {
            $stmt->bind_param($paramTypes, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['total'];
    }

    /**
     * Lấy tất cả tags
     * @return array
     */
    public function GetAllTags()
    {
        $query = "SELECT DISTINCT Tags FROM questions";
        $result = mysqli_query($this->conn, $query);
        if (!$result) {
            die("Query failed: " . mysqli_error($this->conn));
        }
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Thêm câu trả lời cho câu hỏi
     * @param int $questionId
     * @param string $answer
     * @param int $userId
     * @return int
     */
    public function AddAnswerForQuestion($questionId, $answer, $userId)
    {
        $numberEvaluaters = 0; // Mặc định không có đánh giá
        $query = "INSERT INTO answers (QuestionID, Answer, Reference, UserID, CreatedDate, NumberEvaluaters) 
                  VALUES (?, ?, 'Source 1', ?, NOW(), ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("isii", $questionId, $answer, $userId, $numberEvaluaters);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $newAnswerID = $this->conn->insert_id;
        $stmt->close();
        return $newAnswerID;
    }
}