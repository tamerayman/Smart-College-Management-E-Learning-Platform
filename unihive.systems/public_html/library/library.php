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

// Fetch courses the user is registered for
$registered_courses = [];

if ($user_role == 'student') {
    // Get courses student is registered in
    $sql = "SELECT c.course_id, c.name as course_name 
            FROM courses c 
            INNER JOIN student_courses sc ON c.course_id = sc.course_id 
            WHERE sc.student_id = $user_id
            ORDER BY c.name";
} else {
    // Get courses professor teaches
    $sql = "SELECT c.course_id, c.name as course_name 
            FROM courses c 
            INNER JOIN professor_courses pc ON c.course_id = pc.course_id 
            WHERE pc.professor_id = $user_id
            ORDER BY c.name";
}

$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($course = $result->fetch_assoc()) {
        $registered_courses[] = $course;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UniHive - المكتبة</title>
  <link rel="stylesheet" href="assets/css/library.css">
  <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
  <style>
    .professor-controls {
      display: flex;
      justify-content: flex-end;
      margin: 10px 20px 20px 0;
    }
    .upload-btn {
      background-color: #103054;
      color: white;
      border: none;
      border-radius: 10px;
      padding: 10px 20px;
      font-size: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      transition: 0.3s;
      box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
    }
    .upload-btn:hover {
      background-color: #1c4f86;
      transform: translateY(-2px);
      box-shadow: 0px 6px 12px rgba(0, 0, 0, 0.15);
    }
    .upload-btn i {
      font-size: 20px;
    }
    .upload-dropdown {
      position: relative;
      display: inline-block;
    }
    .upload-dropdown-content {
      display: none;
      position: absolute;
      right: 0;
      background-color: #f9f9f9;
      min-width: 160px;
      box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
      z-index: 1;
      border-radius: 8px;
      overflow: hidden;
    }
    .upload-dropdown-content a {
      color: #103054;
      padding: 12px 16px;
      text-decoration: none;
      display: block;
      transition: 0.2s;
    }
    .upload-dropdown-content a:hover {
      background-color: #f1f1f1;
      color: #1c4f86;
    }
    .upload-dropdown:hover .upload-dropdown-content {
      display: block;
    }
    
    /* Modal Styles - Old Style Restoration */
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      display: none;
      justify-content: center;
      align-items: center;
    }
    
    .modal-container {
      background-color: #fff;
      width: 90%;
      max-width: 800px;
      max-height: 90vh;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
      padding: 30px;
      overflow-y: auto;
      position: relative;
    }
    
    .modal-close {
      position: absolute;
      top: 15px;
      right: 15px;
      font-size: 24px;
      color: #777;
      cursor: pointer;
      transition: color 0.3s;
    }
    
    .modal-close:hover {
      color: #000;
    }
    
    .modal-header {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .modal-header h2 {
      color: #103054;
      font-size: 28px;
      margin-bottom: 10px;
    }
    
    .modal-header p {
      color: #6c757d;
      font-size: 16px;
    }
    
    /* Form styling like the original upload.php */
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: #333;
    }
    
    .form-control {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 16px;
      transition: border-color 0.3s;
    }
    
    .form-control:focus {
      border-color: #103054;
      outline: none;
      box-shadow: 0 0 0 3px rgba(16, 48, 84, 0.1);
    }
    
    .form-row {
      display: flex;
      gap: 20px;
      margin-bottom: 20px;
    }
    
    .form-row .form-group {
      flex: 1;
      margin-bottom: 0;
    }
    
    .buttons {
      display: flex;
      gap: 15px;
      margin-top: 30px;
      justify-content: flex-end;
    }
    
    .btn {
      padding: 12px 25px;
      border-radius: 6px;
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      border: none;
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
    }
    
    .btn-primary {
      background-color: #103054;
      color: white;
    }
    
    .btn-primary:hover {
      background-color: #0c2440;
      transform: translateY(-2px);
    }
    
    .btn-secondary {
      background-color: #6c757d;
      color: white;
    }
    
    .btn-secondary:hover {
      background-color: #5a6268;
      transform: translateY(-2px);
    }
    
    .btn-info {
      background-color: #28a745; /* تغيير من #17a2b8 (أزرق) إلى #28a745 (أخضر) */
      color: white;
    }
    
    .btn-info:hover {
      background-color: #218838; /* لون الزر عند تمرير المؤشر فوقه */
      transform: translateY(-2px);
    }
    
    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 6px;
      display: flex;
      align-items: center;
    }
    
    .alert i {
      font-size: 24px;
      margin-right: 15px;
    }
    
    .alert-success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    
    .alert-danger {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
    
    .file-preview {
      border: 1px dashed #ddd;
      border-radius: 6px;
      padding: 15px;
      margin-top: 10px;
      display: none;
    }
    
    .file-preview.active {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .file-preview i {
      font-size: 30px;
      color: #103054;
    }
    
    .file-info {
      flex: 1;
    }
    
    .file-info p {
      margin: 0;
    }
    
    .help-text {
      font-size: 14px;
      color: #6c757d;
      margin-top: 5px;
    }
    
    .loading-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(255, 255, 255, 0.7);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 10;
      display: none;
    }
    
    .spinner {
      width: 50px;
      height: 50px;
      border: 5px solid #f3f3f3;
      border-top: 5px solid #103054;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    @media (max-width: 768px) {
      .form-row {
        flex-direction: column;
        gap: 15px;
      }
      
      .buttons {
        flex-direction: column;
      }
      
      .btn {
        width: 100%;
        justify-content: center;
      }
    }
    
    /* Additional styling for My Uploads Modal */
    .upload-section {
      border-bottom: 1px solid #e9ecef;
      margin-bottom: 25px;
      padding-bottom: 20px;
    }
    
    .upload-section:last-child {
      border-bottom: none;
    }
    
    .section-title {
      color: #103054;
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 15px;
      position: relative;
      padding-right: 15px;
    }
    
    .section-title:before {
      content: '';
      position: absolute;
      left: -10px;
      top: 0;
      height: 100%;
      width: 4px;
      background-color: #103054;
      border-radius: 2px;
    }
    
    .upload-item {
      background-color: #fff;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 15px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
      transition: all 0.3s ease;
      border-left: 3px solid #103054;
    }
    
    .upload-item:hover {
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      transform: translateY(-2px);
    }
    
    .upload-item h3 {
      color: #103054;
      font-size: 18px;
      margin-bottom: 10px;
      font-weight: 600;
    }
    
    .upload-item p {
      color: #555;
      margin-bottom: 5px;
      font-size: 14px;
    }
    
    .upload-item p strong {
      color: #333;
      font-weight: 600;
    }
    
    .text-center {
      text-align: center;
      color: #6c757d;
      padding: 20px;
      font-style: italic;
    }
    
    .loading-indicator {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 30px;
    }
    
    .loading-indicator p {
      margin-top: 15px;
      color: #6c757d;
    }
    
    /* Improved status message styling */
    #uploadStatusMessage {
      display: flex;
      align-items: center;
      padding: 15px;
      border-radius: 6px;
      margin-bottom: 20px;
      animation: fadeIn 0.3s;
    }
    
    #uploadStatusMessage.alert-success {
      background-color: #d1e7dd;
      color: #0f5132;
      border-left: 5px solid #0f5132;
    }
    
    #uploadStatusMessage.alert-danger {
      background-color: #f8d7da;
      color: #842029;
      border-left: 5px solid #842029;
    }
    
    #uploadStatusMessage i {
      font-size: 24px;
      margin-right: 10px;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>

  <header id="header">
    <div style="display: flex; align-items: center;">
      <a href="../home/home.php"><img src="assets/images/logo_white.png" alt="Logo"></a>
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
    <section id="sec1">
      <div id="searchContainer">
        <i class="bx bx-search"></i>
        <input type="text" id="search" placeholder="Search ..." onkeyup="searchData(this.value)">
      </div>
      
      <?php if ($user_role == 'professor'): ?>
      <div class="professor-controls">
        <div class="upload-dropdown">
          <button class="upload-btn">
            <i class='bx bx-upload'></i> Upload Files
          </button>
          <div class="upload-dropdown-content">
            <a href="#" id="openBookUpload"><i class='bx bx-book'></i> Upload Book</a>
            <a href="#" id="openExamUpload"><i class='bx bx-file'></i> Upload Exam</a>
            <a href="#" id="openMyUploads"><i class='bx bx-list-ul'></i> My Uploads</a>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </section>
    
    <section class="sec2">
      <div class="courses">
        <h1 id="noResults" style="display: none;">Not Found!</h1>
      
        <?php if (!empty($registered_courses)): ?>
            <?php foreach ($registered_courses as $course): ?>
                <?php 
                    // Get image path based on course name
                    $course_name_cleaned = strtolower(str_replace(' ', '_', $course['course_name']));
                    $imagePath = "../photo/Library/{$course_name_cleaned} card.png";
                    
                    // Default image if specific one doesn't exist
                    if (!file_exists($imagePath)) {
                        $imagePath = "../photo/Library/accounting card.png";
                    }
                ?>
                <div class="bookContainer animate-item">
                    <a href="../library/course_profile.php?id=<?php echo $course['course_id']; ?>">
                        <img src="<?php echo $imagePath; ?>" alt="">
                        <div class="courseName">
                            <p><?php echo htmlspecialchars($course['course_name']); ?></p>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-courses">
                <p>You are not registered for any courses yet.</p>
            </div>
        <?php endif; ?>
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
  
  <?php if ($user_role == 'professor'): ?>
  <!-- Book Upload Modal -->
  <div class="modal-overlay" id="bookUploadModal">
    <div class="modal-container">
      <span class="modal-close" id="closeBookModal">&times;</span>
      <div class="modal-header">
        <h2>Upload New Book</h2>
        <p>Share educational materials with your students</p>
      </div>
      
      <div id="bookAlertSuccess" class="alert alert-success" style="display: none;">
        <i class='bx bx-check-circle'></i>
        <span>Book uploaded successfully</span>
      </div>
      
      <div id="bookAlertError" class="alert alert-danger" style="display: none;">
        <i class='bx bx-error-circle'></i>
        <span id="bookErrorMessage">Error uploading book</span>
      </div>
      
      <div class="loading-overlay" id="bookLoadingOverlay">
        <div class="spinner"></div>
      </div>
      
      <form id="bookUploadForm" method="post" enctype="multipart/form-data">
        <input type="hidden" name="upload_type" value="book">
        
        <div class="form-group">
          <label for="title">Book Title</label>
          <input type="text" class="form-control" id="title" name="title" required>
        </div>
        
        <div class="form-group">
          <label for="course_id">Course</label>
          <select class="form-control" id="course_id" name="course_id" required>
            <option value="">Select Course</option>
            <?php 
            $courses_query = $conn->query("SELECT c.course_id, c.name as course_name 
                FROM courses c 
                JOIN professor_courses pc ON c.course_id = pc.course_id 
                WHERE pc.professor_id = $user_id");
            while ($course = $courses_query->fetch_assoc()): 
            ?>
              <option value="<?php echo $course['course_id']; ?>">
                <?php echo htmlspecialchars($course['course_name']); ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        
        <div class="form-group">
          <label for="description">Book Description (Optional)</label>
          <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter a brief description of the book content"></textarea>
          <small class="help-text">Provide details about the book to help students understand its content.</small>
        </div>
        
        <div class="form-group">
          <label for="book_file">Book File</label>
          <input type="file" class="form-control" id="book_file" name="book_file" accept=".pdf,.doc,.docx" required>
          <small class="help-text">Allowed formats: PDF, DOC, DOCX. Maximum file size: 10MB</small>
          
          <div class="file-preview" id="bookFilePreview">
            <i class='bx bxs-file-pdf'></i>
            <div class="file-info">
              <p id="bookFileName">filename.pdf</p>
              <p id="bookFileSize">2.5MB</p>
            </div>
          </div>
        </div>
        
        <div class="buttons">
          <button type="button" class="btn btn-secondary" id="cancelBookUpload">
            <i class='bx bx-arrow-back'></i> Cancel
          </button>
          <button type="submit" class="btn btn-primary">
            <i class='bx bx-upload'></i> Upload Book
          </button>
        </div>
      </form>
    </div>
  </div>
  
  <!-- Exam Upload Modal -->
  <div class="modal-overlay" id="examUploadModal">
    <div class="modal-container">
      <span class="modal-close" id="closeExamModal">&times;</span>
      <div class="modal-header">
        <h2>Upload Past Exam</h2>
        <p>Share exam materials to help students prepare</p>
      </div>
      
      <div id="examAlertSuccess" class="alert alert-success" style="display: none;">
        <i class='bx bx-check-circle'></i>
        <span>Exam uploaded successfully</span>
      </div>
      
      <div id="examAlertError" class="alert alert-danger" style="display: none;">
        <i class='bx bx-error-circle'></i>
        <span id="examErrorMessage">Error uploading exam</span>
      </div>
      
      <div class="loading-overlay" id="examLoadingOverlay">
        <div class="spinner"></div>
      </div>
      
      <form id="examUploadForm" method="post" enctype="multipart/form-data">
        <input type="hidden" name="upload_type" value="exam">
        
        <div class="form-group">
          <label for="exam_title">Exam Title</label>
          <input type="text" class="form-control" id="exam_title" name="title" placeholder="e.g. Database Systems Final Exam" required>
        </div>
        
        <div class="form-group">
          <label for="exam_course_id">Course</label>
          <select class="form-control" id="exam_course_id" name="course_id" required>
            <option value="">Select Course</option>
            <?php 
            $courses_query->data_seek(0); // Reset results pointer
            while ($course = $courses_query->fetch_assoc()): 
            ?>
              <option value="<?php echo $course['course_id']; ?>">
                <?php echo htmlspecialchars($course['course_name']); ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="exam_type">Exam Type</label>
            <select class="form-control" id="exam_type" name="exam_type" required>
              <option value="Final">Final Exam</option>
              <option value="Midterm">Midterm Exam</option>
              <option value="Quiz">Quiz</option>
              <option value="Practice">Practice Exam</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="exam_year">Exam Year</label>
            <select class="form-control" id="exam_year" name="exam_year" required>
              <?php for ($year = date('Y'); $year >= date('Y') - 10; $year--): ?>
                <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>
        
        <div class="form-group">
          <label for="exam_file">Exam File</label>
          <input type="file" class="form-control" id="exam_file" name="exam_file" accept=".pdf,.doc,.docx" required>
          <small class="help-text">Allowed formats: PDF, DOC, DOCX. Maximum file size: 10MB</small>
          
          <div class="file-preview" id="examFilePreview">
            <i class='bx bxs-file-pdf'></i>
            <div class="file-info">
              <p id="examFileName">filename.pdf</p>
              <p id="examFileSize">2.5MB</p>
            </div>
          </div>
        </div>
        
        <div class="buttons">
          <button type="button" class="btn btn-secondary" id="cancelExamUpload">
            <i class='bx bx-arrow-back'></i> Cancel
          </button>
          <button type="submit" class="btn btn-primary">
            <i class='bx bx-upload'></i> Upload Exam
          </button>
        </div>
      </form>
    </div>
  </div>
  
  <!-- My Uploads Modal -->
  <div class="modal-overlay" id="myUploadsModal">
    <div class="modal-container" style="max-width: 900px;">
      <span class="modal-close" id="closeMyUploadsModal">&times;</span>
      <div class="modal-header">
        <h2>My Uploads</h2>
        <p>Manage your uploaded books and exams</p>
      </div>
      
      <div id="uploadsContent">
        <div id="uploadStatusMessage" class="alert" style="display: none;"></div>
        
        <!-- Books Section -->
        <div class="upload-section">
          <h3 class="section-title">Books</h3>
          <div id="booksContainer" class="list-container">
            <div class="loading-indicator">
              <div class="spinner"></div>
              <p>Loading data...</p>
            </div>
          </div>
        </div>
        
        <!-- Exams Section -->
        <div class="upload-section">
          <h3 class="section-title">Exams</h3>
          <div id="examsContainer" class="list-container">
            <div class="loading-indicator">
              <div class="spinner"></div>
              <p>Loading data...</p>
            </div>
          </div>
        </div>
      </div>
      
      <div class="buttons">
        <button type="button" class="btn btn-secondary" id="closeUploadsBtn">
          <i class='bx bx-arrow-back'></i> Close
        </button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <script src="assets/js/library.js"></script>
  
  <?php if ($user_role == 'professor'): ?>
  <script>
    // Modal handling
    document.getElementById('openBookUpload').addEventListener('click', function(e) {
      e.preventDefault();
      document.getElementById('bookUploadModal').style.display = 'flex';
      // Reset alerts
      document.getElementById('bookAlertSuccess').style.display = 'none';
      document.getElementById('bookAlertError').style.display = 'none';
    });
    
    document.getElementById('openExamUpload').addEventListener('click', function(e) {
      e.preventDefault();
      document.getElementById('examUploadModal').style.display = 'flex';
      // Reset alerts
      document.getElementById('examAlertSuccess').style.display = 'none';
      document.getElementById('examAlertError').style.display = 'none';
    });
    
    document.getElementById('openMyUploads').addEventListener('click', function(e) {
      e.preventDefault();
      document.getElementById('myUploadsModal').style.display = 'flex';
      loadMyUploads();
    });
    
    document.getElementById('closeBookModal').addEventListener('click', function() {
      document.getElementById('bookUploadModal').style.display = 'none';
    });
    
    document.getElementById('closeExamModal').addEventListener('click', function() {
      document.getElementById('examUploadModal').style.display = 'none';
    });
    
    document.getElementById('closeMyUploadsModal').addEventListener('click', function() {
      document.getElementById('myUploadsModal').style.display = 'none';
    });
    
    document.getElementById('cancelBookUpload').addEventListener('click', function() {
      document.getElementById('bookUploadModal').style.display = 'none';
    });
    
    document.getElementById('cancelExamUpload').addEventListener('click', function() {
      document.getElementById('examUploadModal').style.display = 'none';
    });
    
    document.getElementById('closeUploadsBtn').addEventListener('click', function() {
      document.getElementById('myUploadsModal').style.display = 'none';
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
      if (e.target.classList.contains('modal-overlay')) {
        e.target.style.display = 'none';
      }
    });
    
    // Book file preview
    document.getElementById('book_file').addEventListener('change', function(e) {
      const file = e.target.files[0];
      const filePreview = document.getElementById('bookFilePreview');
      const fileName = document.getElementById('bookFileName');
      const fileSize = document.getElementById('bookFileSize');
      
      if (file) {
        fileName.textContent = file.name;
        fileSize.textContent = (file.size / (1024 * 1024)).toFixed(2) + 'MB';
        filePreview.classList.add('active');
        
        // Change icon based on file type
        const fileIcon = filePreview.querySelector('i');
        if (file.type === 'application/pdf') {
          fileIcon.className = 'bx bxs-file-pdf';
        } else if (file.type.includes('word')) {
          fileIcon.className = 'bx bxs-file-doc';
        } else {
          fileIcon.className = 'bx bxs-file';
        }
      } else {
        filePreview.classList.remove('active');
      }
    });
    
    // Exam file preview
    document.getElementById('exam_file').addEventListener('change', function(e) {
      const file = e.target.files[0];
      const filePreview = document.getElementById('examFilePreview');
      const fileName = document.getElementById('examFileName');
      const fileSize = document.getElementById('examFileSize');
      
      if (file) {
        fileName.textContent = file.name;
        fileSize.textContent = (file.size / (1024 * 1024)).toFixed(2) + 'MB';
        filePreview.classList.add('active');
        
        // Change icon based on file type
        const fileIcon = filePreview.querySelector('i');
        if (file.type === 'application/pdf') {
          fileIcon.className = 'bx bxs-file-pdf';
        } else if (file.type.includes('word')) {
          fileIcon.className = 'bx bxs-file-doc';
        } else {
          fileIcon.className = 'bx bxs-file';
        }
      } else {
        filePreview.classList.remove('active');
      }
    });
    
    // Handle form submissions
    document.getElementById('bookUploadForm').addEventListener('submit', function(e) {
      e.preventDefault();
      document.getElementById('bookLoadingOverlay').style.display = 'flex';
      
      const formData = new FormData(this);
      
      fetch('process_upload.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        document.getElementById('bookLoadingOverlay').style.display = 'none';
        
        if (data.success) {
          // Show success message
          document.getElementById('bookAlertSuccess').style.display = 'flex';
          // Reset form
          document.getElementById('bookUploadForm').reset();
          document.getElementById('bookFilePreview').classList.remove('active');
          
          // Redirect after 2 seconds
          setTimeout(function() {
            window.location.reload();
          }, 2000);
        } else {
          // Show error message
          document.getElementById('bookErrorMessage').textContent = data.message || 'Error uploading file';
          document.getElementById('bookAlertError').style.display = 'flex';
        }
      })
      .catch(error => {
        document.getElementById('bookLoadingOverlay').style.display = 'none';
        document.getElementById('bookErrorMessage').textContent = 'Error connecting to server';
        document.getElementById('bookAlertError').style.display = 'flex';
        console.error('Error:', error);
      });
    });
    
    document.getElementById('examUploadForm').addEventListener('submit', function(e) {
      e.preventDefault();
      document.getElementById('examLoadingOverlay').style.display = 'flex';
      
      const formData = new FormData(this);
      
      fetch('process_upload.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        document.getElementById('examLoadingOverlay').style.display = 'none';
        
        if (data.success) {
          // Show success message
          document.getElementById('examAlertSuccess').style.display = 'flex';
          // Reset form
          document.getElementById('examUploadForm').reset();
          document.getElementById('examFilePreview').classList.remove('active');
          
          // Redirect after 2 seconds
          setTimeout(function() {
            window.location.reload();
          }, 2000);
        } else {
          // Show error message
          document.getElementById('examErrorMessage').textContent = data.message || 'Error uploading file';
          document.getElementById('examAlertError').style.display = 'flex';
        }
      })
      .catch(error => {
        document.getElementById('examLoadingOverlay').style.display = 'none';
        document.getElementById('examErrorMessage').textContent = 'Error connecting to server';
        document.getElementById('examAlertError').style.display = 'flex';
        console.error('Error:', error);
      });
    });
    
    // My Uploads Modal
    function loadMyUploads() {
      // Load books
      fetch('get_my_uploads.php?type=books')
        .then(response => response.json())
        .then(data => {
          const booksContainer = document.getElementById('booksContainer');
          
          if (data.success) {
            if (data.items && data.items.length > 0) {
              let booksHTML = '';
              
              data.items.forEach(book => {
                booksHTML += `
                  <div class="upload-item">
                    <h3>${book.title}</h3>
                    <p><strong>Course:</strong> ${book.course_name}</p>
                    <p><strong>Upload Date:</strong> ${book.upload_date}</p>
                    <div class="actions">
                      <a href="view_page.php?type=book&id=${book.id}" target="_blank" class="btn btn-info"><i class='bx bx-book-open'></i> Read</a>
                      <a href="download.php?type=book&id=${book.id}" class="btn btn-primary"><i class='bx bx-download'></i> Download</a>
                      <button class="btn btn-danger" onclick="deleteUpload('book', ${book.id})"><i class='bx bx-trash'></i> Delete</button>
                    </div>
                  </div>
                `;
              });
              
              booksContainer.innerHTML = booksHTML;
            } else {
              booksContainer.innerHTML = '<p class="text-center">No books uploaded yet</p>';
            }
          } else {
            booksContainer.innerHTML = '<p class="text-center">Error loading data</p>';
          }
        })
        .catch(error => {
          console.error('Error loading books:', error);
          document.getElementById('booksContainer').innerHTML = '<p class="text-center">Error connecting to server</p>';
        });
      
      // Load exams
      fetch('get_my_uploads.php?type=exams')
        .then(response => response.json())
        .then(data => {
          const examsContainer = document.getElementById('examsContainer');
          
          if (data.success) {
            if (data.items && data.items.length > 0) {
              let examsHTML = '';
              
              data.items.forEach(exam => {
                examsHTML += `
                  <div class="upload-item">
                    <h3>${exam.title}</h3>
                    <p>Cour<e: ${exam.course_name}</p>
                    <p>Exam Type: ${exam.exam_type} - ${exam.exam_year}</p>
                    <p>Upload Date: ${exam.upload_date}</p>
                    <div class="actions">
                      <a href="view_page.php?type=exam&id=${exam.id}" target="_blank" class="btn btn-info"><i class='bx bx-file'></i> Read</a>
                      <a href="download.php?type=exam&id=${exam.id}" class="btn btn-primary"><i class='bx bx-download'></i> Download</a>
                      <button class="btn btn-danger" onclick="deleteUpload('exam', ${exam.id})"><i class='bx bx-trash'></i> Delete</button>
                    </div>
                  </div>
                `;
              });
              
              examsContainer.innerHTML = examsHTML;
            } else {
              examsContainer.innerHTML = '<p class="text-center">No exams uploaded yet</p>';
            }
          } else {
            examsContainer.innerHTML = '<p class="text-center">Error loading data</p>';
          }
        })
        .catch(error => {
          console.error('Error loading exams:', error);
          document.getElementById('examsContainer').innerHTML = '<p class="text-center">Error connecting to server</p>';
        });
    }
    
    function deleteUpload(type, id) {
      if (!confirm('Are you sure you want to delete this file?')) {
        return;
      }
      
      fetch(`delete_upload.php?type=${type}&id=${id}`)
        .then(response => response.json())
        .then(data => {
          const statusMessage = document.getElementById('uploadStatusMessage');
          
          if (data.success) {
            statusMessage.className = 'alert alert-success';
            statusMessage.innerHTML = '<i class="bx bx-check-circle"></i> File deleted successfully';
            // Reload the uploads after deletion
            loadMyUploads();
          } else {
            statusMessage.className = 'alert alert-danger';
            statusMessage.innerHTML = `<i class="bx bx-error-circle"></i> ${data.message || 'Error deleting file'}`;
          }
          
          statusMessage.style.display = 'flex';
          
          // Hide the message after 3 seconds
          setTimeout(() => {
            statusMessage.style.display = 'none';
          }, 3000);
        })
        .catch(error => {
          console.error('Error deleting file:', error);
          const statusMessage = document.getElementById('uploadStatusMessage');
          statusMessage.className = 'alert alert-danger';
          statusMessage.innerHTML = '<i class="bx bx-error-circle"></i> Server connection error';
          statusMessage.style.display = 'flex';
          
          setTimeout(() => {
            statusMessage.style.display = 'none';
          }, 3000);
        });
    }
  </script>
  <?php endif; ?>
</body>
</html>
