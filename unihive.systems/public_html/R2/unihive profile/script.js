let userIcon=document.querySelector("#userIcon")


userIcon.addEventListener("click",function(){

    userIcon.style.color="#F5C254"
    
})


const targetSection = document.querySelector(".chat_icon"); 
const myElement = document.querySelector(".chat_icon svg"); 


console.log(targetSection)

console.log(myElement)


const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      myElement.style.bottom="80px"

    } else {
        myElement.style.bottom="20px";
    }
  });
}, { threshold: 0.5 });

observer.observe(targetSection);


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
