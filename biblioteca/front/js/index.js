//all event listeners for the index.php page
//frontend
//burger
document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("menu-burger-button").addEventListener("click", function() {
        document.querySelector(".header-links").classList.toggle("open")
    })
});

//Close burger by Esc
window.addEventListener('keydown', (e) => {
    if (e.key === "Escape") {
        document.querySelector(".header-links").classList.remove("open")
    }
});

// Close burger by outer click
document.getElementById("open-menu-burger").addEventListener('click', event => {
    if (event._isClickWithInMenu) return;
    document.querySelector(".header-links").classList.remove("open")
});
document.getElementById("menu-burger-button").addEventListener('click', event => {
    event._isClickWithInMenu = true;
});
document.body.addEventListener('click', event => {
    if (event._isClickWithInMenu) return;
    document.querySelector(".header-links").classList.remove("open") 
});


//Open user-menu
document.addEventListener("DOMContentLoaded", function() {
     document.getElementById("button-user").addEventListener("click", function() {
         document.getElementById("user-menu").classList.toggle("open")
     })
 });

//Open user-menu after registration
document.addEventListener("DOMContentLoaded", function() {
   document.getElementById("button-user-active").addEventListener("click", function() {
       document.getElementById("dropmenu").classList.toggle("open")
   })
});

//Close user-menu by Esc
window.addEventListener('keydown', (e) => {
    if (e.key === "Escape") {
        document.getElementById("user-menu").classList.remove("open");
    }
});

//Close user-menu by outer click
document.getElementById("button-user").addEventListener('click', event => {
    event._isClickWithInUserMenu = true;
 });

document.getElementById("user-inactive").addEventListener('click', event => {
    if (event._isClickWithInUserMenu) return;
    document.getElementById("user-menu").classList.remove("open")
});

document.body.addEventListener('click', event => {
    if (event._isClickWithInUserMenu) return;
    document.getElementById("user-menu").classList.remove("open") 
});

// Close dropmenu by outer click
document.getElementById("button-user-active").addEventListener('click', event => {
   event._isClickWithInUserMenu = true;
});

document.getElementById("user-active").addEventListener('click', event => {
   if (event._isClickWithInUserMenu) return;
   document.getElementById("dropmenu").classList.remove("open")
});

document.body.addEventListener('click', event => {
   if (event._isClickWithInUserMenu) return;
   document.getElementById("dropmenu").classList.remove("open") 
});


/*Open modale window Login in user-menu*/
document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("user-menu-link-login").addEventListener("click", function() {
        document.getElementById("overlay").classList.toggle("overlay-open");
        let login = document.getElementById("modal-login");
        login.style.display = 'flex';
    })
});

/* from Login to Register*/
document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("no-account-button").addEventListener("click", function() {
        let login = document.getElementById("modal-login");
        login.style.display = 'none';
        let register = document.getElementById("modal-register");
        register.style.display = 'flex';
    })
});

/*Open modale window Register in user-menu*/
document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("user-menu-link-register").addEventListener("click", function() {
        document.getElementById("overlay").classList.toggle("overlay-open");
        let register = document.getElementById("modal-register");
        register.style.display = 'flex';
    })
});

/*from Register to Login*/
document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("has-account-button").addEventListener("click", function() {
        let register = document.getElementById("modal-register");
        register.style.display = 'none';
        let login = document.getElementById("modal-login");
        login.style.display = 'flex';
    })
});

//Close modal window Buy a book and overlay by x
document.getElementById("modal-buy-book-close-button").addEventListener('click', event => {
    document.getElementById("overlay-buy").classList.remove("overlay-open");
    let modalBuyCard = document.getElementById("modal-buy-book");
    modalBuyCard.style.display = 'none';
});

/*Open profile modal window*/
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".profile").forEach(el => el.addEventListener("click", function() {
        document.getElementById("overlay").classList.toggle("overlay-open");
        let register = document.getElementById("modal-profile");
        register.style.display = 'flex';
    }))
});

/*Close modal windows by x */
document.querySelectorAll(".close-button").forEach(el => el.addEventListener('click', event => {
    document.getElementById("overlay").classList.remove("overlay-open");
    let div = document.querySelectorAll(".modal");
    div.forEach(el => el.style.display = 'none');
}));

// Slider About
let slideIndex = 1;

const handleArrowButtons = (slides) => {
    const firstSlide = slides[0];
    const lastSlide = slides[slides.length - 1];

    const arrowLeft = document.getElementById("leftCarret");
    const arrowRight = document.getElementById("rightCarret");


    if (firstSlide.style.display === 'block') {
        arrowLeft.setAttribute('disabled', true);
    } else {
        arrowLeft.removeAttribute('disabled');
    }

    if (lastSlide.style.display === 'block') {
        arrowRight.setAttribute('disabled', true);
    } else {
        arrowRight.removeAttribute('disabled');
    }
}

showSlides(slideIndex);

// Next/previous controls
function plusSlides(n) {
  showSlides(slideIndex += n);
};

// Thumbnail image controls
function currentSlide(n) {
  showSlides(slideIndex = n);
};

function showSlides(n) {
    const showCount = window.innerWidth < 1400 ? 1 : 3;
    const slides = document.getElementsByClassName("img");
    const dots = document.getElementsByClassName("circle-button");
    if (n > slides.length) {slideIndex = 1}
    if (n < 1) {slideIndex = slides.length}
    for (let i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";
    }
    for (let i = 0; i < dots.length; i++) {
        dots[i].className = dots[i].className.replace(" active", "");
    }

    if (showCount === 1) {
        slides[slideIndex-1].style.display = "block";
    } else {
        Array.from(slides).forEach((slide, index) => {
            if (index >= n - 1 && index < (n + showCount - 1)) {
                slide.style.display = "block";
            }
        })        
    }
    dots[slideIndex-1].className += " active";
    handleArrowButtons(slides);
};

let resizeTimer;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => showSlides(slideIndex), 120);
});
