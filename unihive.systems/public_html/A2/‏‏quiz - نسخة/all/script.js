function toggleMenu() {
    const sidebar = document.getElementById('sidebar');
    const menuIcon = document.querySelector('.menu-icon');
    const rect = menuIcon.getBoundingClientRect(); // جلب موقع أيقونة القائمة
    
    // ضبط موضع القائمة ليكون متناسقًا مع أيقونة الزر
    sidebar.style.top = `${rect.bottom + 5}px`;  // جعلها تحت الزر
    sidebar.style.left = `${rect.left}px`; // جعلها بمحاذاة الزر
    
    // التبديل بين الفتح والإغلاق
    if (sidebar.style.display === 'block') {
        sidebar.style.display = 'none';
    } else {
        sidebar.style.display = 'block';
    }
}

// إغلاق القائمة عند التمرير لأسفل
window.onscroll = function() {
    document.getElementById('sidebar').style.display = 'none';
};


document.querySelectorAll(".mean li a").forEach(item => {
    item.addEventListener("click", function() {
     
        document.querySelectorAll(".mean li a").forEach(link => {
            link.classList.remove("active");
        });

        this.classList.add("active");
    });
});
function deepSearch() {
    const input = document.getElementById('searchInput').value.toLowerCase();
    
    // اختار كل العناصر القابلة للبحث حسب تنسيقك
    const elements = document.querySelectorAll('[data-search], .searchable, div, li, p, span');

    elements.forEach(el => {
      const text = el.textContent.toLowerCase();
      const attributes = Array.from(el.attributes).map(attr => attr.value.toLowerCase()).join(" ");
      const combined = text + " " + attributes;

      if (combined.includes(input)) {
        el.style.display = ""; // إظهار العنصر
      } else {
        el.style.display = "none"; // إخفاؤه
      }
    });
  }


function triggerSearch() {
    document.getElementById('searchInput').focus();
}

function highlightText(searchTerm) {
    let content = document.getElementById('content');
    let innerHTML = content.innerHTML;
    let regex = new RegExp(`(${searchTerm})`, 'gi');

    if (searchTerm.trim() !== "") {
        innerHTML = innerHTML.replace(/<span class="highlight">|<\/span>/g, ""); // إزالة التمييز السابق
        content.innerHTML = innerHTML.replace(regex, '<span class="highlight">$1</span>');
    } else {
        content.innerHTML = innerHTML.replace(/<span class="highlight">|<\/span>/g, ""); // إزالة التمييز
    }
}

document.getElementById('searchInput').addEventListener("keyup", function() {
    let query = document.getElementById('searchInput').value;
    highlightText(query);
});   







