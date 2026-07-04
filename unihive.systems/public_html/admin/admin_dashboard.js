window.addEventListener('DOMContentLoaded', function() {
  const adminNameElement = document.getElementById('adminName');
  const adminPhotoElement = document.getElementById('adminPhoto');
  const logoutBtn = document.getElementById('logoutBtn');

  // تهيئة التبويبات باستخدام Bootstrap API
  const tabElements = document.querySelectorAll('a[data-bs-toggle="tab"]');
  tabElements.forEach(tabElement => {
    const tab = new bootstrap.Tab(tabElement);
    tabElement.addEventListener('click', function(e) {
      e.preventDefault();
      tab.show();
    });
  });

  // ضبط النصوص وعناصر الصفحة للإنجليزية فقط
  document.getElementById('dashboardTitle').textContent = 'Admin Dashboard';
  adminNameElement.textContent = 'مدير النظام';
  adminPhotoElement.src = 'admin.jpg';
  document.getElementById('addStudentTitle').textContent = 'Add Student';
  document.getElementById('addProfessorTitle').textContent = 'Add Professor';
  document.getElementById('addStudentBtn').textContent = 'Add Student';
  document.getElementById('addProfessorBtn').textContent = 'Add Professor';
  document.getElementById('studentListTitle').textContent = 'Students List';
  document.getElementById('profListTitle').textContent = 'Professors List';
  // حذف أي زر أو كود خاص بالوضع الداكن
  const themeToggleBtn = document.getElementById('themeToggleBtn');
  if (themeToggleBtn) {
    themeToggleBtn.style.display = 'none';
  }

  // جعل زر الخروج يعيد التوجيه لصفحة تسجيل الدخول
  logoutBtn.textContent = 'Logout';
  logoutBtn.addEventListener('click', () => {
    window.location.href = '../index.php';
  });

  // معالجة نوع الإشعار
  const targetTypeRadios = document.querySelectorAll('input[name="target_type"]');
  const courseSelect = document.querySelector('.course-select');
  const sendAllBtn = document.querySelector('.send-all');
  const sendCourseBtn = document.querySelector('.send-course');
  
  if (targetTypeRadios.length > 0) {
    targetTypeRadios.forEach(radio => {
      radio.addEventListener('change', function() {
        if (this.value === 'course') {
          courseSelect.style.display = 'block';
          sendAllBtn.style.display = 'none';
          sendCourseBtn.style.display = 'block';
        } else {
          courseSelect.style.display = 'none';
          sendAllBtn.style.display = 'block';
          sendCourseBtn.style.display = 'none';
        }
      });
    });
  }
  
  // التحقق من اختيار المقرر قبل الإرسال
  const notificationForm = document.querySelector('form:has(button[name="send_notification_course"])');
  if (notificationForm) {
    notificationForm.addEventListener('submit', function(e) {
      const selectedTargetType = document.querySelector('input[name="target_type"]:checked');
      if (selectedTargetType && selectedTargetType.value === 'course') {
        const courseId = document.querySelector('select[name="course_id"]').value;
        if (!courseId) {
          e.preventDefault();
          alert('الرجاء اختيار مقرر قبل إرسال الإشعار');
        }
      }
    });
  }
  
  // تأكيد حذف الطالب أو الدكتور
  const deleteButtons = document.querySelectorAll('button[name="delete_student"], button[name="delete_professor"]');
  deleteButtons.forEach(button => {
    button.addEventListener('click', function(e) {
      if (!confirm('هل أنت متأكد من عملية الحذف؟')) {
        e.preventDefault();
      }
    });
  });
});