<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo Câu Hỏi Mới</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #e7f3ff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Main Content */
        .container {
            background: #ffffff;
            max-width: 500px;
            width: 90%;
            margin: 30px auto;
            padding: 20px;
            border-radius: 10px;
            border-top: 3px solid #007bff;
            position: relative;
        }

        h2 {
            text-align: center;
            color: #007bff;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
            font-weight: 500;
        }

        label {
            display: block;
            font-weight: bold;
            /* Làm chữ đậm */
            color: #333;
            /* Màu chữ tối hơn */
            margin-bottom: 5px;
            font-size: 14px;
        }

        textarea,
        input[type="text"] {
            width: 100%;
            padding: 10px 12px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            color: #333;
            background: #f1f5f9;
            transition: background 0.3s ease;
        }

        textarea {
            height: 80px;
            resize: vertical;
        }

        textarea:focus,
        input[type="text"]:focus {
            background: #e5e7eb;
            outline: none;
        }

        .error-message {
            color: #dc2626;
            font-size: 12px;
            margin-top: 5px;
            display: none;
            font-style: italic;
        }

        .message-box {
            text-align: center;
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
            font-size: 14px;
            display: none;
        }

        .success-message {
            background: #e7ffe7;
            color: #2f855a;
        }

        .error-message-box {
            background: #fee2e2;
            color: #dc2626;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: #ffffff;
            border: none;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background: #0056b3;
        }

        button.loading::after {
            content: '';
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-left: 8px;
            vertical-align: middle;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
                margin: 15px;
            }

            h2 {
                font-size: 18px;
            }

            textarea,
            input[type="text"],
            button {
                font-size: 13px;
            }
        }

        /* Định dạng placeholder để cùng font với nội dung nhập */
        textarea::placeholder,
        input[type="text"]::placeholder {
            font-family: Arial, sans-serif;
            /* Font giống nội dung */
            font-size: 14px;
            /* Kích thước chữ */
            color: #6b7280;
            /* Màu placeholder */
            font-weight: 500;
            /* Đậm vừa phải */
        }
    </style>
</head>

<body>
    <!-- Main Content -->
    <div class="container">
        <h2>Tạo Câu Hỏi Mới</h2>
        <form id="createQuestionForm" action="/QAReviewer/Question/Create" method="POST">
            <div class="form-group">
                <label for="question">Câu Hỏi:</label>
                <textarea id="question" name="question" placeholder="Nhập câu hỏi của bạn..." required></textarea>
                <div class="error-message" id="question-error"></div>
            </div>
            <div class="form-group">
                <label for="tags">Thẻ (cách nhau bởi dấu phẩy):</label>
                <input type="text" id="tags" name="tags" placeholder="Ví dụ: php, javascript, html" required>
                <div class="error-message" id="tags-error"></div>
            </div>
            <button type="submit" id="submitBtn">Tạo Câu Hỏi</button>
            <div class="message-box" id="message-box"></div>
        </form>
    </div>

    <script>
        const form = document.getElementById('createQuestionForm');
        const submitBtn = document.getElementById('submitBtn');
        const messageBox = document.getElementById('message-box');

        // Hàm hiển thị thông báo
        function showMessage(message, type) {
            messageBox.textContent = message;
            messageBox.className = 'message-box';
            messageBox.classList.add(type === 'success' ? 'success-message' : 'error-message-box');
            messageBox.style.display = 'block';
        }

        // Xử lý validate và submit form
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Reset thông báo và trạng thái
            messageBox.style.display = 'none';
            const questionError = document.getElementById('question-error');
            const tagsError = document.getElementById('tags-error');
            questionError.style.display = 'none';
            tagsError.style.display = 'none';

            // Lấy giá trị đầu vào
            const question = document.getElementById('question').value.trim();
            const tags = document.getElementById('tags').value.trim();
            let isValid = true;

            // Kiểm tra câu hỏi
            if (!question) {
                questionError.textContent = 'Vui lòng nhập câu hỏi.';
                questionError.style.display = 'block';
                isValid = false;
            } else if (question.length < 10) {
                questionError.textContent = 'Câu hỏi phải có ít nhất 10 ký tự.';
                questionError.style.display = 'block';
                isValid = false;
            } else if (question.length > 500) {
                questionError.textContent = 'Câu hỏi không được dài quá 500 ký tự.';
                questionError.style.display = 'block';
                isValid = false;
            }

            // Kiểm tra tags
            if (!tags) {
                tagsError.textContent = 'Vui lòng nhập ít nhất một thẻ.';
                tagsError.style.display = 'block';
                isValid = false;
            } else {
                const tagArray = tags.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0);
                if (tagArray.length === 0) {
                    tagsError.textContent = 'Vui lòng nhập ít nhất một thẻ hợp lệ.';
                    tagsError.style.display = 'block';
                    isValid = false;
                } else if (tagArray.length > 5) {
                    tagsError.textContent = 'Không được nhập quá 5 thẻ.';
                    tagsError.style.display = 'block';
                    isValid = false;
                } else {
                    for (let tag of tagArray) {
                        if (tag.length < 2) {
                            tagsError.textContent = 'Mỗi thẻ phải có ít nhất 2 ký tự.';
                            tagsError.style.display = 'block';
                            isValid = false;
                            break;
                        }
                        if (tag.length > 20) {
                            tagsError.textContent = 'Mỗi thẻ không được dài quá 20 ký tự.';
                            tagsError.style.display = 'block';
                            isValid = false;
                            break;
                        }
                        if (!/^[a-zA-Z0-9]+$/.test(tag)) {
                            tagsError.textContent = 'Thẻ chỉ được chứa chữ cái và số, không chứa ký tự đặc biệt.';
                            tagsError.style.display = 'block';
                            isValid = false;
                            break;
                        }
                    }
                }
            }

            if (!isValid) return;

            // Hiển thị trạng thái loading
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');
            submitBtn.textContent = 'Đang tạo...';

            try {
                const formData = new FormData(form);
                const response = await fetch('/QAReviewer/Question/Create', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    showMessage('Câu hỏi đã được tạo thành công!', 'success');
                    form.reset(); // Reset form sau khi tạo thành công
                } else {
                    showMessage(result.message || 'Lỗi khi tạo câu hỏi.', 'error');
                }
            } catch (error) {
                showMessage('Đã có lỗi xảy ra. Vui lòng thử lại!', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.classList.remove('loading');
                submitBtn.textContent = 'Tạo Câu Hỏi';
            }
        });
    </script>
</body>

</html>