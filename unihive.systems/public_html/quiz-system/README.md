# Quiz System for Educational Platform

## Overview
The Quiz System is an integral part of the educational platform designed to facilitate the creation, management, and completion of quizzes for students and professors. This system allows students to view and attempt quizzes related to their enrolled subjects, while professors can create quizzes, manage submissions, and view student rankings.

## Features
- **For Students:**
  - View quizzes related to enrolled subjects.
  - Access quiz details including duration, expiration time, and the professor who created it.
  - Attempt quizzes and view results.

- **For Professors:**
  - Create new quizzes with customizable settings (title, duration, expiration time, subject).
  - Manage student submissions and review answers.
  - View rankings based on completion times and scores.
  - Access a dashboard for easy navigation to all functionalities.

## Project Structure
```
quiz-system
├── admin
│   ├── create_quiz.php        # Form for creating quizzes
│   ├── dashboard.php           # Main dashboard for professors
│   ├── manage_submissions.php   # Review student submissions
│   ├── manage_quizzes.php      # List and manage quizzes
│   └── rankings.php            # Leaderboard of students
├── assets
│   ├── css
│   │   ├── admin.css           # Styles for admin dashboard
│   │   ├── quiz.css            # Styles for quiz interface
│   │   └── style.css           # General styles
│   └── js
│       ├── admin.js            # JavaScript for admin interactions
│       ├── quiz.js             # JavaScript for quiz management
│       └── timer.js            # Countdown timer for quizzes
├── includes
│   ├── config.php              # Configuration settings
│   ├── db_connect.php          # Database connection
│   ├── functions.php           # Utility functions
│   └── header.php              # Header section for HTML
├── quiz
│   ├── attempt.php             # Interface for attempting quizzes
│   ├── index.php               # List of available quizzes
│   ├── results.php             # Display quiz results
│   └── view.php                # View quiz details and submissions
├── api
│   ├── get_quizzes.php         # API to retrieve quizzes
│   ├── submit_quiz.php         # Handle quiz submissions
│   └── user_progress.php       # Track student progress
├── .htaccess                   # URL rewriting and security settings
└── README.md                   # Project documentation
```

## Setup Instructions
1. **Clone the Repository:**
   ```
   git clone <repository-url>
   ```

2. **Install Dependencies:**
   Ensure you have a web server (like Apache) and PHP installed. Set up a database and import the necessary SQL files if provided.

3. **Configure Database:**
   Update the `includes/config.php` file with your database connection details.

4. **Access the Application:**
   Navigate to the `quiz-system` directory in your web browser to access the application.

## Usage Guidelines
- Students must log in to view and attempt quizzes.
- Professors can log in to access the dashboard for creating and managing quizzes.
- Ensure that quizzes have appropriate settings for duration and expiration to enhance the learning experience.

## Contributing
Contributions are welcome! Please submit a pull request or open an issue for any enhancements or bug fixes.

## License
This project is licensed under the MIT License. See the LICENSE file for details.