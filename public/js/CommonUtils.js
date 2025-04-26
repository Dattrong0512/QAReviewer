const CommonUtils = {
    // Hàm cập nhật hiển thị sao đánh giá
    updateStars: function () {
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
    },

    // Hàm render phân trang
    renderPagination: function ($pagination, totalPages, currentPage, onPageChange, options = {}) {
        $pagination.empty();

        const { maxVisiblePages = 5, hideDetailsOnClick = false, $detailsElement = null } = options;

        // Nút "Previous"
        const $prevBtn = $('<button>')
            .addClass('page-btn')
            .text('«')
            .prop('disabled', currentPage === 1)
            .on('click', function () {
                if (currentPage > 1) {
                    onPageChange(currentPage - 1);
                    if (hideDetailsOnClick && $detailsElement) {
                        $detailsElement.hide();
                    }
                }
            });
        $pagination.append($prevBtn);

        // Các nút số trang
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
                    onPageChange(page);
                    if (hideDetailsOnClick && $detailsElement) {
                        $detailsElement.hide();
                    }
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
                    onPageChange(currentPage + 1);
                    if (hideDetailsOnClick && $detailsElement) {
                        $detailsElement.hide();
                    }
                }
            });
        $pagination.append($nextBtn);
    },

    // Hàm gắn sự kiện hover để hiển thị/ẩn đánh giá
    attachEvaluationHoverEvents: function ($container) {
        $container.on('mouseenter', '.toggle-evaluations', function () {
            const answerId = $(this).data('answer-id');
            const $evaluationDetails = $(`#evaluations-${answerId}`);
            if ($evaluationDetails.length) {
                $evaluationDetails.show();
                CommonUtils.updateStars();
                console.log(`Hiển thị đánh giá cho câu trả lời ${answerId}`);
            }
        });

        $container.on('mouseleave', '.toggle-evaluations', function () {
            const answerId = $(this).data('answer-id');
            const $evaluationDetails = $(`#evaluations-${answerId}`);
            if ($evaluationDetails.length) {
                $evaluationDetails.hide();
                console.log(`Ẩn đánh giá cho câu trả lời ${answerId}`);
            }
        });
    },

    // Hàm gắn sự kiện click cho nút đánh giá
    attachEvaluationClickEvents: function ($container, refreshCallback) {
        $container.on('click', '.evaluater-create-rating', function (e) {
            e.stopPropagation(); // Ngăn sự kiện click lan tỏa
            const $button = $(this);
            const answerId = $button.data('answer-id');
            const refreshId = $button.closest('.question-item').data('question-id') || answerId; // ID để làm mới dữ liệu

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
                            // Gọi callback để làm mới dữ liệu
                            if (refreshCallback) {
                                refreshCallback(refreshId);
                            }
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
    }
};