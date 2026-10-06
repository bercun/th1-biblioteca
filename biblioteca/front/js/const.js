//REST API constants for the front and admin pages

const API_BASE = "/biblioteca/controllers";
const ADMIN_API = `${API_BASE}/admin`;

const FRONT_URL = "/biblioteca/front/index.php";
const ADMIN_URL = "/biblioteca/admin/index.php";
const BOOK_DEFAULT_IMAGE = "../front/assets/images/bookDefault.png";

const API = {
    register: `${API_BASE}/users/register.php`,
    login: `${API_BASE}/users/login.php`,
    logout: `${API_BASE}/users/logout.php`,
    currentUser: `${API_BASE}/users/current.php`,
    booksSearch: `${API_BASE}/books/search.php`,
    booksFavorites: `${API_BASE}/books/favorites.php`,
    booksBuy: `${API_BASE}/books/buy.php`,
    booksReview: `${API_BASE}/books/review.php`,
    adminBooksList: `${ADMIN_API}/books_list.php`,
    adminBooksCreate: `${ADMIN_API}/books_create.php`,
    adminBooksUpdate: `${ADMIN_API}/books_update.php`,
    adminBooksDelete: `${ADMIN_API}/books_delete.php`,
    adminUsersList: `${ADMIN_API}/users_list.php`,
    adminUsersPurchases: `${ADMIN_API}/users_purchases.php`
};
