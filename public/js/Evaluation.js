$(document).ready(function () {
    console.log("Evaluation.js loaded successfully");
    console.log("Dữ liệu window.evaluations ban đầu:", window.evaluations);

    // Khai báo các biến DOM và trạng thái
    const $evaluationListWrapper = $('.evaluation-list-wrapper');
    const $evaluationList = $('#evaluationList');
    const $pagination = $('.pagination');
    const $questionDetails = $('#questionDetails');
    const $questionContent = $('#questionContent');
    const $userEvaluationsWrapper = $('#userEvaluationsWrapper');
    const $userEvaluations = $('#userEvaluations');
    const $viewUserEvaluationsBtn = $('#viewUserEvaluationsBtn');
    const role = window.role;
    const userID = window.userID;
    const username = window.username;

    console.log("Role:", role);
    console.log("User ID:", userID);
    console.log("Username:", username);

    // Hàm chuẩn hóa RateCategory
    function normalizeRating(rating) {
        const normalized = parseFloat(rating.toString().replace(/[^0-9.]/g, '')) || 0;
        return normalized >= 1 && normalized <= 5 ? normalized : 0;
    }

    // Hàm render danh sách đánh giá mới nhất
    function renderEvaluations(evaluations) {
        $evaluationList.empty();

        if (!Array.isArray(evaluations) || evaluations.length === 0) {
            $evaluationList.html('<p>Không có đánh giá nào để hiển thị.</p>');
            return;
        }

        evaluations.forEach(evaluation => {
            const rating = normalizeRating(evaluation.RateCategory);
            const $evaluationDiv = $('<div>')
                .addClass('evaluation-item')
                .attr('data-question-id', evaluation.QuestionID)
                .html(`
                    <div class="evaluation-content-wrapper">
                        <div class="evaluation-content">
                            Đánh giá: <span class="rating-stars" data-rating="${rating}"></span>
                        </div>
                        <div class="evaluation-meta">
                            <span class="meta-label">Đánh giá bởi:</span> ${evaluation.UserName || 'Ẩn danh'} <br>
                            <span class="meta-label">Ngày tạo:</span> ${evaluation.CreatedDate}
                        </div>
                    </div>
                `);
            $evaluationList.append($evaluationDiv);
        });

        CommonUtils.updateStars();
    }

    // Hàm render danh sách đánh giá của người dùng (bao gồm câu trả lời)
    function renderUserEvaluations(evaluations) {

        $userEvaluations.empty();

        if (!Array.isArray(evaluations) || evaluations.length === 0) {
            console.log("Không có đánh giá nào để hiển thị");
            $userEvaluations.html('<p>Bạn chưa có đánh giá nào.</p>');
            return;
        }

        evaluations.forEach(evaluation => {

            const rating = normalizeRating(evaluation.RateCategory);
            const $evaluationDiv = $('<div>')
                .addClass('evaluation-item')
                .attr('data-question-id', evaluation.QuestionID)
                .html(`
                    <div class="evaluation-content-wrapper">
                        <div class="evaluation-content">
                            Đánh giá: <span class="rating-stars" data-rating="${rating}"></span>
                        </div>
                        <div class="evaluation-meta">
                            <span class="meta-label">Đánh giá bởi:</span> ${evaluation.UserName || 'Ẩn danh'} <br>
                            <span class="meta-label">Ngày tạo:</span> ${evaluation.CreatedDate}
                        </div>
                        <div class="answer-content-wrapper">
                            <div class="answer-content">${evaluation.Answer || 'Không có câu trả lời'}</div>
                            <div class="answer-meta">
                                <span class="meta-label">Trả lời bởi:</span> ${evaluation.UserName1 || 'Ẩn danh'} <br>
                                <span class="meta-label">Ngày tạo:</span> ${evaluation.CreatedDate1 || evaluation.CreatedDate}
                            </div>
                        </div>
                    </div>
                `);
            $userEvaluations.append($evaluationDiv);
    
        });

        console.log("Gọi CommonUtils.updateStars");
        CommonUtils.updateStars();
        console.log("Nội dung cuối cùng của #userEvaluations:", $userEvaluations.html());
    }

    // Hàm lấy danh sách đánh giá mới nhất từ server
    function fetchEvaluations(page) {
        $.ajax({
            url: `/QAReviewer/Evaluater/GetNewestEvaluateJson?page=${page}`,
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    window.evaluations = response.data.evaluations;
                    renderEvaluations(response.data.evaluations);
                    CommonUtils.renderPagination($pagination, response.data.totalPages, response.data.currentPage, fetchEvaluations, {
                        hideDetailsOnClick: true,
                        $detailsElement: $questionDetails
                    });
                    const newUrl = `/QAReviewer/Evaluater/ListNewestEvaluate?page=${page}`;
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

    // Hàm lấy danh sách đánh giá của người dùng từ server
    function fetchUserEvaluations() {
        console.log("Bắt đầu gọi API /QAReviewer/Evaluater/GetUserEvaluationsJson");
        $.ajax({
            url: `/QAReviewer/Evaluater/GetUserEvaluationsJson`,
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                console.log("Phản hồi từ API:", response);
                if (response.success) {
                    console.log("Dữ liệu userEvaluations:", response.data.userEvaluations);
                    window.userEvaluations = response.data.userEvaluations;
                    renderUserEvaluations(response.data.userEvaluations);
                    $userEvaluationsWrapper.show();
                    $evaluationListWrapper.hide();
                    console.log("Đã hiển thị $userEvaluationsWrapper, ẩn $evaluationListWrapper");
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
            url: `/QAReviewer/Evaluater/GetQuestionDetails?questionId=${questionId}`,
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
                        const ratingValue = normalizeRating(eval.RateCategory);
                        return `
                            <div class="evaluation-item">
                                <span class="evaluater-name">${eval.EvaluatorUserName}</span>
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
                const evaluaterRating = showEvaluateButton
                    ? `<button class="evaluater-create-rating" data-answer-id="${answer.AnswerID}">Đánh giá</button>`
                    : '';

                const averageRating = normalizeRating(answer.AverageRating);
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
                            <div class="rating-stars" data-rating="${averageRating}"></div>
                            ${ratingInfoHtml}
                            ${evaluaterRating}
                        </div>
                    `);
                $answerSection.append($answerDiv);
            });
        }

        $questionContent.append($questionDiv);
        CommonUtils.updateStars();
    }

    // Sự kiện click để hiển thị chi tiết câu hỏi
    $evaluationList.on('click', '.evaluation-item', function () {
        $('.evaluation-item').removeClass('selected');
        $(this).addClass('selected');
        const questionId = $(this).data('question-id');
        fetchQuestionDetails(questionId);
    });

    $userEvaluations.on('click', '.evaluation-item', function () {
        $('.evaluation-item').removeClass('selected');
        $(this).addClass('selected');
        const questionId = $(this).data('question-id');
        fetchQuestionDetails(questionId);
    });

    // Sự kiện click vào nút "Xem câu trả lời đã đánh giá"
    $viewUserEvaluationsBtn.on('click', function () {
        console.log("Nút 'Xem câu trả lời đã đánh giá' được nhấn");
        console.log("Role hiện tại:", role);
        if (role !== 'Evaluater' && role !== 'Admin') {
            console.log("Không có quyền truy cập - Role không phải Evaluater hoặc Admin");
            alert('Bạn không có quyền truy cập danh sách đánh giá của mình.');
            return;
        }
        if ($userEvaluationsWrapper.is(':visible')) {
            console.log("Ẩn $userEvaluationsWrapper, hiển thị $evaluationListWrapper");
            $userEvaluationsWrapper.hide();
            $evaluationListWrapper.show();
            $(this).text('Xem câu trả lời đã đánh giá');
        } else {
            console.log("Ẩn $evaluationListWrapper, hiển thị $userEvaluationsWrapper");
            $evaluationListWrapper.hide();
            fetchUserEvaluations();
            $(this).text('Ẩn câu trả lời đã đánh giá');
        }
    });

    // Gắn sự kiện hover và click cho đánh giá
    CommonUtils.attachEvaluationHoverEvents($questionContent);
    CommonUtils.attachEvaluationClickEvents($questionContent, fetchQuestionDetails);

    // Render dữ liệu ban đầu
    let evaluationsData = window.evaluations;
    if (typeof evaluationsData === 'undefined') {
        console.error("window.evaluations is not defined");
        evaluationsData = [];
    }

    if (Array.isArray(evaluationsData) && evaluationsData.length > 0) {
        renderEvaluations(evaluationsData);
        CommonUtils.renderPagination($pagination, window.totalPages, window.currentPage, fetchEvaluations, {
            hideDetailsOnClick: true,
            $detailsElement: $questionDetails
        });
    } else {
        $evaluationList.html('<p>Không có đánh giá nào để hiển thị.</p>');
    }
});