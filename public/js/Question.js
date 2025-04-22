$(document).ready(function () {
    console.log("Home.js loaded successfully");

    const $questionList = $('#questionList');
    const $searchInput = $('#searchInput');
    const $tagList = $('#tagList');
    const $pagination = $('.pagination');
    let selectedTag = null; // Biến để lưu tag được chọn
    let allTags = []; // Biến để lưu tất cả tags từ server

    // Hàm chuyển đổi tags từ string thành mảng
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

        // Luôn hiển thị tất cả tags từ allTags
        allTags.forEach(tag => {
            const $tag = $('<span>')
                .addClass('tag')
                .text(tag)
                .data('tag', tag)
                .on('click', function () {
                    selectedTag = $(this).data('tag');
                    fetchQuestions(1); // Quay lại trang 1 khi chọn tag mới
                });

            // Nếu tag này đang được chọn, thêm lớp active
            if (selectedTag === tag) {
                $tag.addClass('active');
            }

            $tagList.append($tag);
        });

        // Thêm nút "Tất cả"
        const $allTag = $('<span>')
            .addClass('tag')
            .text('Tất cả')
            .on('click', function () {
                selectedTag = null;
                fetchQuestions(1); // Quay lại trang 1 khi bỏ chọn tag
            });

        // Nếu không có tag nào được chọn, đánh dấu "Tất cả" là active
        if (!selectedTag) {
            $allTag.addClass('active');
        }

        $tagList.prepend($allTag);
    }

    // Hàm render danh sách câu hỏi
    function renderQuestions(questions) {
        $questionList.empty();
        if (questions.length === 0) {
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
                    <h3>${question.text}</h3>
                    <div class="question-meta">
                        Đặt bởi: ${question.asker} | ${question.createdDate}
                    </div>
                    ${tagsHtml}
                    <div class="answer-section" id="answers-${question.id}" style="display: none;"></div>
                `);

            const $answerSection = $questionDiv.find(`#answers-${question.id}`);
            question.answers.forEach(answer => {
                const ratingInfoHtml = answer.numberEvaluators > 0 ? `
                    <div class="rating-info">
                        Số lượt đánh giá: <span class="toggle-evaluations" data-answer-id="${answer.id}">${answer.numberEvaluators}</span>
                        <div class="evaluation-details" id="evaluations-${answer.id}">
                            ${answer.evaluations.map(eval => {
                                console.log(`Evaluator: ${eval.evaluator}, Rating: ${eval.rating}`);

                                const ratingValue = parseFloat(eval.rating).toFixed(1);
                                const starText = `${ratingValue} STAR`;

                                return `
                                    <div class="evaluation-item">
                                        <span class="evaluator-name">${eval.evaluator}</span>
                                        <span class="rating-text">${starText}</span>
                                        <div class="rating-stars" data-rating="${ratingValue}"></div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                ` : '';

                const $answerDiv = $('<div>').addClass('answer-item').html(`
                    <div class="answer-content-wrapper">
                        <div class="answer-content">${answer.text}</div>
                        <div class="answer-meta">
                            Trả lời bởi: ${answer.answerer} | ${answer.createdDate}
                        </div>
                    </div>
                    <div class="rating-stars" data-rating="${answer.averageRating.toFixed(1)}"></div>
                    ${ratingInfoHtml}
                `);
                $answerSection.append($answerDiv);
            });

            $questionList.append($questionDiv);
        });

        $questionList.on('mouseenter', '.toggle-evaluations', function () {
            const answerId = $(this).data('answer-id');
            const $evaluations = $(`#evaluations-${answerId}`);
            $evaluations.css('display', 'block');
            updateStars();
            console.log(`Showing evaluations for answer ${answerId}`);
        });

        $questionList.on('mouseleave', '.toggle-evaluations', function () {
            const answerId = $(this).data('answer-id');
            const $evaluations = $(`#evaluations-${answerId}`);
            $evaluations.css('display', 'none');
            console.log(`Hiding evaluations for answer ${answerId}`);
        });

        $questionList.on('click', '.question-item', function () {
            const questionId = $(this).data('question-id');
            const $answerSection = $(`#answers-${questionId}`);
            $answerSection.toggle();
        });

        updateStars();
    }

    // Hàm cập nhật ngôi sao đánh giá
    function updateStars() {
        $('.rating-stars').each(function () {
            const rating = parseFloat($(this).data('rating'));
            if (isNaN(rating)) return;

            const fullStars = Math.floor(rating);
            const emptyStars = 5 - fullStars;

            let starsHtml = '';
            for (let i = 0; i < fullStars; i++) {
                starsHtml += '<span class="star full">★</span>';
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

    // Hàm fetch tất cả tags từ server
    function fetchAllTags() {
        $.ajax({
            url: '/QAReviewer/Question/GetAllTags',
            method: 'GET',
            dataType: 'json',
            success: function (tags) {
                allTags = tags; // Lưu tất cả tags vào biến toàn cục
                renderTagList(); // Render tag list ngay sau khi lấy được tags
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error (GetAllTags):", xhr, status, error);
                alert('Lỗi khi tải danh sách tags: ' + error);
            }
        });
    }

    // Hàm fetch câu hỏi từ server
    function fetchQuestions(page) {
        const url = selectedTag
            ? `/QAReviewer/Question/Filter?tag=${encodeURIComponent(selectedTag)}&page=${page}`
            : `/QAReviewer/Question/List?page=${page}`;

        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                window.questions = data.questions; // Cập nhật dữ liệu toàn cục
                renderQuestions(data.questions);
                renderPagination(data.totalPages, data.currentPage);
                renderTagList(); // Cập nhật tag list để đánh dấu tag đang chọn
                if (data.message) {
                    alert(data.message);
                }

                const newUrl = selectedTag
                    ? `/QAReviewer/Question/Filter?tag=${encodeURIComponent(selectedTag)}&page=${page}`
                    : `/QAReviewer/Question/List?page=${page}`;
                window.history.pushState({ page: page, tag: selectedTag }, '', newUrl);
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error:", xhr, status, error);
                alert('Lỗi khi tải dữ liệu: ' + error);
            }
        });
    }

    // Khởi tạo
    if (typeof window.questions === 'undefined') {
        console.error("window.questions is not defined");
    } else {
        console.log("window.questions:", window.questions);
        renderQuestions(window.questions);
    }

    // Lấy tất cả tags khi trang load
    fetchAllTags();

    // Sự kiện click cho phân trang
    $(document).on('click', '.page-btn', function () {
        const page = $(this).data('page');
        fetchQuestions(page);
    });

    // Sự kiện tìm kiếm (client-side)
    $searchInput.on('input', function () {
        const searchTerm = $searchInput.val().toLowerCase().trim();
        let filteredQuestions = window.questions;

        if (searchTerm) {
            filteredQuestions = filteredQuestions.filter(question => {
                const matchText = question.text.toLowerCase().includes(searchTerm);
                const matchAsker = question.asker.toLowerCase().includes(searchTerm);
                const tags = parseTags(question.tags);
                const matchTags = tags.some(tag => tag.toLowerCase().includes(searchTerm));
                return matchText || matchAsker || matchTags;
            });
        }

        renderQuestions(filteredQuestions);
        renderTagList(); // Cập nhật tag list để đánh dấu tag đang chọn
    });
});