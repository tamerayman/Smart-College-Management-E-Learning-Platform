//scrolling
let sec1 = document.getElementById("sec1");
let searchContainer = document.getElementById("searchContainer");
let levelsContainer = document.getElementsByClassName("levelsContainer")[0];

window.onscroll = function(){
  if(scrollY >= 200){
    sec1.style.transition = ".3s ease";
    sec1.style.height = "120px";
    sec1.style.paddingTop = " 75px";
    searchContainer.style.width = "50%";
    levelsContainer.style.display = "none";
    
  }
  else{
    sec1.style.height = "220px";
    sec1.style.paddingTop = " 100px";
    searchContainer.style.width = "60%";
    levelsContainer.style.display = "flex";
  }
}


//drop-down list 
document.getElementsByTagName("button")[0].addEventListener("click", function() {
  let menu = document.getElementById("mean");

  if (menu.style.display === "none" || menu.style.display === "") {
      menu.style.display = "block";
  } else {
      menu.style.display = "none"; 
  }
});

document.querySelectorAll("ul li a").forEach(item => {
  item.addEventListener("click", function() {
   
      document.querySelectorAll("ul li a").forEach(link => {
          link.classList.remove("active");
      });

      this.classList.add("active");
  });
});


//search retrieval

let search = document.getElementById("search");
let books = document.getElementsByClassName("bookContainer");



function searchData(value) {
  let found = false;

  for (let i = 0; i < books.length; i++) {
    if (books[i].textContent.toLocaleLowerCase().includes(value)) {
      books[i].style.display = "block";
      found = true;
    } else {
      books[i].style.display = "none";
    }
  }

  if (!found) {
    // لو مفيش كتب اتعرضت، اعرض رسالة
    document.getElementById("noResults").style.display = "block";
  } else {
    document.getElementById("noResults").style.display = "none";
  }
}

//animated cards
document.addEventListener('DOMContentLoaded', () => {
  const items = document.querySelectorAll('.animate-item');

  const observer = new IntersectionObserver((entries, obs) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        obs.unobserve(entry.target); 
      }
    });
  }, {
    threshold: 0.2  // يتفعل لما 20% من العنصر يظهر في الشاشة
  });

  items.forEach(item => observer.observe(item));
});

// Script for library page

// Show/hide menu
document.getElementById("menu-toggle").addEventListener("click", function() {
    document.getElementById("mean").classList.toggle("active");
});

// Search function
function searchData(keyword) {
    keyword = keyword.toLowerCase();
    let items = document.querySelectorAll(".bookContainer");
    let found = false;
    
    items.forEach(function(item) {
        const text = item.textContent.toLowerCase();
        if(text.includes(keyword)) {
            item.style.display = "block";
            found = true;
        } else {
            item.style.display = "none";
        }
    });
    
    // Show/hide "No Results" message
    document.getElementById("noResults").style.display = found ? "none" : "block";
}

// Animation for items
document.addEventListener("DOMContentLoaded", function() {
    const animateItems = document.querySelectorAll(".animate-item");
    
    animateItems.forEach((item, index) => {
        // Add a slight delay for each item
        setTimeout(() => {
            item.classList.add("visible");
        }, 100 * index);
    });
});