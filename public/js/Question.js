$(document).ready(function () {
    console.log("Question.js loaded successfully");
    console.log("Dữ liệu window.questions ban đầu:", window.questions);

    // Khai báo các biến DOM và trạng thái
    const $questionList = $('#questionList'); // Danh sách câu hỏi
    const $searchInput = $('#searchInput');   // Ô input tìm kiếm
    const $tagList = $('#tagList');           // Danh sách tag
    const $pagination = $('.pagination');     // Phân trang
    let selectedTag = null;                   // Tag hiện tại được chọn
    let allTags = [];                         // Mảng lưu tất cả tag từ server
    let role = window.role;                   // Role của người dùng (có thể là null)
    let userID = window.userID;               // ID người dùng (có thể là null)
    let username = window.username;           // Username (có thể là null)

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
                const evaluationsHtml = answer.evaluations && answer.evaluations.length > 0
                    ? answer.evaluations.map(eval => {
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
                        </div>
                    `);
                $answerSection.append($answerDiv);
            });

            $questionList.append($questionDiv);
        });

        // Sự kiện hover để hiển thị/ẩn đánh giá
        $questionList.on('mouseenter', '.toggle-evaluations', function () {
            const answerId = $(this).data('answer-id');
            $(`#evaluations-${answerId}`).show();
            updateStars();
            console.log(`Hiển thị đánh giá cho câu trả lời ${answerId}`);
        });

        $questionList.on('mouseleave', '.toggle-evaluations', function () {
            const answerId = $(this).data('answer-id');
            $(`#evaluations-${answerId}`).hide();
            console.log(`Ẩn đánh giá cho câu trả lời ${answerId}`);
        });

        // Sự kiện click để hiển thị/ẩn answer-section
        $questionList.on('click', '.question-item', function (e) {
            // Tránh toggle answer-section khi click vào button-answer, input, hoặc submit-answer
            if (!$(e.target).hasClass('button-answer') && !$(e.target).is('input') && !$(e.target).hasClass('submit-answer')) {
                const questionId = $(this).data('question-id');
                $(`#answers-${questionId}`).toggle();
            }
        });

        // Sự kiện click vào nút button-answer để hiển thị ô nhập liệu
        $questionList.on('click', '.button-answer', function () {
            const questionId = $(this).closest('.question-item').data('question-id');
            const $answerSection = $(`#answers-${questionId}`);

            // Hiển thị answer-section nếu đang ẩn
            $answerSection.show();

            // Kiểm tra xem ô nhập liệu đã tồn tại chưa
            if ($answerSection.find('.user-answer').length === 0) {
                // Tạo ô nhập liệu và nút Gửi
                const $userAnswer = $('<div>')
                    .addClass('user-answer')
                    .html(`
                        <input type="text" id="answerInput-${questionId}" placeholder="Nhập câu trả lời của bạn">
                        <button class="submit-answer">Gửi</button>
                    `);

                // Thêm ô nhập liệu vào đầu answer-section
                $answerSection.prepend($userAnswer);
            }
        });

        // Sự kiện click vào nút Gửi để gửi câu trả lời qua AJAX
        $questionList.on('click', '.submit-answer', function () {
            const $answerSection = $(this).closest('.answer-section');
            const questionId = $answerSection.attr('id').replace('answers-', '');
            const $answerInput = $answerSection.find(`#answerInput-${questionId}`);
            const answerText = $answerInput.val().trim();

            if (!answerText) {
                alert('Vui lòng nhập câu trả lời!');
                return;
            }

            // Gửi AJAX để lưu câu trả lời
            $.ajax({
                url: '/QAReviewer/Answer/Create',
                method: 'POST',
                data: { questionId: questionId, answerText: answerText },
                success: function (response) {
                    if (response.success) {
                        alert('Câu trả lời đã được thêm thành công!');
                        // Xóa ô nhập liệu sau khi gửi thành công
                        $answerSection.find('.user-answer').remove();
                        // Tải lại danh sách câu hỏi
                        fetchQuestions(1);
                    } else {
                        alert('Lỗi khi thêm câu trả lời: ' + response.message);
                    }
                },
                error: function (xhr, status, error) {
                    console.error("Lỗi AJAX:", xhr, status, error);
                    alert('Lỗi khi thêm câu trả lời: ' + error);
                }
            });
        });

        updateStars();
    }

    // Hàm cập nhật hiển thị sao đánh giá
    function updateStars() {
        $('.rating-stars').each(function () {
            const rating = parseFloat($(this).data('rating')) || 0;
            if (isNaN(rating)) return;

            const fullStars = Math.floor(rating);
            const hasHalfStar = rating % 1 !== 0;
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

    // Hàm render phân trang
    function renderPagination(totalPages, currentPage) {
        $pagination.empty();

        for (let i = 1; i <= totalPages; i++) {
            const activeClass = i === currentPage ? 'active' : '';
            const $pageBtn = $('<button>')
                .addClass(`page-btn ${activeClass}`)
                .attr('data-page', i)
                .text(i);
            $pagination.append($pageBtn);
        }
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
                renderPagination(data.totalPages, data.currentPage);
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
                    renderPagination(data.totalPages, data.currentPage);
                    renderTagList();
                } else {
                    console.error("Cấu trúc JSON không hợp lệ:", data);
                    alert("Dữ liệu trả về không hợp lệ.");
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

    // Render dữ liệu ban đầu khi truy cập trang
    let questionsData = window.questions;
    if (typeof questionsData === 'undefined') {
        console.error("window.questions is not defined");
        questionsData = [];
    }

    if (Array.isArray(questionsData) && questionsData.length > 0) {
        renderQuestions(questionsData);
        renderPagination(window.totalPages, window.currentPage);
    } else {
        $questionList.html('<p>Không có câu hỏi nào để hiển thị.</p>');
    }

    // Lấy danh sách tag từ server
    fetchAllTags();

    // Gắn sự kiện click cho nút phân trang
    $(document).on('click', '.page-btn', function () {
        const page = $(this).data('page');
        fetchQuestions(page);
    });

    // Gắn sự kiện tìm kiếm khi người dùng nhập
    $searchInput.on('input', function () {
        const searchTerm = $searchInput.val().toLowerCase().trim();
        getQuestionBySearch(searchTerm, 1);

        let filteredQuestions = window.questions || [];
        if (searchTerm) {
            filteredQuestions = filteredQuestions.filter(question => {
                const text = (question.text || "").toLowerCase();
                const asker = (question.asker || "").toLowerCase();
                const tags = parseTags(question.tags);
                const matchText = text.includes(searchTerm);
                const matchAsker = asker.includes(searchTerm);
                const matchTags = tags.some(tag => tag.toLowerCase().includes(searchTerm));
                return matchText || matchAsker || matchTags;
            });
        }

        renderQuestions(filteredQuestions);
        renderTagList();
    });
});