<?php

class Home extends Controller
{
    public function Show()
    {
        $model = $this->model("AskAndAnswerModel");
        $data = $model->GetAskAndAnswer();
        $groupedData = $this->groupData($data);

        $this->view("Layout/MainLayout", [
            "Page" => "Page/Home",
            "AskAndAnswerData" => json_encode($groupedData)
        ]);
    }

    private function groupData($rows)
    {
        $questions = [];

        foreach ($rows as $row) {
            $qId = $row['QuestionID'];
            $aId = $row['AnswerID'];

            if (!isset($questions[$qId])) {
                $questions[$qId] = [
                    "id" => $qId,
                    "text" => $row['Question'],
                    "asker" => $row['UserName'],
                    "createdDate" => $row['CreatedDate'],
                    "answers" => []
                ];
            }

            if (!isset($questions[$qId]['answers'][$aId])) {
                $questions[$qId]['answers'][$aId] = [
                    "id" => $aId,
                    "text" => $row['Answer'],
                    "answerer" => $row['UserName1'] ?? $row['UserName'], // tránh trùng tên cột
                    "createdDate" => $row['CreatedDate1'] ?? $row['CreatedDate'], // tránh trùng tên
                    "averageRating" => floatval($row['AverageRating']),
                    "numberEvaluators" => $row['NumberEvaluaters'],
                    "evaluations" => []
                ];
            }

            if ($row['EvaluatorUserName'] && $row['RateCategory']) {
                $questions[$qId]['answers'][$aId]['evaluations'][] = [
                    "evaluator" => $row['EvaluatorUserName'],
                    "rating" => $row['RateCategory']
                ];
            }
        }

        // chuyển answers từ associative => index array
        foreach ($questions as &$q) {
            $q['answers'] = array_values($q['answers']);
        }

        return array_values($questions);
    }
}

?>