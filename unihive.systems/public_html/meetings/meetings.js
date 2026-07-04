document.addEventListener('DOMContentLoaded', function() {
    // Language switching
    const langToggle = document.getElementById('langToggle');
    const currentLang = document.getElementById('currentLang');
    const htmlElement = document.documentElement;
    
    // Check saved language preference
    const savedLang = getCookie('lang') || 'en';
    htmlElement.setAttribute('data-lang', savedLang);
    
    // Update language display
    if (savedLang === 'ar') {
        currentLang.textContent = 'العربية';
    } else {
        currentLang.textContent = 'English';
    }
    
    // Language toggle functionality
    if (langToggle) {
        langToggle.addEventListener('click', function() {
            const currentLanguage = htmlElement.getAttribute('data-lang');
            const newLanguage = currentLanguage === 'en' ? 'ar' : 'en';
            
            // Set language attribute
            htmlElement.setAttribute('data-lang', newLanguage);
            
            // Update text
            if (newLanguage === 'ar') {
                currentLang.textContent = 'العربية';
            } else {
                currentLang.textContent = 'English';
            }
            
            // Save preference
            setCookie('lang', newLanguage, 30);
        });
    }
    
    // Recording search functionality
    const searchInput = document.getElementById('searchRecordings');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const recordingItems = document.querySelectorAll('.recording-item');
            
            recordingItems.forEach(item => {
                const date = item.getAttribute('data-date').toLowerCase();
                const text = item.textContent.toLowerCase();
                
                if (text.includes(searchTerm) || date.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Check if any recordings are visible in each month section
            const monthSections = document.querySelectorAll('.recordings-month');
            monthSections.forEach(section => {
                const visibleItems = section.querySelectorAll('.recording-item[style=""]').length;
                if (visibleItems === 0) {
                    section.style.display = 'none';
                } else {
                    section.style.display = '';
                }
            });
        });
    }

    // Initialize date picker
    if (typeof flatpickr !== 'undefined') {
        flatpickr(".flatpickr", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: "today",
            time_24hr: true
        });
    }
    
    // Show recordings modal if needed
    if (typeof bootstrap !== 'undefined') {
        const recordingsModalElement = document.getElementById('recordingsModal');
        if (recordingsModalElement) {
            const recordingsExist = recordingsModalElement.querySelector('.recordings-container');
            if (recordingsExist) {
                const recordingsModal = new bootstrap.Modal(recordingsModalElement);
                recordingsModal.show();
            }
        }
    }
    
    // Helper functions for cookies
    function setCookie(name, value, days) {
        let expires = "";
        if (days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }
    
    function getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }

    // تهيئة تقويم Flatpickr
    if (document.querySelector(".flatpickr")) {
        flatpickr(".flatpickr", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: "today"
        });
    }

    // تبديل اللغة
    if (langToggle) {
        langToggle.addEventListener('click', function() {
            const currentLang = document.documentElement.getAttribute('data-lang') || 'en';
            const newLang = currentLang === 'en' ? 'ar' : 'en';
            
            // تعيين كوكي اللغة
            document.cookie = `lang=${newLang}; path=/; max-age=31536000`;
            document.documentElement.setAttribute('data-lang', newLang);
            
            // تحديث نص زر اللغة
            document.getElementById('currentLang').textContent = newLang === 'en' ? 'English' : 'العربية';
            
            // إذا أردنا إعادة تحميل الصفحة لتطبيق اللغة
            // window.location.reload();
        });
    }

    // تحويل أزرار "Join Meeting" و "Start Meeting" لاستخدام AJAX
    document.querySelectorAll('.join-meeting-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const meetingId = this.getAttribute('data-meeting-id');
            const formData = new FormData();
            formData.append('action', 'join');
            formData.append('meeting_id', meetingId);
            
            // إظهار مؤشر التحميل
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Please wait...';
            this.disabled = true;
            
            fetch('meeting_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // فتح الاجتماع في نافذة جديدة
                    window.open(data.url, '_blank');
                } else {
                    // إظهار رسالة الخطأ
                    alert(data.message || 'Failed to join the meeting');
                }
                
                // إعادة تمكين الزر
                this.innerHTML = this.classList.contains('is-active') ? 'Join Meeting' : 'Start Meeting';
                this.disabled = false;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while trying to join the meeting');
                
                // إعادة تمكين الزر
                this.innerHTML = this.classList.contains('is-active') ? 'Join Meeting' : 'Start Meeting';
                this.disabled = false;
            });
        });
    });
    
    // تحويل أزرار "View Recordings" لاستخدام AJAX
    document.querySelectorAll('.view-recordings-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const meetingId = this.getAttribute('data-meeting-id');
            const meetingName = this.getAttribute('data-meeting-name');
            const courseId = this.getAttribute('data-course-id');
            
            const formData = new FormData();
            formData.append('action', 'view_recordings');
            formData.append('meeting_id', meetingId);
            formData.append('meeting_name', meetingName);
            formData.append('course_id', courseId);
            
            // إظهار مؤشر التحميل
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...';
            this.disabled = true;
            
            fetch('meeting_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.showModal) {
                    // عرض المودال
                    var recordingsModal = new bootstrap.Modal(document.getElementById('recordingsModal'));
                    recordingsModal.show();
                }
                
                // إعادة تمكين الزر
                this.innerHTML = '<i class="bx bx-video-recording"></i> View Recordings';
                this.disabled = false;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while trying to load recordings');
                
                // إعادة تمكين الزر
                this.innerHTML = '<i class="bx bx-video-recording"></i> View Recordings';
                this.disabled = false;
            });
        });
    });
    
    // البحث في التسجيلات
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.recording-item').forEach(item => {
                const dateText = item.getAttribute('data-date').toLowerCase();
                if (dateText.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
});
