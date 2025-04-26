<?php
class AnswerEvaluaterModel extends DB
{

    /**
     * Thêm câu trả lời cho câu hỏi
     * @param int $answerId
     * @param string $rating
     * @return bool
     */
    public function AddEvaluatorForAnswer($answerId, $rating)
    {
        $query = "INSERT INTO ANSWER_EVALUATES (AnswerID, UserID, RateCategory, CreatedDate) 
                  VALUES (?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($query);
        $userId = $_SESSION['userID'];
        $stmt->bind_param("iis", $answerId, $userId, $rating);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();
        return true;
    }
}
