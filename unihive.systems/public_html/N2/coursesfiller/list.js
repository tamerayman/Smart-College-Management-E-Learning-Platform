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