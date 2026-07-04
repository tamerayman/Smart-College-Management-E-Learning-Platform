/**
 * مكونات واجهة المستخدم الموحدة لنظام الإدارة
 * يتم تحميل هذا الملف في كل صفحات الإدارة
 */

document.addEventListener('DOMContentLoaded', function() {
    // تهيئة مكونات Bootstrap
    initializeBootstrapComponents();
    
    // إضافة تأثيرات للأزرار
    enhanceButtons();
    
    // تحسينات للجداول 
    enhanceTables();
    
    // تأكيدات للأزرار الخطرة
    setupDangerousActionConfirmations();
    
    // تحسين النماذج وإضافة التحقق
    enhanceForms();
    
    // تحسين التفاعل مع البطاقات
    enhanceCards();

    // تفعيل ميزة البحث المباشر
    setupLiveSearch();
    
    // إضافة تأثيرات انتقالية
    addTransitionEffects();
});

/**
 * تهيئة مكونات Bootstrap
 */
function initializeBootstrapComponents() {
    // تهيئة التلميحات
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // تهيئة البوبوفر
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

/**
 * إضافة تأثيرات للأزرار
 */
function enhanceButtons() {
    const buttons = document.querySelectorAll('.btn');
    
    buttons.forEach(button => {
        // إضافة تأثير عند النقر
        button.addEventListener('click', function(e) {
            if (!this.classList.contains('no-ripple')) {
                const ripple = document.createElement('span');
                const diameter = Math.max(this.clientWidth, this.clientHeight);
                
                ripple.style.width = ripple.style.height = `${diameter}px`;
                ripple.style.left = `${e.clientX - this.getBoundingClientRect().left - diameter/2}px`;
                ripple.style.top = `${e.clientY - this.getBoundingClientRect().top - diameter/2}px`;
                
                ripple.classList.add('ripple');
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            }
        });
    });
}

/**
 * تحسينات للجداول
 */
function enhanceTables() {
    const tables = document.querySelectorAll('.table');
    
    tables.forEach(table => {
        // إضافة تأثير التحويم على صفوف الجدول
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transition = 'background-color 0.2s ease';
                this.style.backgroundColor = 'rgba(0, 0, 0, 0.03)';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
        
        // إضافة قابلية الفرز للأعمدة إذا كانت هناك رأس للجدول
        if (table.querySelector('thead')) {
            const headers = table.querySelectorAll('thead th');
            headers.forEach((header, index) => {
                if (!header.classList.contains('no-sort')) {
                    header.style.cursor = 'pointer';
                    header.addEventListener('click', function() {
                        sortTable(table, index);
                    });
                }
            });
        }
    });
}

/**
 * فرز الجدول عند النقر على رأس العمود
 */
function sortTable(table, columnIndex) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const isAsc = table.querySelectorAll('thead th')[columnIndex].classList.toggle('asc');
    
    // فرز الصفوف
    rows.sort((a, b) => {
        const cellA = a.querySelectorAll('td')[columnIndex].textContent.trim();
        const cellB = b.querySelectorAll('td')[columnIndex].textContent.trim();
        
        if (!isNaN(cellA) && !isNaN(cellB)) {
            return isAsc ? parseFloat(cellA) - parseFloat(cellB) : parseFloat(cellB) - parseFloat(cellA);
        } else {
            return isAsc ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
        }
    });
    
    // إعادة ترتيب الصفوف في الجدول
    rows.forEach(row => tbody.appendChild(row));
}

/**
 * إضافة تأكيدات للأزرار الخطرة مثل الحذف
 */
function setupDangerousActionConfirmations() {
    // تأكيد حذف الطالب أو الأستاذ
    const deleteButtons = document.querySelectorAll('button[name="delete_student"], button[name="delete_professor"], .delete-btn');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const isArabic = document.documentElement.lang === 'ar';
            const message = isArabic ? 'هل أنت متأكد من حذف هذا العنصر؟' : 'Are you sure you want to delete this item?';
            
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

/**
 * تحسين النماذج وإضافة التحقق
 */
function enhanceForms() {
    const forms = document.querySelectorAll('form.needs-validation');
    
    forms.forEach(form => {
        // إضافة تأثيرات للحقول عند الكتابة
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
                
                // إضافة تحقق بسيط
                if (this.hasAttribute('required') && this.value.trim() === '') {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                    if (this.value.trim() !== '') {
                        this.classList.add('is-valid');
                    }
                }
            });
        });
        
        // التحقق من النموذج قبل الإرسال
        form.addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                
                // تلقائياً التمرير إلى أول حقل غير صالح
                const firstInvalid = this.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
            
            this.classList.add('was-validated');
        }, false);
    });
}

/**
 * تحسين التفاعل مع البطاقات
 */
function enhanceCards() {
    const cards = document.querySelectorAll('.card');
    
    cards.forEach(card => {
        // إضافة تأثير رفع بسيط عند التحويم
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 20px rgba(0, 0, 0, 0.1)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });
}

/**
 * تفعيل ميزة البحث المباشر
 */
function setupLiveSearch() {
    const searchInputs = document.querySelectorAll('.search-input');
    
    searchInputs.forEach(input => {
        const targetId = input.dataset.target;
        const target = document.getElementById(targetId);
        
        if (!target) return;
        
        input.addEventListener('input', function() {
            const searchValue = this.value.toLowerCase();
            const items = target.querySelectorAll('.search-item');
            
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                const shouldShow = text.includes(searchValue);
                
                if (shouldShow) {
                    item.style.display = '';
                    
                    // تمييز نص البحث
                    if (searchValue.length > 0) {
                        if (!item.dataset.originalHtml) {
                            item.dataset.originalHtml = item.innerHTML;
                        }
                        
                        const regex = new RegExp(`(${searchValue})`, 'gi');
                        item.innerHTML = item.dataset.originalHtml.replace(regex, '<mark>$1</mark>');
                    } else if (item.dataset.originalHtml) {
                        item.innerHTML = item.dataset.originalHtml;
                    }
                } else {
                    item.style.display = 'none';
                }
            });
            
            // عرض رسالة عند عدم وجود نتائج
            const noResults = target.querySelector('.no-results');
            if (noResults) {
                const visibleItems = Array.from(items).filter(item => item.style.display !== 'none');
                noResults.style.display = visibleItems.length === 0 ? 'block' : 'none';
            }
        });
    });
}

/**
 * إضافة تأثيرات انتقالية لعناصر الصفحة
 */
function addTransitionEffects() {
    // تأثير ظهور تدريجي للبطاقات
    const fadeInElements = document.querySelectorAll('.fade-in');
    fadeInElements.forEach(element => {
        element.style.opacity = '0';
        element.style.transition = 'opacity 0.5s ease-in-out';
        
        // تأخير قصير قبل الظهور للحصول على تأثير أفضل
        setTimeout(() => {
            element.style.opacity = '1';
        }, 100);
    });
    
    // تأثير انزلاق للعناصر
    const slideInElements = document.querySelectorAll('.slide-in');
    slideInElements.forEach(element => {
        element.style.transform = 'translateY(20px)';
        element.style.opacity = '0';
        element.style.transition = 'transform 0.5s ease-out, opacity 0.5s ease-out';
        
        setTimeout(() => {
            element.style.transform = 'translateY(0)';
            element.style.opacity = '1';
        }, 200);
    });
}
