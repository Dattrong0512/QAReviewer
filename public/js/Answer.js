$(document).ready(function () {
    console.log("LatestAnswers.js loaded successfully");
    console.log("Dữ liệu window.answers ban đầu:", window.answers);

    // Khai báo các biến DOM và trạng thái
    const $answerList = $('#answerList');     // Danh sách câu trả lời
    const $pagination = $('.pagination');     // Phân trang
    const $questionDetails = $('#questionDetails'); // Khu vực hiển thị chi tiết câu hỏi
    const $questionContent = $('#questionContent'); // Nội dung chi tiết câu hỏi
    let role = window.role;                   // Role của người dùng
    let userID = window.userID;               // ID người dùng
    let username = window.username;           // Username

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

    // Hàm render phân trang
    function renderPagination(totalPages, currentPage) {
        $pagination.empty();

        // Nút "Previous"
        const $prevBtn = $('<button>')
            .addClass('page-btn')
            .text('«')
            .prop('disabled', currentPage === 1)
            .on('click', function () {
                if (currentPage > 1) {
                    fetchAnswers(currentPage - 1);
                    $questionDetails.hide();
                }
            });
        $pagination.append($prevBtn);

        // Các nút số trang
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        startPage = Math.max(1, endPage - maxVisiblePages + 1);

        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === currentPage ? 'active' : '';
            const $pageBtn = $('<button>')
                .addClass(`page-btn ${activeClass}`)
                .attr('data-page', i)
                .text(i)
                .on('click', function () {
                    const page = $(this).data('page');
                    fetchAnswers(page);
                    $questionDetails.hide();
                });
            $pagination.append($pageBtn);
        }

        // Nút "Next"
        const $nextBtn = $('<button>')
            .addClass('page-btn')
            .text('»')
            .prop('disabled', currentPage === totalPages)
            .on('click', function () {
                if (currentPage < totalPages) {
                    fetchAnswers(currentPage + 1);
                    $questionDetails.hide();
                }
            });
        $pagination.append($nextBtn);
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
                    renderPagination(response.data.totalPages, response.data.currentPage);
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

        // Gom nhóm câu hỏi và câu trả lời
        const questionMap = {};
        questions.forEach(item => {
            const questionId = item.QuestionID;
            if (!questionMap[questionId]) {
                questionMap[questionId] = {
                    text: item.Question,
                    asker: item.UserName,
                    createdDate: item.CreatedDate,
                    tags: item.Tags,
                    answers: []
                };
            }
            if (item.AnswerID) {
                questionMap[questionId].answers.push({
                    id: item.AnswerID,
                    text: item.Answer,
                    answerer: item.UserName1,
                    createdDate: item.CreatedDate1,
                    averageRating: item.AverageRating || 0,
                    numberEvaluators: item.NumberEvaluaters || 0,
                    evaluations: item.EvaluatorUserName ? [{
                        evaluator: item.EvaluatorUserName,
                        rating: item.RateCategory
                    }] : []
                });
            }
        });

        const question = Object.values(questionMap)[0];
        const tags = question.tags ? question.tags.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0) : [];
        const tagsHtml = tags.length > 0
            ? `<div class="question-tags">${tags.map(tag => `<span class="tag">${tag}</span>`).join('')}</div>`
            : '';

        const $questionDiv = $('<div>')
            .addClass('question-item')
            .attr('data-question-id', question.id)
            .html(`
                <div class="header-wrapper">
                    <h3>${question.text}</h3>
                </div>
                <div class="question-meta">
                    <span class="meta-label">Đặt bởi:</span> ${question.asker || 'Ẩn danh'} <br>
                    <span class="meta-label">Ngày tạo:</span> ${question.createdDate}
                </div>
                ${tagsHtml}
                <div class="answer-section">
                    <h4>Câu trả lời</h4>
                </div>
            `);

        const $answerSection = $questionDiv.find('.answer-section');
        if (question.answers.length === 0) {
            $answerSection.append('<p>Chưa có câu trả lời nào.</p>');
        } else {
            question.answers.forEach(answer => {
                // Xử lý danh sách đánh giá
                let processedEvaluations = [];
                if (answer.evaluations && answer.evaluations.length > 0) {
                    const evalMap = {};
                    answer.evaluations.forEach(eval => {
                        const evaluator = eval.evaluator;
                        if (!evalMap[evaluator]) {
                            evalMap[evaluator] = { ratings: [], evaluator: evaluator };
                        }
                        evalMap[evaluator].ratings.push(parseFloat(eval.rating) || 0);
                    });

                    for (const evaluator in evalMap) {
                        const ratings = evalMap[evaluator].ratings;
                        const averageRating = ratings.reduce((sum, rating) => sum + rating, 0) / ratings.length;
                        processedEvaluations.push({
                            evaluator: evaluator,
                            rating: averageRating
                        });
                    }
                }

                const evaluationsHtml = processedEvaluations.length > 0
                    ? processedEvaluations.map(eval => {
                        const ratingValue = parseFloat(eval.rating) || 0;
                        return `
                            <div class="evaluation-item">
                                <span class="evaluator-name">${eval.evaluator}</span>
                                <div class="rating-stars" data-rating="${ratingValue}"></div>
                            </div>
                        `;
                    }).join('')
                    : '<p>Chưa có đánh giá nào.</p>';

                const ratingInfoHtml = `
                    <div class="rating-info">
                        Số lượt đánh giá: <span class="toggle-evaluations" data-answer-id="${answer.id}">
                            ${answer.numberEvaluators || 0}
                        </span>
                        <div class="evaluation-details" id="evaluations-${answer.id}">
                            ${evaluationsHtml}
                        </div>
                    </div>
                `;

                const hasUserEvaluated = processedEvaluations.some(eval => eval.evaluator === username);
                const evaluaterRating = (role === 'Evaluater' || role === 'Admin') && !hasUserEvaluated
                    ? `<button class="evaluater-create-rating" data-answer-id="${answer.id}">Đánh giá</button>`
                    : '';

                const $answerDiv = $('<div>')
                    .addClass('answer-item')
                    .html(`
                        <div class="answer-content-wrapper">
                            <div class="answer-content">${answer.text}</div>
                            <div class="answer-meta">
                                <span class="meta-label">Trả lời bởi:</span> ${answer.answerer || 'Ẩn danh'} <br>
                                <span class="meta-label">Ngày tạo:</span> ${answer.createdDate}
                            </div>
                        </div>
                        <div class="rating-wrapper">
                            <div class="rating-stars" data-rating="${answer.averageRating || 0}"></div>
                            ${ratingInfoHtml}
                            ${evaluaterRating}
                        </div>
                    `);
                $answerSection.append($answerDiv);
            });
        }

        $questionContent.append($questionDiv);
        updateStars();
    }

    // Hàm cập nhật hiển thị sao đánh giá
    function updateStars() {
        $('.rating-stars').each(function () {
            let rating = parseFloat($(this).data('rating')) || 0;
            if (isNaN(rating)) return;

            rating = Math.min(Math.max(rating, 0), 5);
            const fullStars = Math.floor(rating);
            const hasHalfStar = rating % 1 >= 0.5;
            const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);

            let starsHtml = '';
            for (let i = 0; i < fullStars; i++) {
                starsHtml += '<span class="star full">★</span>';
            }
            if (hasHalfStar) {
                starsHtml += '<span class="star half">★</span>';
            }
            for (let i = 0; i < emptyStars; i++) {
                starsHtml += '<span class="star empty">☆</span>';
            }

            $(this).html(starsHtml);
        });
    }

    // Sự kiện click để hiển thị chi tiết câu hỏi và làm nổi bật câu trả lời được chọn
    $answerList.on('click', '.answer-item', function () {
        $('.answer-item').removeClass('selected');
        $(this).addClass('selected');
        
        const questionId = $(this).data('question-id');
        fetchQuestionDetails(questionId);
    });

    // Sự kiện hover để hiển thị/ẩn đánh giá
    $questionContent.on('mouseenter', '.toggle-evaluations', function () {
        const answerId = $(this).data('answer-id');
        const $evaluationDetails = $(`#evaluations-${answerId}`);
        if ($evaluationDetails.length) {
            $evaluationDetails.show();
            updateStars();
            console.log(`Hiển thị đánh giá cho câu trả lời ${answerId}`);
        }
    });

    $questionContent.on('mouseleave', '.toggle-evaluations', function () {
        const answerId = $(this).data('answer-id');
        const $evaluationDetails = $(`#evaluations-${answerId}`);
        if ($evaluationDetails.length) {
            $evaluationDetails.hide();
            console.log(`Ẩn đánh giá cho câu trả lời ${answerId}`);
        }
    });

    // Sự kiện click cho nút đánh giá
    $questionContent.on('click', '.evaluater-create-rating', function (e) {
        e.stopPropagation(); // Ngăn sự kiện click lan tỏa
        const $button = $(this);
        const answerId = $button.data('answer-id');

        // Kiểm tra nếu toggle đã tồn tại, thì xóa đi
        const $existingToggle = $button.find('.rating-toggle');
        if ($existingToggle.length) {
            $existingToggle.remove();
            return;
        }

        // Tạo toggle 1-5 sao
        const $ratingToggle = $('<div>')
            .addClass('rating-toggle')
            .html(`
                <div class="star-toggle" data-value="1">★</div>
                <div class="star-toggle" data-value="2">★</div>
                <div class="star-toggle" data-value="3">★</div>
                <div class="star-toggle" data-value="4">★</div>
                <div class="star-toggle" data-value="5">★</div>
            `);

        // Thêm toggle trực tiếp vào nút .evaluater-create-rating
        $button.append($ratingToggle);

        // Sự kiện hover cho các sao
        $ratingToggle.find('.star-toggle').on('mouseover', function () {
            const hoverValue = $(this).data('value');
            $ratingToggle.find('.star-toggle').each(function () {
                const starValue = $(this).data('value');
                if (starValue <= hoverValue) {
                    $(this).addClass('hover');
                } else {
                    $(this).removeClass('hover');
                }
            });
        });

        // Khi chuột rời khỏi toggle, xóa trạng thái hover
        $ratingToggle.on('mouseleave', function () {
            $ratingToggle.find('.star-toggle').removeClass('hover');
        });

        // Sự kiện click cho các sao trong toggle
        $ratingToggle.find('.star-toggle').on('click', function () {
            const rating = $(this).data('value');
            $ratingToggle.find('.star-toggle').each(function () {
                const starValue = $(this).data('value');
                if (starValue <= rating) {
                    $(this).addClass('selected');
                } else {
                    $(this).removeClass('selected');
                }
            });

            // Gửi AJAX để lưu đánh giá
            $.ajax({
                url: '/QAReviewer/Evaluater/Create',
                method: 'POST',
                dataType: 'json',
                data: {
                    answerId: answerId,
                    rating: rating
                },
                success: function (response) {
                    if (response.success) {
                        alert('Đánh giá thành công!');
                        $ratingToggle.remove();
                        fetchQuestionDetails(questionId); // Cập nhật lại chi tiết câu hỏi
                    } else {
                        alert('Lỗi khi gửi đánh giá: ' + response.message);
                    }
                },
                error: function (xhr, status, error) {
                    console.error("Lỗi AJAX (Evaluation):", xhr, status, error);
                    console.log("Phản hồi server:", xhr.responseText);
                    alert('Lỗi khi gửi đánh giá: ' + error);
                }
            });
        });
    });

    // Render dữ liệu ban đầu
    let answersData = window.answers;
    if (typeof answersData === 'undefined') {
        console.error("window.answers is not defined");
        answersData = [];
    }

    if (Array.isArray(answersData) && answersData.length > 0) {
        renderAnswers(answersData);
        renderPagination(window.totalPages, window.currentPage);
    } else {
        $answerList.html('<p>Không có câu trả lời nào để hiển thị.</p>');
    }
});