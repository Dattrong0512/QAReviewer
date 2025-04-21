<?php
class AskAndAnswerModel extends DB
{

    public function GetAskAndAnswer()
    {
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
                        aw.CreatedDate, 
                        aw.NumberEvaluaters,
                        us_answer.UserID, 
                        us_answer.UserName, 
                        us_answer.Role, 
                        awv.UserID AS EvaluatorUserID,
                        us_evaluator.UserName AS EvaluatorUserName,
                        awv.RateCategory,
                        -- Subquery để tính trung bình số sao cho mỗi AnswerID
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
                    ORDER BY qs.QuestionID, qs.CreatedDate, aw.CreatedDate, aw.AnswerID;";

        $result = mysqli_query($this->conn, $query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
