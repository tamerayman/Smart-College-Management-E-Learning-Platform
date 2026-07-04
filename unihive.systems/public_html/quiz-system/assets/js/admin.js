// filepath: quiz-system/assets/js/admin.js

document.addEventListener('DOMContentLoaded', function() {
    // Function to handle quiz creation form submission
    const quizForm = document.getElementById('quizForm');
    if (quizForm) {
        quizForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(quizForm);
            fetch('api/create_quiz.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Quiz created successfully!');
                    window.location.reload();
                } else {
                    alert('Error creating quiz: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    }

    // Function to handle deletion of quizzes
    const deleteButtons = document.querySelectorAll('.delete-quiz');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const quizId = this.dataset.quizId;
            if (confirm('Are you sure you want to delete this quiz?')) {
                fetch('api/delete_quiz.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: quizId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Quiz deleted successfully!');
                        window.location.reload();
                    } else {
                        alert('Error deleting quiz: ' + data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });
    });

    // Function to dynamically update quiz list or submissions
    function updateContent(url, targetElement) {
        fetch(url)
        .then(response => response.text())
        .then(html => {
            document.querySelector(targetElement).innerHTML = html;
        })
        .catch(error => console.error('Error:', error));
    }
});