$(document).ready(function () {
    console.log("LatestAnswers.js loaded successfully");
    console.log("Dữ liệu window.answers ban đầu:", window.answers);

    // Khai báo các biến DOM và trạng thái
    const $answerList = $('#answerList');
    const $pagination = $('.pagination');
    const $questionDetails = $('#questionDetails');
    const $questionContent = $('#questionContent');
    const role = window.role;
    const userID = window.userID;
    const username = window.username;

    console.log("Role:", role);
    console.log("User ID:", userID);
    console.log("Username:", username);

    // Hàm render danh sách câu trả lời
    function renderAnswers(answers) {
        console.log("Rendering answers:", answers);
        $answerList.empty();

        if (!Array.isArray(answers) || answers.length === 0) {
            $answerList.html('<p>Không có câu trả lời nào để hiển thị.</p>');
            return;
        }

        answers.forEach(answer => {
            const $answerDiv = $('<div>')
                .addClass('answer-item')
                .attr('data-question-id', answer.QuestionID)
                .html(`
                    <div class="answer-content-wrapper">
                        <div class="answer-content">${answer.Answer}</div>
                        <div class="answer-meta">
                            <span class="meta-label">Trả lời bởi:</span> ${answer.UserName || 'Ẩn danh'} <br>
                            <span class="meta-label">Ngày tạo:</span> ${answer.CreatedDate}
                        </div>
                    </div>
                `);

            $answerList.append($answerDiv);
        });
    }

    // Hàm lấy danh sách câu trả lời mới nhất từ server
    function fetchAnswers(page) {
        $.ajax({
            url: `/QAReviewer/Answer/GetNewestAnswersJson?page=${page}`,
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    window.answers = response.data.answers;
                    renderAnswers(response.data.answers);
                    CommonUtils.renderPagination($pagination, response.data.totalPages, response.data.currentPage, fetchAnswers, {
                        hideDetailsOnClick: true,
                        $detailsElement: $questionDetails
                    });
                    const newUrl = `/QAReviewer/Answer/ListAnswerNewest?page=${page}`;
                    window.history.pushState({ page: page }, '', newUrl);
                } else {
                    console.error("Lỗi từ server:", response.message);
                    alert('Lỗi: ' + response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error("Lỗi AJAX:", xhr, status, error);
                if (xhr.responseText) {
                    console.error("Phản hồi server:", xhr.responseText);
                }
                alert('Lỗi khi tải dữ liệu: ' + error);
            }
        });
    }

    // Hàm lấy chi tiết câu hỏi và các câu trả lời
    function fetchQuestionDetails(questionId) {
        $.ajax({
            url: `/QAReviewer/Answer/GetQuestionDetails?questionId=${questionId}`,
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    renderQuestionDetails(response.data.questions);
                    $questionDetails.show();
                } else {
                    alert('Lỗi: ' + response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error("Lỗi AJAX:", xhr, status, error);
                alert('Lỗi khi tải chi tiết câu hỏi: ' + error);
            }
        });
    }

    // Hàm render chi tiết câu hỏi
    function renderQuestionDetails(questions) {
        $questionContent.empty();

        if (!Array.isArray(questions) || questions.length === 0) {
            $questionContent.html('<p>Không có dữ liệu để hiển thị.</p>');
            return;
        }

        const question = questions[0];
        const tags = question.Tags ? question.Tags.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0) : [];
        const tagsHtml = tags.length > 0
            ? `<div class="question-tags">${tags.map(tag => `<span class="tag">${tag}</span>`).join('')}</div>`
            : '';

        const $questionDiv = $('<div>')
            .addClass('question-item')
            .attr('data-question-id', question.QuestionID)
            .html(`
                <div class="header-wrapper">
                    <h3>${question.Question}</h3>
                </div>
                <div class="question-meta">
                    <span class="meta-label">Đặt bởi:</span> ${question.UserName || 'Ẩn danh'} <br>
                    <span class="meta-label">Ngày tạo:</span> ${question.CreatedDate}
                </div>
                ${tagsHtml}
                <div class="answer-section">
                    <h4>Câu trả lời</h4>
                </div>
            `);

        const $answerSection = $questionDiv.find('.answer-section');
        const answers = question.Answers || [];
        if (answers.length === 0) {
            $answerSection.append('<p>Chưa có câu trả lời nào.</p>');
        } else {
            answers.forEach(answer => {
                const evaluations = answer.Evaluations || [];
                const evaluationsHtml = evaluations.length > 0
                    ? evaluations.map(eval => {
                        const ratingValue = parseFloat(eval.RateCategory) || 0;
                        return `
                            <div class="evaluation-item">
                                <span class="evaluator-name">${eval.EvaluatorUserName}</span>
                                <div class="rating-stars" data-rating="${ratingValue}"></div>
                            </div>
                        `;
                    }).join('')
                    : '<p>Chưa có đánh giá nào.</p>';

                const ratingInfoHtml = `
                    <div class="rating-info">
                        Số lượt đánh giá: <span class="toggle-evaluations" data-answer-id="${answer.AnswerID}">
                            ${answer.NumberEvaluators || 0}
                        </span>
                        <div class="evaluation-details" id="evaluations-${answer.AnswerID}">
                            ${evaluationsHtml}
                        </div>
                    </div>
                `;

                const hasUserEvaluated = evaluations.some(eval => eval.EvaluatorUserName === username);
                const showEvaluateButton = (role === 'Evaluater' || role === 'Admin') && !hasUserEvaluated;
                const evaluatorRating = showEvaluateButton
                    ? `<button class="evaluater-create-rating" data-answer-id="${answer.AnswerID}">Đánh giá</button>`
                    : '';

                const $answerDiv = $('<div>')
                    .addClass('answer-item')
                    .html(`
                        <div class="answer-content-wrapper">
                            <div class="answer-content">${answer.Answer}</div>
                            <div class="answer-meta">
                                <span class="meta-label">Trả lời bởi:</span> ${answer.UserName1 || 'Ẩn danh'} <br>
                                <span class="meta-label">Ngày tạo:</span> ${answer.CreatedDate1}
                            </div>
                        </div>
                        <div class="rating-wrapper">
                            <div class="rating-stars" data-rating="${answer.AverageRating || 0}"></div>
                            ${ratingInfoHtml}
                            ${evaluatorRating}
                        </div>
                    `);
                $answerSection.append($answerDiv);
            });
        }

        $questionContent.append($questionDiv);
        CommonUtils.updateStars();
    }

    // Sự kiện click để hiển thị chi tiết câu hỏi và làm nổi bật câu trả lời được chọn
    $answerList.on('click', '.answer-item', function () {
        $('.answer-item').removeClass('selected');
        $(this).addClass('selected');
        const questionId = $(this).data('question-id');
        fetchQuestionDetails(questionId);
    });

    // Gắn sự kiện hover và click cho đánh giá
    CommonUtils.attachEvaluationHoverEvents($questionContent);
    CommonUtils.attachEvaluationClickEvents($questionContent, fetchQuestionDetails);

    // Render dữ liệu ban đầu
    let answersData = window.answers;
    if (typeof answersData === 'undefined') {
        console.error("window.answers is not defined");
        answersData = [];
    }

    if (Array.isArray(answersData) && answersData.length > 0) {
        renderAnswers(answersData);
        CommonUtils.renderPagination($pagination, window.totalPages, window.currentPage, fetchAnswers, {
            hideDetailsOnClick: true,
            $detailsElement: $questionDetails
        });
    } else {
        $answerList.html('<p>Không có câu trả lời nào để hiển thị.</p>');
    }
});