<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="author" content="Vortex: Zhanna Antipushina, 
                                    Walter Bercunchelli, Karla Medina, Rodrigo Nuñez">
        <meta name="description" content="TH2 Proyecto semestral: 
                                    eLibrary of Brooklyn Public Library">
  
        <title>Brooklyn Public Library</title>
        <link rel="stylesheet" href="../front/style.css">
        <link rel="stylesheet" href="../front/media query.css">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Forum">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter">
        <script
          src="https://kit.fontawesome.com/e7c741f98a.js"
          crossorigin="anonymous">
        </script>

        <link rel="apple-touch-icon" sizes="180x180" href="../front/assets/favicon/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="../front/assets/favicon/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="../front/assets/favicon/favicon-16x16.png">
        <link rel="icon" type="image/png" sizes="192x192" href="../front/assets/favicon/android-chrome-192x192.png">
        <link rel="icon" type="image/png" sizes="512x512" href="../front/assets/favicon/android-chrome-512x512.png">
        
    </head>

    <body>
        <header>
            <div id="header">
                <h1>Brooklyn&nbsp;Public&nbsp;Library</h1>
                <div class="header-links">
                    <nav>
                    <ul>
                        <li><a href="#about">About</a></li>
                        <li><a href="#favorites">Favorites</a></li>
                        <li><a href="#search">Search</a></li>
                        <li><a href="#our-contacts">Help</a></li>   
                    </ul>
                    
                    <ol id="open-menu-burger">
                        <li><a href="#about" class="burger-menu-link">About</a></li>
                        <li><a href="#favorites" class="burger-menu-link">Favorites</a></li>
                        <li><a href="#search" class="burger-menu-link">Book Search</a></li>
                        <li><a href="#our-contacts" class="burger-menu-link">Help</a></li>
                    </ol>
                    </nav>
        
                    <div id="user-menu">
                        <button id="button-user"><img id="user" src="../front/assets/icons/icon_profile.svg" alt="user"></button>
                        <div id="user-inactive">
                            <p>Profile</p>
                            <hr class="line-user">
                            <button class="user-menu-link" id="user-menu-link-login">Log In</button>
                            <button class="user-menu-link" id="user-menu-link-register">Register</button>
                        </div>
                    </div>

                    <div id="dropmenu">
                        <button id="button-user-active"></button>
                        <div id="user-active">
                            <p>Profile</p>
                            <hr class="line-user">
                            <button class="user-menu-link profile" id="user-menu-link-profile">My profile</button>
                            <a class="user-menu-link admin-dashboard-link hidden" id="user-menu-link-admin" href="/biblioteca/admin/index.php">Admin</a>
                            <button class="user-menu-link" id="user-menu-link-logout">Log Out</button>
                        </div>
                    </div>

                    <button class="menu-burger-button" id="menu-burger-button">
                        <span class="burger-lines"></span>
                        <span class="burger-lines"></span>
                        <span class="burger-lines"></span>
                    </button>
                </div>
            </div>
            <h2 id="welcome">WELCOME<br>TO THE BROOKLYN<br>LIBRARY</h2>
        </header>

        <main>
            <section id="about">
                <h2>About</h2>
                <hr class="line">
                
                <p>The Brooklyn Library is a free workspace, a large number of books and a cozy coffee shop inside</p>
            
                <div id="carousel">
                    <div id="carousel-img">
                        <img id="image1" src="../front/assets/images/image1.png" alt="library image" class="img fade">
                        <img id="image2" src="../front/assets/images/image2.png" alt="library image" class="img fade">
                        <img id="image3" src="../front/assets/images/image3.png" alt="library image" class="img fade">
                        <img id="image4" src="../front/assets/images/image4.png" alt="library image" class="img fade">
                        <img id="image5" src="../front/assets/images/image5.png" alt="library image" class="img fade">
                        
                        <button class="prev" id="leftCarret" onclick="plusSlides(-1)" disabled>
                            <img src="../front/assets/icons/Carret_Left.svg" alt="previous slide">
                        </button>
                        <button class="next" id="rightCarret" onclick="plusSlides(1)">
                            <img src="../front/assets/icons/Carret_Right.svg" alt="next slide">
                        </button>
                    </div>
                    <div class="carousel-indicators">
                        <button type="button" id="slide1" class="circle-button active" onclick="currentSlide(1)" aria-label="Slide 1"></button>
                        <button type="button" id="slide2" class="circle-button" onclick="currentSlide(2)" aria-label="Slide 2"></button>
                        <button type="button" id="slide3" class="circle-button" onclick="currentSlide(3)" aria-label="Slide 3"></button>
                        <button type="button" id="slide4" class="circle-button" onclick="currentSlide(4)" aria-label="Slide 4"></button>
                        <button type="button" id="slide5" class="circle-button" onclick="currentSlide(5)" aria-label="Slide 5"></button>
                    </div>
                </div>
            </section>

            <section id="favorites">
                
                <h2>Favorites</h2>
                <hr class="line">

                <fieldset>
                
                    <legend>Pick favorites of season</legend>
                    
                    <div class="fieldset-radio">
                        <input type="radio" id="winter" name="season" class="season" value="winter" checked>
                        <label for="winter">Winter</label>
                    </div>
                    <div class="fieldset-radio">
                        <input type="radio" id="spring" name="season" class="season" value="spring">
                        <label for="spring">Spring</label>
                    </div>
                    <div class="fieldset-radio">
                        <input type="radio" id="summer" name="season" class="season" value="summer">
                        <label for="summer">Summer</label>
                    </div>
                    <div class="fieldset-radio">
                        <input type="radio" id="autumn" name="season" class="season" value="autumn">
                        <label for="autumn">Autumn</label>
                    </div>
                </fieldset>
                

                <div id="favorites-items"></div>

            </section>

            <section id="search">
                <h2>Book Search</h2>
                <hr class="line">

                <div class="search-form">
                    <input type="search" id="search-input" placeholder="Search by title, author or ISBN">
                    <button type="button" id="search-button">Search</button>
                </div>

                <div id="search-results">
                </div>
            </section>


            <section id="our-contacts">
                <h2>Help</h2>
                <hr class="line">
                <div id="info">
                    <ol id="contacts">
                        <li>For all Library inquiries: 
                            <a class="tel" href="tel:+6177302370" target="_blank">(617)7302370</a>
                        </li>
                        <li>For TTY service: 
                            <a class="tel" href="tel:+6177302370" target="_blank">(617)7302370</a>
                        </li>
                        <li id="contact-staff">Library Director: 
                            <a id="mail" href="mailto:director@mail.com" target="_blank">Amanda Hirst</a>
                        </li>
                    </ol>

                    <a class="adress" href="https://goo.gl/maps/5ypowhUEVMVT93iF9" target="_blank">
                        <img id="map" src="../front/assets/images/map.png" alt="map">
                    </a>
                    
                </div>
            </section>

        
        </main>
        
        <footer>
            <div id="contact">
                <a class="adress" href="https://goo.gl/maps/5ypowhUEVMVT93iF9" target="_blank">
                286 Cadman Plaza, New York, NY 11238, United States
                </a>

                <ul id="media">
                    <li>
                        <a class="link" href="https://x.com/bklynlibrary" target="_blank">
                            <i class="fa-brands fa-x-twitter fa-xl" style="color: #ffffff;"></i>
                        </a>
                    </li>
                    <li>
                        <a class="link" href="https://www.instagram.com/bklynlibrary" target="_blank">
                            <i class="fa-brands fa-instagram fa-xl" style="color: #ffffff;"></i>
                        </a>
                    </li>
                    <li>
                        <a class="link" href="https://www.facebook.com/BrooklynPublicLibrary/" target="_blank">
                            <i class="fa-brands fa-square-facebook fa-xl" style="color: #ffffff;"></i>
                        </a>
                    </li>
                </ul>

                <div id="workdays">Mon - Fri<br>8:00 am - 7:00 pm</div>
                <div id="weekend">Sat - Sun<br>10:00 am - 6:00 pm</div>
            </div>
            
            <hr id="footer-line">
            <div id="underline">
                <div id="year">2026</div>
                <a class="link" href="https:www.esi.edu.uy/" target="_blank">
                    ESI, Montevideo
                </a>
                <div id="logo-vortex">TH2 Vortex
                    <img id="vortex" src="../front/assets/icons/vortex.png" alt="logo Vortex">
                </div>  
            </div>
        </footer>

        <div id="overlay" class="overlay">
            <div id="modal-login" class="modal">
                
                    <button class="close-button">x</button> 
                    <h6>LOGIN</h6>     
                
                <form id="form-modal-login">
                    <label class="login-label">E-mail</label>
                    <input class="login-input" type="text" name="email-login" required>
                    <label class="login-label">Password</label>
                    <input class="login-input"type="password" name="password-login" required>
                </form>
                <button type="submit" form="form-modal-login" class="button-login">Log In</button>
                <div class="redirect">
                    <p>Don’t have an account?</p>
                    <button id="no-account-button">Register</button>
                </div>
            </div>
        

            <div id="modal-register" class="modal">
                
                    <button class="close-button">x</button> 
                    <h6>REGISTER</h6>
                
                <form id="form-modal-register">
                    <label class="login-label">First name</label>
                    <input class="login-input" type="text" name="first" required>
                    <label class="login-label">Last name</label>
                    <input class="login-input" type="text" name="last" required>
                    <label class="login-label">E-mail</label>
                    <input class="login-input" type="email" name="email-register" required>
                    <label class="login-label">Password</label>
                    <input class="login-input" type="password" minlength="8" name="password-register" required>
                </form>
                <button type="submit" form="form-modal-register" class="button-signup">Sign Up</button>
                <div class="redirect">
                    <p>Already have an account?</p>
                    <button id="has-account-button">Login</button>
                </div>
            </div>

            <div id="modal-profile" class="modal">
                <aside id="user-data">
                    <div id="avatar"></div>
                    <span id="username-profile"></span>
                </aside>
                <div class="modal-profile-info">
                   <button class="close-button">x</button>
                    <h2>MY PROFILE</h2>
                    <div id="icons">
                            <div id="visit">
                                <h5>Visits</h5>
                                <img src="../front/assets/icons/visits.svg" alt="visits">
                                <span class="visits" id="visit-counter-profile">1</span>
                            </div>

                            <div id="bonus">
                                <h5>Bonuses</h5>
                                <img src="../front/assets/icons/star.svg" alt="bonuses">
                                <span class="bonuses">0</span>
                            </div>

                            <div id="books-number">
                                <h5>Books</h5>
                                <img src="../front/assets/icons/books.svg" alt="books">
                                <span class="books-number">0</span>
                            </div>
                    </div>               
                    <p id="my-books-list">My books:</p>
                    <ul id="my-books"></ul>                    
                </div>
            </div>
        </div>

        <div id="overlay-buy" class="overlay">
            <div id="modal-buy-book">
                <div class="modal-header-buy-book">           
                    <h2>Buy a book</h2>
                    <button id="modal-buy-book-close-button"><img src="../front/assets/icons/close_btn_white.svg" alt="close button"></button> 
                </div>
                <div id="buy-book">
                    <form id="form-modal-buy-book">
                        <label class="buy-book-label">Bank card number</label>
                        <input class="buy-book-input" type="number" minlength="16" name="bank" required>
                        <label class="buy-book-label">Expiration code</label>
                        <div class="buy-book-code-row">
                            <input class="buy-book-input-code" type="number" minlength="2" maxlength="2" name="code-part1" required>
                            <input class="buy-book-input-code" type="number" minlength="2" maxlength="2" name="code-part2" required>
                        </div>
                        <label class="buy-book-label">CVC</label>
                        <input id="buy-book-input-cvc" type="number" minlength="3" maxlength="3" name="CVC" required>     
                        <label class="buy-book-label">Cardholder name</label>
                        <input class="buy-book-input" type="text" name="name" required>
                        <label class="buy-book-label">Postal code</label>
                        <input class="buy-book-input" type="number" name="postal" required>
                        <label class="buy-book-label">City / Town</label>
                        <input class="buy-book-input" type="text" name="city" required>
                        <p id="price">$1</p>
                        <button type="submit" form="form-modal-buy-book" class="buy-book">Buy</button>
                        
                    </form>
                    <p id="add-info-p">Enter your card details to make the purchase. After the purchase, the book will be available for download in your profile.</p>
                    
                </div>
            </div>
        </div>
        <script src="../front/js/index.js"></script>
        <script src="../front/js/const.js"></script>
        <script src="../front/js/utils.js"></script>
        <script src="../front/js/register.js"></script>
        <script src="../front/js/reviews.js"></script>
        <script src="../front/js/favorites.js"></script>
        <script src="../front/js/search.js"></script>
        <script src="../front/js/logout.js"></script>
        
    </body>
</html>