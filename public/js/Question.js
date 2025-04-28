$(document).ready(function () {
    console.log("Question.js loaded successfully");
    console.log("Dữ liệu window.questions ban đầu:", window.questions);

    // Khai báo các biến DOM và trạng thái
    const $questionList = $('#questionList');
    const $searchInput = $('#searchInput');
    const $tagList = $('#tagList');
    const $pagination = $('.pagination');
    let selectedTag = null;
    let allTags = [];
    const role = window.role;
    const userID = window.userID;
    const username = window.username;

    console.log("Role:", role);
    console.log("User ID:", userID);
    console.log("Username:", username);

    // Hàm chuyển đổi tags từ chuỗi thành mảng
    function parseTags(tags) {
        if (typeof tags === 'string') {
            return tags.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0);
        } else if (Array.isArray(tags)) {
            return tags;
        }
        return [];
    }

    // Hàm render danh sách tag
    function renderTagList() {
        $tagList.empty();

        const $allTag = $('<span>')
            .addClass('tag')
            .text('Tất cả')
            .on('click', function () {
                selectedTag = null;
                fetchQuestions(1);
            });
        if (!selectedTag) {
            $allTag.addClass('active');
        }
        $tagList.append($allTag);

        allTags.forEach(tag => {
            const $tag = $('<span>')
                .addClass('tag')
                .text(tag)
                .data('tag', tag)
                .on('click', function () {
                    selectedTag = $(this).data('tag');
                    fetchQuestions(1);
                });
            if (selectedTag === tag) {
                $tag.addClass('active');
            }
            $tagList.append($tag);
        });
    }

    // Hàm render danh sách câu hỏi
    function renderQuestions(questions) {
        console.log("Rendering questions:", questions);
        $questionList.empty();

        if (!Array.isArray(questions) || questions.length === 0) {
            $questionList.html('<p>Không có câu hỏi nào để hiển thị.</p>');
            return;
        }

        questions.forEach(question => {
            const tags = parseTags(question.tags);
            const tagsHtml = tags.length > 0
                ? `<div class="question-tags">${tags.map(tag => `<span class="tag">${tag}</span>`).join('')}</div>`
                : '';

            const $questionDiv = $('<div>')
                .addClass('question-item')
                .attr('data-question-id', question.id)
                .html(`
                    <div class="header-wrapper">
                        <h3>${question.text}</h3>
                        ${role === 'Answerer' || role === 'Admin' ? '<button class="button-answer">Thêm câu trả lời</button>' : ''}
                    </div>
                    <div class="question-meta">
                        Đặt bởi: ${question.asker || 'Ẩn danh'} | ${question.createdDate}
                    </div>
                    ${tagsHtml}
                    <div class="answer-section" id="answers-${question.id}" style="display: none;"></div>
                `);

            const $answerSection = $questionDiv.find(`#answers-${question.id}`);
            question.answers.forEach(answer => {
                // Xử lý danh sách đánh giá: gom nhóm theo evaluator và tính trung bình nếu trùng
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
                const evaluatorRating = ((role === 'Evaluater'|| role === 'Admin') && !hasUserEvaluated)
                    ? `<button class="evaluater-create-rating" data-answer-id="${answer.id}">Đánh giá</button>`
                    : '';

                const $answerDiv = $('<div>')
                    .addClass('answer-item')
                    .html(`
                        <div class="answer-content-wrapper">
                            <div class="answer-content">${answer.text}</div>
                            <div class="answer-meta">
                                Trả lời bởi: ${answer.answerer || 'Ẩn danh'} | ${answer.createdDate}
                            </div>
                        </div>
                        <div class="rating-wrapper">
                            <div class="rating-stars" data-rating="${answer.averageRating || 0}"></div>
                            ${ratingInfoHtml}
                            ${evaluatorRating}
                        </div>
                    `);
                $answerSection.append($answerDiv);
            });

            $questionList.append($questionDiv);
        });

        CommonUtils.updateStars();
    }

    // Hàm lấy tất cả tag từ server
    function fetchAllTags() {
        $.ajax({
            url: '/QAReviewer/Question/GetAllTags',
            method: 'GET',
            dataType: 'json',
            success: function (tags) {
                allTags = tags;
                renderTagList();
            },
            error: function (xhr, status, error) {
                console.error("Lỗi AJAX (GetAllTags):", xhr, status, error);
                alert('Lỗi khi tải danh sách tags: ' + error);
            }
        });
    }

    // Hàm lấy danh sách câu hỏi từ server
    function fetchQuestions(page) {
        const url = selectedTag
            ? `/QAReviewer/Question/Filter?tag=${encodeURIComponent(selectedTag)}&page=${page}`
            : `/QAReviewer/Question/List?page=${page}`;

        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                window.questions = data.questions;
                renderQuestions(data.questions);
                CommonUtils.renderPagination($pagination, data.totalPages, data.currentPage, fetchQuestions);
                renderTagList();
                if (data.message) {
                    alert(data.message);
                }

                const newUrl = selectedTag
                    ? `/QAReviewer/Question/Filter?tag=${encodeURIComponent(selectedTag)}&page=${page}`
                    : `/QAReviewer/Question/List?page=${page}`;
                window.history.pushState({ page: page, tag: selectedTag }, '', newUrl);
            },
            error: function (xhr, status, error) {
                console.error("Lỗi AJAX:", xhr, status, error);
                alert('Lỗi khi tải dữ liệu: ' + error);
            }
        });
    }

    // Hàm tìm kiếm câu hỏi từ server
    function getQuestionBySearch(inputSearch, page) {
        const url = inputSearch
            ? `/QAReviewer/Question/Search?input=${encodeURIComponent(inputSearch)}&page=${page}`
            : `/QAReviewer/Question/List?page=${page}`;

        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                if (data && data.questions) {
                    window.questions = data.questions;
                    renderQuestions(data.questions);
                    CommonUtils.renderPagination($pagination, data.totalPages, data.currentPage, getQuestionBySearch.bind(null, inputSearch));
                    renderTagList();
                } else {
                    console.error("Cấu trúc JSON không hợp lệ:", data);
                    alert("Dữ liệu trả về không hợp lệ.");
                }
            },
            error: function (xhr, status, error) {
                console.error("Lỗi AJAX:", xhr, status, error);
                if (xhr.responseText) {
                    console.error("Ph反应 hồi server:", xhr.responseText);
                }
                alert('Lỗi khi tải dữ liệu: ' + error);
            }
        });
    }

    // Sự kiện click để hiển thị/ẩn answer-section
    $questionList.on('click', '.question-item', function (e) {
        if (!$(e.target).hasClass('button-answer') && !$(e.target).is('input') && !$(e.target).hasClass('submit-answer')) {
            const questionId = $(this).data('question-id');
            const $answerSection = $(`#answers-${questionId}`);
            if ($answerSection.length) {
                $answerSection.toggle();
                console.log(`Toggle answer-section cho câu hỏi ${questionId}`);
            }
        }
    });

    // Sự kiện click vào nút button-answer để hiển thị ô nhập liệu
    $questionList.on('click', '.button-answer', function () {
        const questionId = $(this).closest('.question-item').data('question-id');
        const $answerSection = $(`#answers-${questionId}`);

        $answerSection.show();

        if ($answerSection.find('.user-answer').length === 0) {
            const $userAnswer = $('<div>')
                .addClass('user-answer')
                .html(`
                    <input type="text" id="answerInput-${questionId}" placeholder="Nhập câu trả lời của bạn">
                    <button class="submit-answer">Gửi</button>
                `);
            $answerSection.prepend($userAnswer);
        }
    });

    // Sự kiện click vào nút Gửi để gửi câu trả lời qua AJAX
       // Sự kiện click vào nút Gửi để gửi câu trả lời qua AJAX
    $questionList.on('click', '.submit-answer', function () {
        const $answerSection = $(this).closest('.answer-section');
        const questionId = $answerSection.attr('id').replace('answers-', '');
        const $answerInput = $answerSection.find(`#answerInput-${questionId}`);
        const answerText = $answerInput.val().trim();

        // Reset thông báo lỗi cũ (nếu có)
        $answerSection.find('.error-message').remove();

        // Validation
        let isValid = true;
        let errorMessage = '';

        // Kiểm tra không để trống
        if (!answerText) {
            errorMessage = 'Vui lòng nhập câu trả lời.';
            isValid = false;
        }
        // Kiểm tra độ dài tối thiểu
        else if (answerText.length < 5) {
            errorMessage = 'Câu trả lời phải có ít nhất 10 ký tự.';
            isValid = false;
        }
        // Kiểm tra độ dài tối đa
        else if (answerText.length > 1000) {
            errorMessage = 'Câu trả lời không được vượt quá 1000 ký tự.';
            isValid = false;
        }
        // Kiểm tra nội dung không chỉ chứa ký tự không ý nghĩa
        else if (!/[a-zA-Z0-9]/.test(answerText)) {
            errorMessage = 'Câu trả lời phải chứa ít nhất một chữ cái hoặc số.';
            isValid = false;
        }

        // Nếu không hợp lệ, hiển thị thông báo lỗi
        if (!isValid) {
            const $errorDiv = $('<div>')
                .addClass('error-message')
                .text(errorMessage)
                .css({
                    color: '#dc2626',
                    fontSize: '12px',
                    marginTop: '5px',
                    fontStyle: 'italic'
                });
            $answerInput.after($errorDiv);
            return;
        }

        // Nếu hợp lệ, gửi AJAX
        $.ajax({
            url: '/QAReviewer/Answer/Create',
            method: 'POST',
            dataType: 'json',
            data: { questionId: questionId, answerText: answerText },
            beforeSend: function () {
                // Vô hiệu hóa nút gửi khi đang xử lý
                $(this).prop('disabled', true).text('Đang gửi...');
            },
            success: function (response) {
                if (response.success) {
                    const $successDiv = $('<div>')
                        .addClass('success-message')
                        .text('Câu trả lời đã được thêm thành công!')
                        .css({
                            color: '#2f855a',
                            fontSize: '12px',
                            marginTop: '5px',
                            textAlign: 'center'
                        });
                    alert('Câu trả lời đã được thêm thành công!');
                    $answerSection.prepend($successDiv);
                    $answerSection.find('.user-answer').remove();
                    fetchQuestions(1);
                } else {
                    const $errorDiv = $('<div>')
                        .addClass('error-message')
                        .text('Lỗi khi thêm câu trả lời: ' + response.message)
                        .css({
                            color: '#dc2626',
                            fontSize: '12px',
                            marginTop: '5px',
                            fontStyle: 'italic'
                        });
                    $answerInput.after($errorDiv);
                }
            },
            error: function (xhr, status, error) {
                console.error("Lỗi AJAX:", xhr, status, error);
                console.log("Phản hồi server:", xhr.responseText);
                const $errorDiv = $('<div>')
                    .addClass('error-message')
                    .text('Lỗi khi thêm câu trả lời: ' + (xhr.responseText || error || 'Không xác định'))
                    .css({
                        color: '#dc2626',
                        fontSize: '12px',
                        marginTop: '5px',
                        fontStyle: 'italic'
                    });
                $answerInput.after($errorDiv);
            },
            complete: function () {
                // Kích hoạt lại nút sau khi hoàn tất
                $(this).prop('disabled', false).text('Gửi');
            }
        });
    });

    // Gắn sự kiện hover và click cho đánh giá
    CommonUtils.attachEvaluationHoverEvents($questionList);
    CommonUtils.attachEvaluationClickEvents($questionList, fetchQuestions.bind(null, 1));

    // Gắn sự kiện click cho nút phân trang
    $(document).on('click', '.page-btn', function () {
        const page = $(this).data('page');
        const searchTerm = $searchInput.val().toLowerCase().trim();
        if (searchTerm) {
            getQuestionBySearch(searchTerm, page);
        } else {
            fetchQuestions(page);
        }
    });

    // Gắn sự kiện tìm kiếm khi người dùng nhập
    $searchInput.on('input', function () {
        const searchTerm = $searchInput.val().toLowerCase().trim();
        getQuestionBySearch(searchTerm, 1);
    });

    // Render dữ liệu ban đầu khi truy cập trang
    let questionsData = window.questions;
    if (typeof questionsData === 'undefined') {
        console.error("window.questions is not defined");
        questionsData = [];
    }

    if (Array.isArray(questionsData) && questionsData.length > 0) {
        renderQuestions(questionsData);
        CommonUtils.renderPagination($pagination, window.totalPages, window.currentPage, fetchQuestions);
    } else {
        $questionList.html('<p>Không có câu hỏi nào để hiển thị.</p>');
    }

    // Lấy danh sách tag từ server
    fetchAllTags();
});