// filepath: quiz-system/assets/js/quiz.js
document.addEventListener('DOMContentLoaded', function() {
    // Function to fetch quizzes for the student
    function fetchQuizzes() {
        fetch('../api/get_quizzes.php')
            .then(response => response.json())
            .then(data => {
                displayQuizzes(data);
            })
            .catch(error => console.error('Error fetching quizzes:', error));
    }

    // Function to display quizzes on the page
    function displayQuizzes(quizzes) {
        const quizContainer = document.getElementById('quizContainer');
        quizContainer.innerHTML = '';

        quizzes.forEach(quiz => {
            const quizCard = document.createElement('div');
            quizCard.className = 'quiz-card';
            quizCard.innerHTML = `
                <h3>${quiz.title}</h3>
                <p>Duration: ${quiz.duration} minutes</p>
                <p>Expiration: ${new Date(quiz.expiration).toLocaleString()}</p>
                <p>Created by: ${quiz.professor}</p>
                <a href="../quiz/attempt.php?id=${quiz.id}" class="btn">Attempt Quiz</a>
            `;
            quizContainer.appendChild(quizCard);
        });
    }

    // Function to initialize the quiz system
    function initQuizSystem() {
        fetchQuizzes();
    }

    // Call the initialization function
    initQuizSystem();
});