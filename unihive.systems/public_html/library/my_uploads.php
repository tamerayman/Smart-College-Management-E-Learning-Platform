<?php
session_start();
require_once '../config.php';
require_once '../auth.php';

// Check if user is logged in and is a professor
if (!isLoggedIn() || $_SESSION['role'] != 'professor') {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

$conn = connectDB();

// Handle delete request
if (isset($_GET['delete']) && isset($_GET['type'])) {
    $delete_id = (int)$_GET['delete'];
    $delete_type = $_GET['type'];
    
    if ($delete_type == 'book') {
        $table = 'library_books';
    } elseif ($delete_type == 'exam') {
        $table = 'library_exams';
    } else {
        $error_message = 'Invalid file type';
    }
    
    if (empty($error_message)) {
        // Check if file belongs to this professor
        $check_sql = "SELECT file_path FROM $table WHERE id = $delete_id AND uploaded_by = $user_id";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            $file = $check_result->fetch_assoc();
            $file_path = '../' . $file['file_path'];
            
            // Delete from database
            $delete_sql = "DELETE FROM $table WHERE id = $delete_id AND uploaded_by = $user_id";
            if ($conn->query($delete_sql)) {
                // Delete physical file
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                $success_message = 'File deleted successfully';
            } else {
                $error_message = 'Error deleting file';
            }
        } else {
            $error_message = 'File not found or you do not have permission to delete it';
        }
    }
}

// Get professor's uploaded books
$books_sql = "SELECT lb.*, c.name as course_name
              FROM library_books lb
              INNER JOIN courses c ON lb.course_id = c.course_id
              WHERE lb.uploaded_by = $user_id
              ORDER BY lb.upload_date DESC";
$books_result = $conn->query($books_sql);

// Get professor's uploaded exams
$exams_sql = "SELECT le.*, c.name as course_name
              FROM library_exams le
              INNER JOIN courses c ON le.course_id = c.course_id
              WHERE le.uploaded_by = $user_id
              ORDER BY le.upload_date DESC";
$exams_result = $conn->query($exams_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniHive - My Uploads</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="library_styles.css">
    <style>
        .upload-container {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0px 3px 10px rgba(0,0,0,0.1);
        }
        
        .section-title {
            color: #103054;
            font-size: 22px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .list-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .upload-item {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0px 2px 5px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        
        .upload-item:hover {
            transform: translateY(-3px);
            box-shadow: 0px 5px 10px rgba(0,0,0,0.1);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
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
        
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .btn {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn i {
            font-size: 16px;
        }
        
        .btn-info {
            background-color: #17a2b8;
            color: white;
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .professor-controls {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }
        
        .upload-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .upload-btn {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .upload-dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 160px;
            border-radius: 4px;
            box-shadow: 0px 2px 10px rgba(0,0,0,0.1);
            z-index: 1;
        }
        
        .upload-dropdown-content a {
            color: #007bff;
            padding: 12px 16px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .upload-dropdown-content a:hover {
            background-color: #f1f1f1;
        }
        
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .modal-container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0px 4px 20px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .modal-close {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 18px;
            cursor: pointer;
        }
        
        .modal-header {
            margin-bottom: 20px;
        }
        
        .loading-overlay {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            justify-content: center;
            align-items: center;
            z-index: 1001;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(0, 123, 255, 0.3);
            border-top: 4px solid #007bff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .actions {
                flex-wrap: wrap;
            }
            
            .btn {
                flex: 1;
                text-align: center;
                justify-content: center;
            }
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

        <div id="mean" class="mean">
            <ul>
                <li><a href="../home/home.php"><i class='bx bxs-dashboard'></i><p>Dashboard</p></a></li>
                <li><a href="library.php" class="active"><i class='bx bx-library'></i><p>Library</p></a></li>   
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
        <div class="container">
            <h1 class="page-title">My Uploads</h1>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="professor-controls">
                <div class="upload-dropdown">
                    <button class="upload-btn">
                        <i class='bx bx-upload'></i> رفع ملف جديد
                    </button>
                    <div class="upload-dropdown-content">
                        <a href="#" id="openBookUpload"><i class='bx bx-book'></i> رفع كتاب</a>
                        <a href="#" id="openExamUpload"><i class='bx bx-file'></i> رفع امتحان</a>
                    </div>
                </div>
            </div>
            
            <div class="upload-container">
                <h2 class="section-title">Books</h2>
                <?php if ($books_result && $books_result->num_rows > 0): ?>
                    <div class="list-container">
                        <?php while ($book = $books_result->fetch_assoc()): ?>
                            <div class="upload-item">
                                <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                                <p>Course: <?php echo htmlspecialchars($book['course_name']); ?></p>
                                <p>Upload Date: <?php echo date('Y-m-d', strtotime($book['upload_date'])); ?></p>
                                <div class="actions">
                                    <a href="view_page.php?type=book&id=<?php echo $book['id']; ?>" target="_blank" class="btn btn-info"><i class='bx bx-book-open'></i> Read</a>
                                    <a href="download.php?type=book&id=<?php echo $book['id']; ?>" class="btn btn-primary"><i class='bx bx-download'></i> Download</a>
                                    <a href="my_uploads.php?delete=<?php echo $book['id']; ?>&type=book" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this file?')"><i class='bx bx-trash'></i> Delete</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center">You haven't uploaded any books yet</p>
                <?php endif; ?>
            </div>
            
            <div class="upload-container">
                <h2 class="section-title">Exams</h2>
                <?php if ($exams_result && $exams_result->num_rows > 0): ?>
                    <div class="list-container">
                        <?php while ($exam = $exams_result->fetch_assoc()): ?>
                            <div class="upload-item">
                                <h3><?php echo htmlspecialchars($exam['title']); ?></h3>
                                <p>Course: <?php echo htmlspecialchars($exam['course_name']); ?></p>
                                <p>Exam Type: <?php echo htmlspecialchars($exam['exam_type']); ?> - <?php echo $exam['exam_year']; ?></p>
                                <p>Upload Date: <?php echo date('Y-m-d', strtotime($exam['upload_date'])); ?></p>
                                <div class="actions">
                                    <a href="view_page.php?type=exam&id=<?php echo $exam['id']; ?>" target="_blank" class="btn btn-info"><i class='bx bx-file'></i> Read</a>
                                    <a href="download.php?type=exam&id=<?php echo $exam['id']; ?>" class="btn btn-primary"><i class='bx bx-download'></i> Download</a>
                                    <a href="my_uploads.php?delete=<?php echo $exam['id']; ?>&type=exam" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this file?')"><i class='bx bx-trash'></i> Delete</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center">You haven't uploaded any exams yet</p>
                <?php endif; ?>
            </div>
            
            <div style="margin-bottom: 30px; text-align: center;">
                <a href="library.php" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Back to Library</a>
            </div>
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

    <script>
        document.getElementById("menu-toggle").addEventListener("click", function() {
            document.getElementById("mean").classList.toggle("active");
        });
        
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
        
        document.getElementById('closeBookModal').addEventListener('click', function() {
            document.getElementById('bookUploadModal').style.display = 'none';
        });
        
        document.getElementById('closeExamModal').addEventListener('click', function() {
            document.getElementById('examUploadModal').style.display = 'none';
        });
        
        document.getElementById('cancelBookUpload').addEventListener('click', function() {
            document.getElementById('bookUploadModal').style.display = 'none';
        });
        
        document.getElementById('cancelExamUpload').addEventListener('click', function() {
            document.getElementById('examUploadModal').style.display = 'none';
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
                    
                    // Reload page after 2 seconds
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
                    
                    // Reload page after 2 seconds
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
    </script>
</body>
</html>
