


let userIcon=document.querySelector("#userIcon")


userIcon.addEventListener("click",function(){

    userIcon.style.color="#F5C254"
    
})





const targetSections = document.querySelectorAll(".cards_item"); 
const myElement = document.querySelector(".header_search"); 
const myElement2 = document.querySelector(".header_title h1");

console.log(targetSections);
console.log(myElement);

const visibleCards = new Map();

const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    visibleCards.set(entry.target, entry.isIntersecting); 
    const anyCardVisible = [...visibleCards.values()].some(isVisible => isVisible);

    if (anyCardVisible) {
      myElement.style.position = "fixed";
      myElement.style.top = "20px";
      myElement.style.zIndex = "10000";
      myElement.style.marginTop = "0px";
      myElement2.style.display = "none";
      
    } else {
      myElement.style.position = "relative"; 
      myElement.style.marginTop = "220px"; 
      myElement2.style.display = "flex";
    }
  });
}, { threshold: 0.5 }); 


targetSections.forEach(section => {
  observer.observe(section);
  visibleCards.set(section, false);
});




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




const observerS = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('show');
    }
  });
});

const hiddenCards = document.querySelectorAll('.cards_item.hidden');
hiddenCards.forEach(card => observerS.observe(card));