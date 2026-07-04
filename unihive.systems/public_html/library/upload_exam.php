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

// Get professor's courses
$courses_sql = "SELECT c.course_id, c.name as course_name 
                FROM courses c 
                JOIN professor_courses pc ON c.course_id = pc.course_id 
                WHERE pc.professor_id = $user_id";
$courses_result = $conn->query($courses_sql);

// Get course name from URL if provided
$selected_course = isset($_GET['course']) ? $_GET['course'] : '';
$selected_course_id = 0;

if(!empty($selected_course)) {
    // Try to find the course ID based on the course name provided
    $course_lookup = $conn->prepare("SELECT course_id FROM courses WHERE LOWER(name) LIKE ? OR LOWER(name) LIKE ?");
    $search_term = '%' . strtolower($selected_course) . '%';
    $course_lookup->bind_param("ss", $search_term, $search_term);
    $course_lookup->execute();
    $lookup_result = $course_lookup->get_result();
    if($lookup_result->num_rows > 0) {
        $course_data = $lookup_result->fetch_assoc();
        $selected_course_id = $course_data['course_id'];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $course_id = (int)$_POST['course_id'];
    $exam_type = $conn->real_escape_string($_POST['exam_type']);
    $exam_year = (int)$_POST['exam_year'];
    
    // Validate course belongs to professor
    $check_sql = "SELECT 1 FROM professor_courses WHERE professor_id = $user_id AND course_id = $course_id";
    $check_result = $conn->query($check_sql);
    if ($check_result->num_rows == 0) {
        $error_message = 'You do not have permission to upload files for this course';
    } else if (!isset($_FILES['exam_file'])) {
        $error_message = 'No file was selected for upload';
    } else if ($_FILES['exam_file']['error'] != 0) {
        // Get detailed error message based on the error code
        switch ($_FILES['exam_file']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $error_message = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = 'The uploaded file was only partially uploaded';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = 'No file was uploaded';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message = 'Missing a temporary folder';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message = 'Failed to write file to disk';
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_message = 'A PHP extension stopped the file upload';
                break;
            default:
                $error_message = 'Unknown upload error (' . $_FILES['exam_file']['error'] . ')';
        }
    } else {
        // Handle file upload
        $upload_dir = '../uploads/exams/';
        
        // Try to create directory with full permissions if it doesn't exist
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                $error_message = 'Failed to create upload directory. Please check folder permissions.';
            } else {
                // Make sure the permissions are set correctly
                chmod($upload_dir, 0777);
            }
        }

        if (empty($error_message)) {
            // Check if directory is writable
            if (!is_writable($upload_dir)) {
                $error_message = 'Upload directory is not writable. Please check permissions.';
            } else {
                $file_extension = pathinfo($_FILES['exam_file']['name'], PATHINFO_EXTENSION);
                $allowed_extensions = array('pdf', 'doc', 'docx');
                
                if (!in_array(strtolower($file_extension), $allowed_extensions)) {
                    $error_message = 'File type not allowed. Allowed types: PDF, DOC, DOCX';
                } else {
                    $new_filename = uniqid() . '.' . $file_extension;
                    $file_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['exam_file']['tmp_name'], $file_path)) {
                        // Save to database
                        $relative_path = 'uploads/exams/' . $new_filename;
                        $sql = "INSERT INTO library_exams (title, course_id, exam_type, exam_year, uploaded_by, file_path) 
                                VALUES ('$title', $course_id, '$exam_type', $exam_year, $user_id, '$relative_path')";
                        
                        if ($conn->query($sql)) {
                            $success_message = 'Exam uploaded successfully';
                        } else {
                            $error_message = 'Error saving file data: ' . $conn->error;
                            unlink($file_path); // Delete the uploaded file
                        }
                    } else {
                        $error_message = 'Failed to move uploaded file. Check folder permissions.';
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniHive - Upload Exam</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="library_styles.css">
    <style>
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .upload-container {
            max-width: 650px;
            margin: 100px auto 50px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .upload-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .upload-header h1 {
            color: #103054;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .upload-header p {
            color: #6c757d;
            font-size: 16px;
        }
        
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
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <div class="upload-header">
            <h1>Upload Past Exam</h1>
            <p>Share exam materials to help students prepare</p>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class='bx bx-check-circle'></i>
                <span><?php echo $success_message; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class='bx bx-error-circle'></i>
                <span><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>
        
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Exam Title</label>
                <input type="text" class="form-control" id="title" name="title" placeholder="e.g. Database Systems Final Exam" required>
            </div>
            
            <div class="form-group">
                <label for="course_id">Course</label>
                <select class="form-control" id="course_id" name="course_id" required>
                    <option value="">Select Course</option>
                    <?php while ($course = $courses_result->fetch_assoc()): ?>
                        <option value="<?php echo $course['course_id']; ?>" <?php echo ($selected_course_id == $course['course_id']) ? 'selected' : ''; ?>>
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
                
                <div class="file-preview" id="filePreview">
                    <i class='bx bxs-file-pdf'></i>
                    <div class="file-info">
                        <p id="fileName">filename.pdf</p>
                        <p id="fileSize">2.5MB</p>
                    </div>
                </div>
            </div>
            
            <div class="buttons">
                <a href="library.php" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Cancel</a>
                <button type="submit" class="btn btn-primary"><i class='bx bx-upload'></i> Upload Exam</button>
            </div>
        </form>
    </div>

    <script>
        // File preview
        document.getElementById('exam_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const filePreview = document.getElementById('filePreview');
            const fileName = document.getElementById('fileName');
            const fileSize = document.getElementById('fileSize');
            
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
    </script>
</body>
</html>
