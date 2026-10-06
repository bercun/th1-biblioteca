<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Brooklyn Public Library</title>
    <link rel="stylesheet" href="../front/style.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Forum">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter">
</head>
<body class="admin-body">
    <header class="admin-header">
        <div class="admin-header-inner">
            <h1>Admin Dashboard</h1>
            <div class="admin-header-actions">
                <span id="admin-user-label"></span>
                <a class="admin-link" id="admin-library-link" href="/biblioteca/front/index.php">Library</a>
                <button type="button" id="admin-logout">Log Out</button>
            </div>
        </div>
    </header>

    <main class="admin-main">
        <p id="admin-access-message" class="admin-message">Checking access...</p>

        <div id="admin-app" class="admin-app hidden">
            <nav class="admin-tabs">
                <button type="button" class="admin-tab active" data-tab="books">Books</button>
                <button type="button" class="admin-tab" data-tab="users">Users</button>
            </nav>

            <section id="tab-books" class="admin-panel">
                <h2>Books</h2>

                <form id="book-form" class="admin-form">
                    <input type="hidden" id="book-id" value="">
                    <div class="admin-form-grid">
                        <label>
                            Title
                            <input type="text" id="book-title" required>
                        </label>
                        <label>
                            Author
                            <input type="text" id="book-author" required>
                        </label>
                        <label>
                            ISBN
                            <input type="text" id="book-isbn" placeholder="optional">
                        </label>
                        <label>
                            Season
                            <select id="book-category" required>
                                <option value="1">Winter</option>
                                <option value="2">Spring</option>
                                <option value="3">Summer</option>
                                <option value="4">Autumn</option>
                            </select>
                        </label>
                        <label>
                            Price
                            <input type="number" id="book-price" min="0" step="0.01" value="1" required>
                        </label>
                        <label>
                            Bonus
                            <input type="number" id="book-bonus" min="0" step="1" value="1" required>
                        </label>
                        <label class="admin-form-wide">
                            Cover image
                            <input
                                type="file"
                                id="book-image-file"
                                accept="image/png,image/jpeg,image/webp,image/gif"
                            >
                            <input type="hidden" id="book-image" value="">
                            <span id="book-image-path" class="admin-image-path">
                                Default: bookDefault.png
                            </span>
                            <img
                                id="book-image-preview"
                                class="admin-image-preview"
                                src="../front/assets/images/bookDefault.png"
                                alt="Book cover preview"
                            >
                        </label>
                        <label class="admin-form-wide">
                            Description
                            <textarea id="book-description" rows="3" required></textarea>
                        </label>
                    </div>
                    <div class="admin-form-actions">
                        <button type="submit" id="book-submit">Add book</button>
                        <button type="button" id="book-cancel" class="hidden">Cancel edit</button>
                    </div>
                </form>

                <div class="admin-table-wrap">
                    <table class="admin-table" id="books-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>ISBN</th>
                                <th>Season</th>
                                <th>Price</th>
                                <th>Bonus</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </section>

            <section id="tab-users" class="admin-panel hidden">
                <h2>Users</h2>
                <div class="admin-table-wrap">
                    <table class="admin-table" id="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Visits</th>
                                <th>Bonus</th>
                                <th>Purchases</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div id="user-purchases" class="admin-purchases hidden">
                    <h3 id="user-purchases-title">Purchases</h3>
                    <div class="admin-table-wrap">
                        <table class="admin-table" id="purchases-table">
                            <thead>
                                <tr>
                                    <th>Book</th>
                                    <th>Author</th>
                                    <th>ISBN</th>
                                    <th>Price</th>
                                    <th>Bonus</th>
                                    <th>Purchased at</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script src="../front/js/const.js"></script>
    <script src="../front/js/utils.js"></script>
    <script src="admin.js"></script>
</body>
</html>
