<?php
// Include authentication and profile functions
require_once 'includes/auth.php';
require_once 'includes/profile_functions.php';

// Check if user is logged in
requireLogin();

// Get current user data
$user = getCurrentUser();
$role = $_SESSION['role'];

// Get departments for dropdowns
$departments = getDepartments();

// Get courses based on role
$courses = [];
if ($role == 'student') {
    $courses = getStudentCourses($user['student_id']);
} elseif ($role == 'professor') {
    $courses = getProfessorCourses($user['professor_id']);
}

// Get profile image if available
$profile_image = "";
$user_id = $_SESSION['user_id'];

// Check for profile image in user_profiles table
global $conn;
$img_sql = "SELECT profile_image FROM user_profiles WHERE user_id = ?";
$img_stmt = $conn->prepare($img_sql);
$img_stmt->bind_param("i", $user_id);
$img_stmt->execute();
$img_result = $img_stmt->get_result();

if ($img_result->num_rows > 0) {
    $profile_data = $img_result->fetch_assoc();
    if (!empty($profile_data['profile_image'])) {
        $profile_image = $profile_data['profile_image'];
    }
}

// Handle profile update
$update_success = false;
$update_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        // Collect form data
        $data = [];
        
        switch ($role) {
            case 'student':
                $data['name'] = $_POST['name'];
                $data['level'] = $_POST['level'];
                $data['department_id'] = $_POST['department_id'];
                break;
                
            case 'professor':
                $data['name'] = $_POST['name'];
                $data['department_id'] = $_POST['department_id'];
                break;
                
            case 'admin':
                $data['name'] = $_POST['name'];
                break;
        }
        
        // Handle profile image if uploaded
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            // Create uploads directory if it doesn't exist
            $upload_dir = __DIR__ . '/uploads/profile_images/';
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    $update_error = "Failed to create upload directory. Check permissions.";
                }
            }
            
            if (empty($update_error)) {
                // Process the uploaded file
                $file_name = time() . '_' . basename($_FILES['profile_image']['name']);
                $target_file = $upload_dir . $file_name;
                $web_path = 'uploads/profile_images/' . $file_name;
                $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                
                // Check if it's a valid image
                $valid_extensions = array("jpg", "jpeg", "png", "gif");
                
                if (in_array($image_file_type, $valid_extensions)) {
                    // Upload file
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                        // Check if record exists in user_profiles
                        $check_sql = "SELECT COUNT(*) as count FROM user_profiles WHERE user_id = ?";
                        $check_stmt = $conn->prepare($check_sql);
                        $check_stmt->bind_param("i", $user_id);
                        $check_stmt->execute();
                        $check_result = $check_stmt->get_result();
                        $count = $check_result->fetch_assoc()['count'];
                        
                        if ($count > 0) {
                            // Update existing record
                            $img_update_sql = "UPDATE user_profiles SET profile_image = ? WHERE user_id = ?";
                            $img_update_stmt = $conn->prepare($img_update_sql);
                            $img_update_stmt->bind_param("si", $web_path, $user_id);
                        } else {
                            // Insert new record
                            $img_update_sql = "INSERT INTO user_profiles (user_id, profile_image) VALUES (?, ?)";
                            $img_update_stmt = $conn->prepare($img_update_sql);
                            $img_update_stmt->bind_param("is", $user_id, $web_path);
                        }
                        
                        if ($img_update_stmt->execute()) {
                            $profile_image = $web_path;
                            $update_success = true;
                        } else {
                            $update_error = "Failed to update profile image in database: " . $conn->error;
                        }
                    } else {
                        $update_error = "Failed to upload image. Check file permissions.";
                    }
                } else {
                    $update_error = "Only JPG, JPEG, PNG & GIF files are allowed.";
                }
            }
        }
        
        // Update profile
        if (empty($update_error) && updateProfile($_SESSION['user_id'], $role, $data)) {
            $update_success = true;
            
            // Refresh user data
            $user = getCurrentUser();
        } else if (empty($update_error)) {
            $update_error = "Failed to update profile.";
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
  header("Location: ../../logout.php");
  exit();
}

// Determine display name and role-specific information
$display_name = isset($user['name']) ? $user['name'] : 'User';
$department = isset($user['department_name']) ? $user['department_name'] : '';
$year_or_role = '';

if ($role == 'student') {
    $year_or_role = 'Year ' . $user['level'];
} elseif ($role == 'professor') {
    $year_or_role = 'Professor';
} elseif ($role == 'admin') {
    $year_or_role = 'Administrator';
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link
      href="https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@100..700&display=swap"
      rel="stylesheet"
    />
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>" />
    <title>Profile - UniHive</title>
    <link rel="icon" type="image/png" href="images/logo_blue.png">
    <style>
 
    </style>
  </head>
  <body>
  <?php if($update_success): ?>
  <div class="alert alert-success alert-msg">Profile updated successfully!</div>
<?php endif; ?>

<?php if(!empty($update_error)): ?>
  <div class="alert alert-danger alert-msg"><?php echo $update_error; ?></div>
<?php endif; ?>
<!-- --------------------------------------------------------------------------------- -->
    <section class="navbar">
      <div class="navbar_container">
        <nav>
          <div class="logo">
            <div class="logo_image">
              <img src="images/logo.png" alt="" />
            </div>
          <div class="div">
            <button id="menu-toggle">
              <i class='bx bx-menu'></i>
          </button>
          <div id="mean" class="mean" >
              <ul>
              <li><a href="../../home/home.php">  <i class='bx bxs-dashboard'></i>       <p>Dashboard</p> </a>       </li>
              <li><a href="../../library/library.php">    <i class='bx bx-library'></i>    <p>Library</p> </a>       </li>   
              <li><a href="../../quiz-system/quiz/index.php">  <i class='bx bx-question-mark'></i>   <p>Quizzes</p> </a>       </li>   
              <li><a href="../unihive course/course.html">    <i class='bx bxs-videos' ></i>        <p>Courses</p>  </a>       </li>   
              <li><a href="#">  <i class='bx bxs-file-blank'></i>    <p>Sheets</p>   </a>       </li>   
              <li><a href="../../meetings/meetings.php">     <i class='bx bxs-video'></i>      <p>Meeting</p>    </a>       </li>   
              <li><a href="#">    <i class='bx bxs-message-rounded-dots' ></i>      <p>Chat</p>   </a>       </li>   
              <li><a href="#">    <i class='bx bxs-calendar'></i>  <p>Calendar</p>    </a>     </li>   
              </ul>
             </div> 
          </div>
          </div>
          <div class="user">
            <div class="user_notifications icon">
              <svg
                width="27"
                height="35"
                viewBox="0 0 40 40"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  fill-rule="evenodd"
                  clip-rule="evenodd"
                  d="M5.10142 13.8514C5.10142 10.1777 6.67109 6.6546 9.46513 4.05697C12.2592 1.45934 16.0487 0 20.0001 0C23.9515 0 27.741 1.45934 30.535 4.05697C33.3291 6.6546 34.8987 10.1777 34.8987 13.8514V15.3904C34.8987 19.7469 36.6645 23.7156 39.5736 26.7177C39.7548 26.9044 39.8841 27.1296 39.95 27.3735C40.016 27.6174 40.0167 27.8726 39.952 28.1168C39.8873 28.361 39.7592 28.5867 39.579 28.7742C39.3987 28.9618 39.1718 29.1054 38.9181 29.1925C35.5101 30.3622 31.9433 31.224 28.255 31.7432C28.3381 32.7982 28.1863 33.8579 27.809 34.8559C27.4318 35.854 26.8373 36.769 26.0626 37.5438C25.288 38.3186 24.3498 38.9367 23.3067 39.3593C22.2636 39.7819 21.1379 40 20.0001 40C18.8622 40 17.7366 39.7819 16.6935 39.3593C15.6504 38.9367 14.7122 38.3186 13.9375 37.5438C13.1629 36.769 12.5683 35.854 12.1911 34.8559C11.8139 33.8579 11.6621 32.7982 11.7451 31.7432C8.10687 31.2306 4.53414 30.3753 1.08209 29.1904C0.828555 29.1034 0.601793 28.96 0.421607 28.7728C0.241422 28.5855 0.113293 28.36 0.0484117 28.1161C-0.0164697 27.8722 -0.0161307 27.6172 0.0493995 27.3735C0.11493 27.1297 0.243658 26.9046 0.424341 26.7177C3.44117 23.6123 5.10821 19.5749 5.10142 15.3904V13.8514ZM15.0383 32.1146C15.01 32.7368 15.1175 33.3578 15.3541 33.9404C15.5907 34.5229 15.9517 35.055 16.4152 35.5045C16.8787 35.954 17.4353 36.3117 18.0513 36.5561C18.6674 36.8004 19.3303 36.9264 20.0001 36.9264C20.6699 36.9264 21.3328 36.8004 21.9488 36.5561C22.5649 36.3117 23.1215 35.954 23.585 35.5045C24.0485 35.055 24.4094 34.5229 24.6461 33.9404C24.8827 33.3578 24.9901 32.7368 24.9619 32.1146C21.6607 32.3911 18.3395 32.3911 15.0383 32.1146Z"
                  fill="#FFFBFB"
                />
              </svg>
            </div>
            <div class="user_profile icon">
              <svg
                id="userIcon"
                width="30"
                viewBox="0 0 87 90"
                fill="none"
                stroke="currentcolor"
                stroke-width="5"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  fill-rule="evenodd"
                  clip-rule="evenodd"
                  d="M73.3888 77.3527C77.6683 73.1998 81.0775 68.1845 83.4058 62.6169C85.734 57.0493 86.9318 51.0476 86.925 44.983C86.925 20.4218 67.6475 0.512939 43.8653 0.512939C20.0831 0.512939 0.805571 20.4218 0.805571 44.983C0.798776 51.0476 1.99652 57.0493 4.32476 62.6169C6.65301 68.1845 10.0623 73.1998 14.3418 77.3527C22.3219 85.1378 32.8877 89.4683 43.8653 89.4531C54.8429 89.4683 65.4086 85.1378 73.3888 77.3527ZM18.0074 71.4917C21.108 67.4855 25.0427 64.2521 29.5193 62.0317C33.9958 59.8114 38.8992 58.661 43.8653 58.6661C48.8313 58.661 53.7347 59.8114 58.2113 62.0317C62.6878 64.2521 66.6225 67.4855 69.7232 71.4917C66.3403 75.0206 62.3127 77.8207 57.8739 79.7295C53.4351 81.6383 48.6735 82.618 43.8653 82.6115C39.057 82.618 34.2954 81.6383 29.8566 79.7295C25.4178 77.8207 21.3902 75.0206 18.0074 71.4917ZM60.4267 31.2999C60.4267 35.8361 58.6818 40.1866 55.576 43.3942C52.4701 46.6018 48.2576 48.4038 43.8653 48.4038C39.4729 48.4038 35.2604 46.6018 32.1546 43.3942C29.0487 40.1866 27.3038 35.8361 27.3038 31.2999C27.3038 26.7637 29.0487 22.4132 32.1546 19.2056C35.2604 15.998 39.4729 14.196 43.8653 14.196C48.2576 14.196 52.4701 15.998 55.576 19.2056C58.6818 22.4132 60.4267 26.7637 60.4267 31.2999Z"
                  fill="white"
                />
              </svg>
            </div>
          </div>
        </nav>
      </div>
    </section>
    <section>
      
    
        <div class="profile">
          <div class="profile_container">
          <div class="profile_user">
            <header>
            <!-- The image will now be pulled up into the title section -->
            <!-- Add image here before the name -->
        <div class="curve"></div>
           <div class="profile_user_image_container   ">
             <img class="profile_user_image"
            src="<?php echo !empty($profile_image) ? $profile_image : 'images/user-removebg-preview (1).png'; ?>"
            alt="<?php echo htmlspecialchars($display_name); ?>" />
                <!-- Add profile image edit option  -->
             <div class="profile_settings_edit_image">
          <a href="#" id="edit-profile-image-btn">
                  <svg xmlns="http://www.w3.org/2000/svg" height="30px" width="30px" fill="currentcolor" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M149.1 64.8L138.7 96 64 96C28.7 96 0 124.7 0 160L0 416c0 35.3 28.7 64 64 64l384 0c35.3 0 64-28.7 64-64l0-256c0-35.3-28.7-64-64-64l-74.7 0L362.9 64.8C356.4 45.2 338.1 32 317.4 32L194.6 32c-20.7 0-39 13.2-45.5 32.8zM256 192a96 96 0 1 1 0 192 96 96 0 1 1 0-192z"/></svg>
          </a>
          <form id="profileImageForm" action="profile.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="update_profile" value="1">
            <?php if($role == 'student'): ?>
            <input type="hidden" name="name" value="<?php echo htmlspecialchars($user['name']); ?>">
            <input type="hidden" name="level" value="<?php echo htmlspecialchars($user['level']); ?>">
            <input type="hidden" name="department_id" value="<?php echo htmlspecialchars($user['department_id']); ?>">
            <?php elseif($role == 'professor'): ?>
            <input type="hidden" name="name" value="<?php echo htmlspecialchars($user['name']); ?>">
            <input type="hidden" name="department_id" value="<?php echo htmlspecialchars($user['department_id']); ?>">
            <?php elseif($role == 'admin'): ?>
            <input type="hidden" name="name" value="<?php echo htmlspecialchars($user['name']); ?>">
            <?php endif; ?>
            <input type="file" name="profile_image" id="profileImageInput" style="display: none;">
          </form>
        </div>
           </div>
</header>
           <div class="profile_user_title">
             <h3><?php echo htmlspecialchars($display_name); ?></h3>
             <p>ahmed@unihive.systems</p>
            <p><?php echo htmlspecialchars($department); ?></p>
            <p class="fourth"><?php echo htmlspecialchars($year_or_role); ?></p>
                 <div class="profile_settings">
          <a href="profile.php?logout=1" class="profile_settings_logout">
            <svg
              width="20"
              height="20"
              viewBox="0 0 28 28"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                fill-rule="evenodd"
                clip-rule="evenodd"
                d="M4.66667 2.15385C4.04783 2.15385 3.45434 2.38077 3.01675 2.78469C2.57917 3.18862 2.33333 3.73646 2.33333 4.30769V23.6923C2.33333 24.2635 2.57917 24.8114 3.01675 25.2153C3.45434 25.6192 4.04783 25.8462 4.66667 25.8462H14C14.6188 25.8462 15.2123 25.6192 15.6499 25.2153C16.0875 24.8114 16.3333 24.2635 16.3333 23.6923V18.3077C16.3333 18.0221 16.4563 17.7482 16.675 17.5462C16.8938 17.3442 17.1906 17.2308 17.5 17.2308C17.8094 17.2308 18.1062 17.3442 18.325 17.5462C18.5438 17.7482 18.6667 18.0221 18.6667 18.3077V23.6923C18.6667 24.8348 18.175 25.9305 17.2998 26.7383C16.4247 27.5462 15.2377 28 14 28H4.66667C3.42899 28 2.242 27.5462 1.36684 26.7383C0.491665 25.9305 0 24.8348 0 23.6923V4.30769C0 3.16522 0.491665 2.06954 1.36684 1.26169C2.242 0.453845 3.42899 0 4.66667 0H14C15.2377 0 16.4247 0.453845 17.2998 1.26169C18.175 2.06954 18.6667 3.16522 18.6667 4.30769V9.69231C18.6667 9.97793 18.5438 10.2518 18.325 10.4538C18.1062 10.6558 17.8094 10.7692 17.5 10.7692C17.1906 10.7692 16.8938 10.6558 16.675 10.4538C16.4563 10.2518 16.3333 9.97793 16.3333 9.69231V4.30769C16.3333 3.73646 16.0875 3.18862 15.6499 2.78469C15.2123 2.38077 14.6188 2.15385 14 2.15385H4.66667ZM12.4911 8.93128C12.7096 9.13321 12.8323 9.40692 12.8323 9.69231C12.8323 9.97769 12.7096 10.2514 12.4911 10.4533L9.81556 12.9231H26.8333C27.1428 12.9231 27.4395 13.0365 27.6583 13.2385C27.8771 13.4405 28 13.7144 28 14C28 14.2856 27.8771 14.5595 27.6583 14.7615C27.4395 14.9635 27.1428 15.0769 26.8333 15.0769H9.81556L12.4911 17.5467C12.6057 17.6453 12.6977 17.7642 12.7614 17.8963C12.8252 18.0284 12.8595 18.171 12.8623 18.3156C12.865 18.4602 12.8362 18.6038 12.7775 18.7379C12.7188 18.872 12.6315 18.9938 12.5207 19.0961C12.4099 19.1983 12.278 19.2789 12.1327 19.3331C11.9874 19.3873 11.8318 19.4139 11.6752 19.4113C11.5185 19.4088 11.3641 19.3771 11.2209 19.3183C11.0778 19.2594 10.949 19.1745 10.8422 19.0687L6.17556 14.761C5.95708 14.5591 5.83436 14.2854 5.83436 14C5.83436 13.7146 5.95708 13.4409 6.17556 13.239L10.8422 8.93128C11.061 8.72961 11.3575 8.61633 11.6667 8.61633C11.9758 8.61633 12.2724 8.72961 12.4911 8.93128Z"
                fill="#fff"
              />
            </svg>
            <p>log out</p>
          </a>
        </div>
           </div>
                
              
                   
        </div>
        <div class="profile_user_courses">
          <?php if(!empty($courses)): ?>
          <div class="courses-section">
     <div class="courses-section-header">
           <h4><svg xmlns="http://www.w3.org/2000/svg" height="14" width="15.75" viewBox="0 0 576 512" fill="#fff"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M249.6 471.5c10.8 3.8 22.4-4.1 22.4-15.5l0-377.4c0-4.2-1.6-8.4-5-11C247.4 52 202.4 32 144 32C93.5 32 46.3 45.3 18.1 56.1C6.8 60.5 0 71.7 0 83.8L0 454.1c0 11.9 12.8 20.2 24.1 16.5C55.6 460.1 105.5 448 144 448c33.9 0 79 14 105.6 23.5zm76.8 0C353 462 398.1 448 432 448c38.5 0 88.4 12.1 119.9 22.6c11.3 3.8 24.1-4.6 24.1-16.5l0-370.3c0-12.1-6.8-23.3-18.1-27.6C529.7 45.3 482.5 32 432 32c-58.4 0-103.4 20-123 35.6c-3.3 2.6-5 6.8-5 11L304 456c0 11.4 11.7 19.3 22.4 15.5z"/></svg> my courses</h4>
     </div>
     <div class="courses-scroll-area">
          <ul class="courses-list">
            <?php foreach($courses as $course): ?>
              <li><?php echo htmlspecialchars($course['name']); ?></li>
            <?php endforeach; ?>
          </ul>
              </div>
              </div>
            <?php endif; ?>
          </div>
          </div>
        </div>
   
    </section>
    <section>
      <main>
        
      </main>
    </section>

    
    <div class="footer">
      <div class="footer_container">
        <div class="footer_content">
          <a href="#" class="website">www.unihive.com</a>
          <div class="footer_links">
            <!-- ...existing code... -->
          </div>
        </div>
      </div>
    </div>
    
    <script src="script.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const editImageBtn = document.getElementById('edit-profile-image-btn');
        const fileInput = document.getElementById('profileImageInput');
        
        editImageBtn.addEventListener('click', function(e) {
          e.preventDefault();
          fileInput.click();
        });
        
        fileInput.addEventListener('change', function() {
          if (this.files && this.files[0]) {
            document.getElementById('profileImageForm').submit();
          }
        });
      });

      setTimeout(function() {
    document.querySelectorAll('.alert-msg').forEach(function(el) {
      el.style.display = 'none';
    });
  }, 3000);
    </script>
  </body>
</html>
