<?php
class QuestionAndAnswerModel extends DB
{
    public function GetAskAndAnswer()
    {
        return $this->GetAskAndAnswerWithPagination(0, PHP_INT_MAX);
    }

    public function GetAskAndAnswerWithPagination($offset, $itemsPerPage)
    {
        // Bước 1: Lấy danh sách QuestionID theo phân trang
        $subQuery = "SELECT distinct QuestionID 
                     FROM questions 
                     ORDER BY CreatedDate DESC 
                     LIMIT ?, ?";

        $stmt = $this->conn->prepare($subQuery);
        $stmt->bind_param("ii", $offset, $itemsPerPage);
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

        // Bước 2: Lấy toàn bộ dữ liệu liên quan đến các QuestionID
        $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
        $query = "SELECT 
                        qs.QuestionID, 
                        qs.Question, 
                        qs.Tags, 
                        qs.CreatedDate, 
                        qs.NumberAnswerers,
                        us_question.UserID, 
                        us_question.UserName, 
                        us_question.Role,
                        aw.AnswerID, 
                        aw.Answer, 
                        aw.CreatedDate AS CreatedDate1, 
                        aw.NumberEvaluaters,
                        us_answer.UserID AS UserID1, 
                        us_answer.UserName AS UserName1, 
                        us_answer.Role AS Role1, 
                        awv.UserID AS EvaluatorUserID,
                        us_evaluator.UserName AS EvaluatorUserName,
                        awv.RateCategory,
                        (
                            SELECT 
                                AVG(
                                    CAST(
                                        SUBSTRING(awv2.RateCategory, 1, LENGTH(awv2.RateCategory) - 4) AS DECIMAL
                                    )
                                )
                            FROM answer_evaluates awv2
                            WHERE awv2.AnswerID = aw.AnswerID
                        ) AS AverageRating
                    FROM questions qs
                    LEFT JOIN answers aw ON qs.QuestionID = aw.QuestionID
                    LEFT JOIN users us_question ON qs.UserID = us_question.UserID
                    LEFT JOIN  users us_answer ON aw.UserID = us_answer.UserID
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


    public function GetAskAndAnswerWithPaginationAndTag($offset, $itemsPerPage, $tag)
    {
        // Bước 1: Lấy danh sách QuestionID theo phân trang và tag
        $subQuery = "SELECT QuestionID 
                     FROM questions 
                     WHERE EXISTS (SELECT 1 FROM answers WHERE answers.QuestionID = questions.QuestionID)
                     AND Tags LIKE ?
                     ORDER BY CreatedDate DESC 
                     LIMIT ?, ?";

        $tagPattern = "%$tag%"; // Tìm kiếm tag trong chuỗi Tags
        $stmt = $this->conn->prepare($subQuery);
        $stmt->bind_param("sii", $tagPattern, $offset, $itemsPerPage);
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

        // Bước 2: Lấy toàn bộ dữ liệu liên quan đến các QuestionID
        $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
        $query = "SELECT 
                        qs.QuestionID, 
                        qs.Question, 
                        qs.Tags, 
                        qs.CreatedDate, 
                        qs.NumberAnswerers,
                        us_question.UserID, 
                        us_question.UserName, 
                        us_question.Role,
                        aw.AnswerID, 
                        aw.Answer, 
                        aw.CreatedDate AS CreatedDate1, 
                        aw.NumberEvaluaters,
                        us_answer.UserID AS UserID1, 
                        us_answer.UserName AS UserName1, 
                        us_answer.Role AS Role1, 
                        awv.UserID AS EvaluatorUserID,
                        us_evaluator.UserName AS EvaluatorUserName,
                        awv.RateCategory,
                        (
                            SELECT 
                                AVG(
                                    CAST(
                                        SUBSTRING(awv2.RateCategory, 1, LENGTH(awv2.RateCategory) - 4) AS DECIMAL
                                    )
                                )
                            FROM answer_evaluates awv2
                            WHERE awv2.AnswerID = aw.AnswerID
                        ) AS AverageRating
                    FROM questions qs
                    JOIN answers aw ON qs.QuestionID = aw.QuestionID
                    JOIN users us_question ON qs.UserID = us_question.UserID
                    JOIN users us_answer ON aw.UserID = us_answer.UserID
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

    public function GetTotalQuestions()
    {
        $query = "SELECT COUNT(DISTINCT QuestionID) AS total 
                  FROM questions";
        $result = mysqli_query($this->conn, $query);
        $row = $result->fetch_assoc();
        return $row['total'];
    }

    public function GetTotalQuestionsByTag($tag)
    {
        $query = "SELECT COUNT(DISTINCT qs.QuestionID) AS total 
                  FROM questions qs 
                  WHERE EXISTS (SELECT 1 FROM answers WHERE answers.QuestionID = qs.QuestionID)
                  AND Tags LIKE ?";
        $tagPattern = "%$tag%";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $tagPattern);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['total'];
    }

    public function GetAllTags()
    {
        $query = "SELECT DISTINCT Tags FROM questions";
    
        $result = mysqli_query($this->conn, $query);
        if (!$result) {
            die("Query failed: " . mysqli_error($this->conn));
        }
    
        return $result->fetch_all(MYSQLI_ASSOC);
    }


    public function AddAnswerForQuestion($questionId, $answer, $userId)
    {
        $number = 0;
        $numberEvaluaters = "Select NumberEvaluater from answers where QuestionID = ?";
        $stmt = $this->conn->prepare($numberEvaluaters);
        $stmt->bind_param("i", $questionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        if($row == null){
            $number = 0;
        }
        else{
            $number = $row['NumberEvaluaters'];
        }
        $number += 1;

        $query = "INSERT INTO answers (QuestionID, Answer, Reference, UserID, CreatedDate, NumberEvaluaters) 
                  VALUES (?, ?, 'Source 1', ?, NOW(), $number)";
        
        // Chuẩn bị câu lệnh
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        // Bind các tham số
        $stmt->bind_param("isi", 
            $questionId,  // QuestionID (int)
            $answer,      // Answer (string)
            $userId       // UserID (int)
        );

        // Thực thi câu lệnh
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        // Lấy AnswerID vừa chèn
        $newAnswerID = $this->conn->insert_id;

        // Đóng statement
        $stmt->close();
        // Trả về AnswerID mới
        return $newAnswerID;
    }
}