<?php
require_once '../includes/header.php';
?>

<section>
  <img src="../assets/img/courses/accounting.png" alt="">
</section>

<section class="container">
  <div class="description">
    <h1>Accounting</h1>
    <span>Description:<br></span>
    <p>
      "Accounting is the process of recording, summarizing, and analyzing financial transactions to help individuals and organizations make informed financial decisions. It is often referred to as the language of business."
    </p>
  </div>

  <div class="btnsContainer">
    <a href="../view_demo.php?course=accounting&type=read" target="_blank">
      <button>Read <i class="bx bxs-file-pdf"></i></button>
    </a>
    <a href="../view_demo.php?course=accounting&type=download">
      <button>Download <i class="bx bxs-cloud-download"></i></button>
    </a>
    
    <!-- Professor upload buttons - will be shown/hidden via PHP in dynamic version -->
    <div class="professor-upload" id="professorUpload" style="display:none;">
      <a href="../upload.php?course=accounting">
        <button>Upload Book <i class="bx bx-book-add"></i></button>
      </a>
      <a href="../upload_exam.php?course=accounting">
        <button>Upload Exam <i class="bx bx-file-plus"></i></button>
      </a>
    </div>
  </div>
</section>

<script>
  // Check if user is a professor and show upload buttons
  function checkIfProfessor() {
    // In a real implementation, this would use PHP to check the user's role
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
