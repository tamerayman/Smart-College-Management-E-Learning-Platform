<?php
require_once '../includes/header.php';
?>

<section>
  <img src="../assets/img/courses/database.png" alt="">
</section>

<section class="container">
  <div class="description">
    <h1>Database</h1>
    <span>Description:<br></span>
    <p>"Database is a subject that focuses on the design, creation, and management of data stored in a structured format. It teaches how to organize, retrieve, and manipulate data using systems like SQL."</p>
  </div>

  <div class="btnsContainer">
    <a href="../view_demo.php?course=database&type=read" target="_blank">
      <button>Read <i class="bx bxs-file-pdf"></i></button>
    </a>
    <a href="../view_demo.php?course=database&type=download">
      <button>Download <i class="bx bxs-cloud-download"></i></button>
    </a>
    
    <!-- Professor upload buttons -->
    <div class="professor-upload" id="professorUpload" style="display:none;">
      <a href="../upload.php?course=database">
        <button>Upload Book <i class="bx bx-book-add"></i></button>
      </a>
      <a href="../upload_exam.php?course=database">
        <button>Upload Exam <i class="bx bx-file-plus"></i></button>
      </a>
    </div>
  </div>
</section>

<script>
  // Same professor check script
  function checkIfProfessor() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('role') === 'professor') {
      document.getElementById('professorUpload').style.display = 'block';
    }
  }
  window.onload = checkIfProfessor;
</script>

<?php
require_once '../includes/footer.php';
?>
