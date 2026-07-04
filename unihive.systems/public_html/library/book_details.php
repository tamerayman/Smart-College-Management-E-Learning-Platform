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

// Get book ID from URL
$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($book_id <= 0) {
    header('Location: ../N2/library.php');
    exit();
}

// Get book details
$sql = "SELECT lb.*, c.name as course_name, c.level 
        FROM library_books lb 
        INNER JOIN courses c ON lb.course_id = c.course_id 
        WHERE lb.id = $book_id";
$result = $conn->query($sql);

if (!$result || $result->num_rows == 0) {
    header('Location: ../N2/library.php');
    exit();
}

$book = $result->fetch_assoc();

// Check if student has access to this book's course
if ($user_role == 'student') {
    $access_sql = "SELECT 1 FROM student_courses 
                  WHERE student_id = $user_id 
                  AND course_id = " . $book['course_id'];
    $access_result = $conn->query($access_sql);
    
    if ($access_result->num_rows == 0) {
        header('Location: ../N2/library.php');
        exit();
    }
}

// Get image path for course
$imagePath = "../photo/Library/" . strtolower(str_replace(' ', '_', $book['course_name'])) . ".png";
if (!file_exists($imagePath)) {
    $imagePath = "../photo/Library/accounting.png"; // Default image
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UniHive - <?php echo htmlspecialchars($book['title']); ?></title>
  <link rel="stylesheet" href="../N2/coursesfiller/coursesFiller.css">
  <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
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
      <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($book['course_name']); ?>">
    </section>

    <section class="container">
      <div class="description">
        <h1><?php echo htmlspecialchars($book['title']); ?></h1>
        <span>Description:<br></span>
        <p>
          <?php echo nl2br(htmlspecialchars($book['description'] ?: 'No description available for this book.')); ?>
        </p>
      </div>

      <div class="btnsContainer">
        <a href="view.php?type=book&id=<?php echo $book['id']; ?>">
          <button>Read <i class="bx bxs-file-pdf"></i></button>
        </a>
        <a href="download.php?type=book&id=<?php echo $book['id']; ?>">
          <button>Download <i class="bx bxs-cloud-download"></i></button>
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

  <script src="../N2/coursesfiller/list.js"></script>
</body>
</html>
