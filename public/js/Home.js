$(document).ready(function () {
    console.log("Home.js loaded successfully");

    const $questionList = $('#questionList');
    const $searchInput = $('#searchInput');

    function renderQuestions(questions) {
        $questionList.empty();
        if (questions.length === 0) {
            $questionList.html('<p>Không có câu hỏi nào để hiển thị.</p>');
            return;
        }

        questions.forEach(question => {
            const $questionDiv = $('<div>')
                .addClass('question-item')
                .attr('data-question-id', question.id)
                .html(`
                    <h3>${question.text}</h3>
                    <div class="question-meta">
                        Đặt bởi: ${question.asker} | ${question.createdDate}
                    </div>
                    <div class="answer-section" id="answers-${question.id}" style="display: none;"></div>
                `);

            const $answerSection = $questionDiv.find(`#answers-${question.id}`);
            question.answers.forEach(answer => {
                const ratingInfoHtml = answer.numberEvaluators > 0 ? `
                    <div class="rating-info">
                        Số lượt đánh giá: <span class="toggle-evaluations" data-answer-id="${answer.id}">${answer.numberEvaluators}</span>
                        <div class="evaluation-details" id="evaluations-${answer.id}">
                            ${answer.evaluations.map(eval => {
                                // Debug: Kiểm tra giá trị eval.rating
                                console.log(`Evaluator: ${eval.evaluator}, Rating: ${eval.rating}`);

                                const ratingValue = parseFloat(eval.rating).toFixed(1);
                                // Tạo văn bản số sao (ví dụ: "4.0 STAR")
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
            $evaluations.css('display', 'block'); // Đảm bảo hiển thị
            updateStars(); // Gọi lại updateStars để render sao trong tooltip
            console.log(`Showing evaluations for answer ${answerId}`);
        });

        $questionList.on('mouseleave', '.toggle-evaluations', function () {
            const answerId = $(this).data('answer-id');
            const $evaluations = $(`#evaluations-${answerId}`);
            $evaluations.css('display', 'none'); // Ẩn khi rời chuột
            console.log(`Hiding evaluations for answer ${answerId}`);
        });

        $questionList.on('click', '.question-item', function () {
            const questionId = $(this).data('question-id');
            const $answerSection = $(`#answers-${questionId}`);
            $answerSection.toggle();
        });

        updateStars();
    }

    function updateStars() {
        $('.rating-stars').each(function () {
            const rating = parseFloat($(this).data('rating'));
            if (isNaN(rating)) return; // Bỏ qua nếu rating không hợp lệ

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

    if (typeof window.questions === 'undefined') {
        console.error("window.questions is not defined");
    } else {
        console.log("window.questions:", window.questions);
        renderQuestions(window.questions);
    }

    $(document).on('click', '.page-btn', function () {
        const page = $(this).data('page');
        $.ajax({
            url: '/QAReviewer/Question/List?page=' + page,
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                renderQuestions(data.questions);
                $('.page-btn').removeClass('active');
                $(`.page-btn[data-page="${data.currentPage}"]`).addClass('active');
                if (data.message) {
                    alert(data.message);
                }

                const newUrl = `/QAReviewer/Question/List?page=${page}`;
                window.history.pushState({ page: page }, '', newUrl);
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error:", xhr, status, error);
                alert('Lỗi khi tải dữ liệu phân trang: ' + error);
            }
        });
    });

    $searchInput.on('input', function () {
        const searchTerm = $(this).val().toLowerCase();
        const filteredQuestions = window.questions.filter(question =>
            question.text.toLowerCase().includes(searchTerm)
        );
        renderQuestions(filteredQuestions);
    });
});