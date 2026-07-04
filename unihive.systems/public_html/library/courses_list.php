<?php
session_start();
require_once '../config.php';
require_once '../auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

$conn = connectDB();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniHive - Course Library</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/library_styles.css">
    <style>
        #sec1 {
            text-align: center;
            padding: 20px 0;
        }
        
        #searchContainer {
            position: relative;
            max-width: 500px;
            margin: 0 auto;
        }
        
        #search {
            width: 100%;
            padding: 12px 20px;
            padding-left: 45px;
            border: 2px solid #ddd;
            border-radius: 25px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        #search:focus {
            border-color: #103054;
            box-shadow: 0 0 8px rgba(16, 48, 84, 0.2);
            outline: none;
        }
        
        #searchContainer i {
            position: absolute;
            left: 15px;
            top: 12px;
            font-size: 20px;
            color: #555;
        }
        
        .courses {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .bookContainer {
            width: 200px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .bookContainer:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        
        .bookContainer img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        
        .courseName {
            background-color: #103054;
            color: white;
            padding: 10px;
            text-align: center;
        }
        
        .courseName p {
            margin: 0;
            font-size: 16px;
            font-weight: 500;
        }
        
        .bookContainer a {
            text-decoration: none;
            color: inherit;
        }
        
        .animate-item {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <header id="header">
        <div style="display: flex; align-items: center;">
            <a href="../home/home.php"><img src="assets/img/logo_white.png" alt=""></a>
            <button id="menu-toggle">
                <i class='bx bx-menu'></i>
            </button>
        </div>

        <div id="mean" class="mean">
            <ul>
                <li><a href="../home/home.php"><i class='bx bxs-dashboard'></i><p>Dashboard</p></a></li>
                <li><a href="index.php" class="active"><i class='bx bx-library'></i><p>Library</p></a></li>   
                <li><a href="../A2/‏‏quiz - نسخة/all/index2.html"><i class='bx bx-question-mark'></i><p>Quizzes</p></a></li>   
                <li><a href="../R2/unihive course/course.html"><i class='bx bxs-videos'></i><p>Courses</p></a></li>   
                <li><a href="#"><i class='bx bxs-file-blank'></i><p>Sheets</p></a></li>   
                <li><a href="../meetings/meetings.php"><i class='bx bxs-video'></i><p>Meeting</p></a></li>   
                <li><a href="#"><i class='bx bxs-message-rounded-dots'></i><p>Chat</p></a></li>   
                <li><a href="#"><i class='bx bxs-calendar'></i><p>Calendar</p></a></li>   
            </ul>
        </div>

        <div class="icons">
            <a href="#"><i class="bx bxs-bell"></i></a>
            <a href="../R2/unihive profile/profile.php"><i class="bx bxs-user-circle"></i></a>
        </div>
    </header>

    <main>
        <section id="sec1">
            <div id="searchContainer">
                <i class="bx bx-search"></i>
                <input type="text" id="search" placeholder="Search courses..." onkeyup="searchData(this.value)">
            </div>    
        </section>
        
        <section class="sec2">
            <div class="courses">
                <h1 id="noResults" style="display: none;">Not Found!</h1>
                
                <div class="bookContainer animate-item">
                    <a href="courses/accounting.php<?php echo ($user_role == 'professor' ? '?role=professor' : ''); ?>">
                    <img src="assets/img/courses/accounting-card.png" alt="">
                    <div class="courseName">
                        <p>Accounting</p>
                    </div>
                    </a>
                </div>

                <div class="bookContainer animate-item">
                    <a href="courses/database.php<?php echo ($user_role == 'professor' ? '?role=professor' : ''); ?>">
                    <img src="assets/img/courses/database-card.png" alt="">
                    <div class="courseName">
                        <p>Database</p>
                    </div>
                    </a>
                </div>

                <div class="bookContainer animate-item">
                    <a href="courses/data_mining.php<?php echo ($user_role == 'professor' ? '?role=professor' : ''); ?>">
                    <img src="assets/img/courses/data-mining-card.png" alt="">
                    <div class="courseName">
                        <p>Data Mining</p>
                    </div>
                    </a>
                </div>

                <div class="bookContainer animate-item">
                    <a href="courses/data_structure.php<?php echo ($user_role == 'professor' ? '?role=professor' : ''); ?>">
                    <img src="assets/img/courses/data-structure-card.png" alt="">
                    <div class="courseName">
                        <p>Data Structure</p>
                    </div>
                    </a>
                </div>

                <div class="bookContainer animate-item">
                    <a href="courses/economy.php<?php echo ($user_role == 'professor' ? '?role=professor' : ''); ?>">
                    <img src="assets/img/courses/economy-card.png" alt="">
                    <div class="courseName">
                        <p>Economy</p>
                    </div>
                    </a>
                </div>
            </div>
        </section>
        
        <div class="container" style="text-align: center; margin-top: 20px;">
            <a href="index.php" class="btn btn-primary">Back to Library</a>
        </div>
    </main>

    <footer>
        <p>www.unihive.com</p>
        <div class="footerIcons">
            <a href="#"><i class="bx bxs-phone"></i></a>
            <a href="#"><i class="bx bxl-facebook"></i></a>
            <a href="#"><i class="bx bx-globe"></i></a>
        </div>
    </footer>

    <script>
        document.getElementById("menu-toggle").addEventListener("click", function() {
            document.getElementById("mean").classList.toggle("active");
        });
        
        function searchData(keyword) {
            keyword = keyword.toLowerCase();
            const courses = document.querySelectorAll('.bookContainer');
            const noResults = document.getElementById('noResults');
            let resultsFound = false;
            
            courses.forEach(course => {
                const courseName = course.querySelector('.courseName p').textContent.toLowerCase();
                if (courseName.includes(keyword)) {
                    course.style.display = 'block';
                    resultsFound = true;
                } else {
                    course.style.display = 'none';
                }
            });
            
            noResults.style.display = resultsFound ? 'none' : 'block';
        }
    </script>
</body>
</html>
