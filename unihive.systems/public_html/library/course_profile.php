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

// Get course ID from URL
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($course_id <= 0) {
    header('Location: ../N2/library.php');
    exit();
}

// Check if user is registered for this course
if ($user_role == 'student') {
    $access_sql = "SELECT 1 FROM student_courses WHERE student_id = $user_id AND course_id = $course_id";
} else {
    $access_sql = "SELECT 1 FROM professor_courses WHERE professor_id = $user_id AND course_id = $course_id";
}

$access_result = $conn->query($access_sql);
if (!$access_result || $access_result->num_rows == 0) {
    header('Location: ../N2/library.php');
    exit();
}

// Get course details
$course_sql = "SELECT name as course_name, description FROM courses WHERE course_id = $course_id";
$course_result = $conn->query($course_sql);
if (!$course_result || $course_result->num_rows == 0) {
    header('Location: ../N2/library.php');
    exit();
}
$course = $course_result->fetch_assoc();

// Get books for this course
$books_sql = "SELECT * FROM library_books WHERE course_id = $course_id ORDER BY upload_date DESC";
$books_result = $conn->query($books_sql);
$books = [];
if ($books_result && $books_result->num_rows > 0) {
    while ($book = $books_result->fetch_assoc()) {
        $books[] = $book;
    }
}

// Get exams for this course
$exams_sql = "SELECT * FROM library_exams WHERE course_id = $course_id ORDER BY exam_year DESC, upload_date DESC";
$exams_result = $conn->query($exams_sql);
$exams = [];
if ($exams_result && $exams_result->num_rows > 0) {
    while ($exam = $exams_result->fetch_assoc()) {
        $exams[] = $exam;
    }
}

// Get image path for course
$imagePath = "../photo/Library/" . strtolower(str_replace(' ', '_', $course['course_name'])) . ".png";
if (!file_exists($imagePath)) {
    $imagePath = "../photo/Library/accounting.png"; // Default image
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UniHive - <?php echo htmlspecialchars($course['course_name']); ?></title>
  <link rel="stylesheet" href="../N2/coursesfiller/coursesFiller.css">
  <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
  <style>
    .materials-section {
        margin-top: 30px;
    }
    .materials-section h2 {
        color: #103054;
        margin-bottom: 15px;
    }
    .material-item {
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .material-item h3 {
        margin-top: 0;
        color: #333;
    }
    .material-item .details {
        color: #666;
        font-size: 14px;
        margin-bottom: 10px;
    }
    .material-item .actions {
        display: flex;
        gap: 10px;
    }
    .material-item .actions a {
        text-decoration: none;
    }
    .empty-message {
        text-align: center;
        color: #666;
        padding: 20px;
    }
    .container {
        height: auto;
        min-height: 386px;
    }
    
    .professor-actions {
      margin-top: 20px;
      display: flex;
      gap: 10px;
    }
    
    .professor-actions a {
      text-decoration: none;
    }
    
    .upload-btn {
      background-color: #38618a;
      color: white;
      padding: 10px 15px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 14px;
      transition: all 0.3s ease;
    }
    
    .upload-btn:hover {
      background-color: #4c749c;
      transform: translateY(-2px);
    }
    
    .upload-btn i {
      font-size: 18px;
    }
  </style>
</head>
<body>

  <header id="header">
    <div style="display: flex; align-items: center;">
      <a href="../home/home.php"><img src="../N2/logo_white.png" alt=""></a>
      <button id="menu-toggle">
        <i class='bx bx-menu'></i>
      </button>
    </div>

    
  <div id="mean" class="mean" >
      <ul>
      <li><a href="../home/home.php">  <i class='bx bxs-dashboard'></i>       <p>Dashbord</p> </a>       </li>
      <li><a href="../library/index.php">    <i class='bx bx-library'></i>    <p>Library</p> </a>       </li>   
      <li><a href="../A2/‏‏quiz - نسخة/all/index2.html">  <i class='bx bx-question-mark'></i>   <p>Quizzes</p> </a>       </li>   
      <li><a href="../R2/unihive course/course.html">    <i class='bx bxs-videos' ></i>        <p>Courses</p>  </a>       </li>   
      <li><a href="#">  <i class='bx bxs-file-blank'></i>    <p>Sheets</p>   </a>       </li>   
      <li><a href="../meetings/meetings.php">     <i class='bx bxs-video'></i>      <p>Meeting</p>    </a>       </li>   
      <li><a href="#">    <i class='bx bxs-message-rounded-dots' ></i>      <p>Chat</p>   </a>       </li>   
      <li><a href="#">    <i class='bx bxs-calendar'></i>  <p >Calender</p>    </a>     </li>   
      </ul>
    </div>
    <div class="icons">
      <a href=""><i class="bx bxs-bell"></i></a>
      <a href="../R2/unihive profile/profile.php"><i class="bx bxs-user-circle"></i></a>
    </div>
    
  </header>
  
  <main>
    <section>
      <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($course['course_name']); ?>">
    </section>

    <section class="container">
      <div class="description">
        <h1><?php echo htmlspecialchars($course['course_name']); ?></h1>
        <span>Description:<br></span>
        <p>
          <?php echo nl2br(htmlspecialchars($course['description'] ?: 'No description available for this course.')); ?>
        </p>
        
        <?php if ($user_role == 'professor'): ?>
        <div class="professor-actions">
          <a href="../library/upload.php?course_id=<?php echo $course_id; ?>">
            <div class="upload-btn">
              <i class='bx bx-book-add'></i> رفع كتاب
            </div>
          </a>
          <a href="../library/upload_exam.php?course_id=<?php echo $course_id; ?>">
            <div class="upload-btn">
              <i class='bx bx-file-plus'></i> رفع امتحان
            </div>
          </a>
        </div>
        <?php endif; ?>
      </div>

      <div class="materials-section">
        <h2>Course Books</h2>
        <?php if (!empty($books)): ?>
            <?php foreach ($books as $book): ?>
                <div class="material-item">
                    <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                    <div class="details">
                        <p><?php echo nl2br(htmlspecialchars(substr($book['description'], 0, 100) . (strlen($book['description']) > 100 ? '...' : ''))); ?></p>
                        <small>Uploaded: <?php echo date('Y-m-d', strtotime($book['upload_date'])); ?></small>
                    </div>
                    <div class="actions">
                        <a href="view.php?type=book&id=<?php echo $book['id']; ?>" class="btn btn-sm btn-primary">Read</a>
                        <a href="download.php?type=book&id=<?php echo $book['id']; ?>" class="btn btn-sm btn-success">Download</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-message">No books available for this course yet.</div>
        <?php endif; ?>

        <h2>Past Exams</h2>
        <?php if (!empty($exams)): ?>
            <?php foreach ($exams as $exam): ?>
                <div class="material-item">
                    <h3><?php echo htmlspecialchars($exam['title']); ?></h3>
                    <div class="details">
                        <p>Type: <?php echo htmlspecialchars($exam['exam_type']); ?> (<?php echo $exam['exam_year']; ?>)</p>
                        <small>Uploaded: <?php echo date('Y-m-d', strtotime($exam['upload_date'])); ?></small>
                    </div>
                    <div class="actions">
                        <a href="view.php?type=exam&id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-primary">Read</a>
                        <a href="download.php?type=exam&id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-success">Download</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-message">No exams available for this course yet.</div>
        <?php endif; ?>
      </div>

      <div class="btnsContainer" style="margin-top: 20px;">
        <a href="../N2/library.php">
            <button>Back to Library <i class="bx bx-arrow-back"></i></button>
        </a>
      </div>
    </section>
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
    // Toggle menu
    document.getElementById("menu-toggle").addEventListener("click", function() {
        document.getElementById("mean").classList.toggle("active");
    });
  </script>
</body>
</html>
