// filepath: quiz-system/assets/js/timer.js
document.addEventListener("DOMContentLoaded", function() {
    const timerDisplay = document.getElementById("timer");
    const quizDuration = parseInt(timerDisplay.dataset.duration, 10); // Duration in seconds
    const expirationTime = new Date(timerDisplay.dataset.expiration).getTime(); // Expiration time in milliseconds

    let timeRemaining = quizDuration;

    const countdown = setInterval(function() {
        const now = new Date().getTime();
        const distance = expirationTime - now;

        if (distance < 0) {
            clearInterval(countdown);
            timerDisplay.innerHTML = "Time's up!";
            // Optionally, submit the quiz automatically
            document.getElementById("quizForm").submit();
            return;
        }

        timeRemaining = Math.floor(distance / 1000);
        const minutes = Math.floor((timeRemaining % 3600) / 60);
        const seconds = timeRemaining % 60;

        timerDisplay.innerHTML = `${minutes}m ${seconds}s`;
    }, 1000);
});