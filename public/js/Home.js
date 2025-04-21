// public/js/Home.js

const questionList = document.getElementById('questionList');

window.questions.forEach(question => {
    const questionDiv = document.createElement('div');
    questionDiv.classList.add('question-item');
    questionDiv.innerHTML = `
        <h3>${question.text}</h3>
        <div class="question-meta">
            Đặt bởi: ${question.asker} | ${question.createdDate}
        </div>
        <div class="answer-section" id="answers-${question.id}" style="display: none;">
            ${question.answers.map(answer => `
                <div class="answer-item">
                    <div class="answer-content">${answer.text}</div>
                    <div class="answer-meta">
                        Trả lời bởi: ${answer.answerer} | ${answer.createdDate}
                    </div>
                    <div class="rating-info">
                        Điểm trung bình: ${answer.averageRating.toFixed(1)}/5 |
                        Số lượt đánh giá: <span onclick="toggleEvaluations(${answer.id})">${answer.numberEvaluators}</span>
                    </div>
                    <div class="evaluation-details" id="evaluations-${answer.id}" style="display: none;">
                        ${answer.evaluations.map(eval => `
                            <div class="evaluation-item">
                                ${eval.evaluator}: ${eval.rating}
                            </div>
                        `).join('')}
                    </div>
                </div>
            `).join('')}
        </div>
    `;
    questionDiv.addEventListener('click', () => toggleAnswers(question.id));
    questionList.appendChild(questionDiv);
});

function toggleAnswers(questionId) {
    const section = document.getElementById(`answers-${questionId}`);
    const isVisible = section.style.display === 'block';
    document.querySelectorAll('.answer-section').forEach(sec => sec.style.display = 'none');
    section.style.display = isVisible ? 'none' : 'block';
}

function toggleEvaluations(answerId) {
    const evalDetails = document.getElementById(`evaluations-${answerId}`);
    const isVisible = evalDetails.style.display === 'block';
    evalDetails.style.display = isVisible ? 'none' : 'block';
}
