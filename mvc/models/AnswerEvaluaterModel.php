<?php
class AnswerEvaluaterModel extends DB
{
    private function normalizeRating($rating)
    {
        $rating = trim($rating);
        $normalized = floatval(preg_replace('/[^0-9.]/', '', $rating));
        if ($normalized < 1 || $normalized > 5) {
            return 1; // Đặt giá trị mặc định nếu không hợp lệ
        }
        return $normalized;
    }

    public function AddEvaluatorForAnswer($answerId, $rating)
    {
        $normalizedRating = $this->normalizeRating($rating);
        $query = "INSERT INTO ANSWER_EVALUATES (AnswerID, UserID, RateCategory, CreatedDate) 
                  VALUES (?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($query);
        $userId = $_SESSION['userID'];
        $stmt->bind_param("iis", $answerId, $userId, $normalizedRating);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();
        return true;
    }

    public function GetAllEvaluateAnswer($offset, $itemsPerPage)
    {
        $query = "SELECT ae.EvaluateID, ae.AnswerID, ae.RateCategory, ae.CreatedDate, 
                         u.UserID, u.UserName, a.QuestionID
                  FROM answer_evaluates ae
                  LEFT JOIN users u ON ae.UserID = u.UserID
                  LEFT JOIN answers a ON ae.AnswerID = a.AnswerID
                  ORDER BY ae.CreatedDate DESC 
                  LIMIT ?, ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $offset, $itemsPerPage);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function GetTotalEvaluations()
    {
        $query = "SELECT COUNT(*) as total FROM answer_evaluates";
        $result = $this->conn->query($query);
        $row = $result->fetch_assoc();
        return $row['total'];
    }

    public function GetUserEvaluations($userId)
    {
        $query = "SELECT ae.EvaluateID, ae.AnswerID, ae.RateCategory, ae.CreatedDate, 
                         u.UserID, u.UserName, a.QuestionID, 
                         a.Answer, u2.UserName AS UserName1, a.CreatedDate AS CreatedDate1
                  FROM answer_evaluates ae
                  LEFT JOIN users u ON ae.UserID = u.UserID
                  LEFT JOIN answers a ON ae.AnswerID = a.AnswerID
                  LEFT JOIN users u2 ON a.UserID = u2.UserID
                  WHERE ae.UserID = ?
                  ORDER BY ae.CreatedDate DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
