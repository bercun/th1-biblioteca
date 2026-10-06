let adminBooks = [];
let editingBookId = null;
//add event listeners to the admin page
document.addEventListener("DOMContentLoaded", async () => {
    const libraryLink = document.getElementById("admin-library-link");

    if (libraryLink) {
        libraryLink.href = FRONT_URL;
    }

    document
        .getElementById("admin-logout")
        ?.addEventListener("click", adminLogout);

    document
        .querySelectorAll(".admin-tab")
        .forEach(tab => {
            tab.addEventListener("click", () => {
                switchTab(tab.dataset.tab);
            });
        });

    document
        .getElementById("book-form")
        ?.addEventListener("submit", saveBook);

    document
        .getElementById("book-cancel")
        ?.addEventListener("click", resetBookForm);

    document
        .getElementById("book-image-file")
        ?.addEventListener("change", previewSelectedBookImage);

    await initAdmin();
});

function setBookImagePreview(imagePath) {
    const preview = document.getElementById("book-image-preview");
    const pathLabel = document.getElementById("book-image-path");
    const hiddenInput = document.getElementById("book-image");
    const resolved = String(imagePath || "").trim() || BOOK_DEFAULT_IMAGE;

    if (hiddenInput) {
        hiddenInput.value =
            resolved === BOOK_DEFAULT_IMAGE ? "" : resolved;
    }

    if (pathLabel) {
        pathLabel.textContent =
            resolved === BOOK_DEFAULT_IMAGE
                ? "Default: bookDefault.png"
                : resolved;
    }

    if (preview) {
        preview.src = resolved;
    }
}

function previewSelectedBookImage(event) {
    const file = event.target.files?.[0];
    const preview = document.getElementById("book-image-preview");
    const pathLabel = document.getElementById("book-image-path");

    if (!file || !preview) {
        return;
    }

    preview.src = URL.createObjectURL(file);

    if (pathLabel) {
        pathLabel.textContent = `Selected: ${file.name}`;
    }
}

//initialize admin
async function initAdmin() {
    const message = document.getElementById("admin-access-message");
    const app = document.getElementById("admin-app");

    //try to get current user
    try {
        const response = await fetch(
            API.currentUser,
            { cache: "no-store" }
        );
        const user = await response.json();

        //if response is not ok or user is not found, return
        if (!response.ok || !user) {
            message.replaceChildren();
            message.append("Access denied. Please ");

            const link = document.createElement("a");
            link.href = FRONT_URL;
            link.textContent = "log in";
            message.appendChild(link);

            message.append(" as admin.");
            return;
        }

        //if user role is not admin, return
        if ((user.role ?? "user") !== "admin") {
            message.textContent =
                "Access denied. Admin role required.";
            return;
        }

        //show admin app
        message.classList.add("hidden");
        app.classList.remove("hidden");

        document.getElementById("admin-user-label").textContent =
            `${user.first_name} ${user.last_name} (${user.email})`;

        await loadBooks();
        await loadUsers();

    } catch (error) {
        console.error(error);
        message.textContent = "Unable to connect to the server.";
    }
}

//switch tab
function switchTab(tabName) {
    //loop through admin tabs
    document.querySelectorAll(".admin-tab").forEach(tab => {
        tab.classList.toggle("active", tab.dataset.tab === tabName);
    });

    //if tab name is not books, hide books tab
    document.getElementById("tab-books")
        ?.classList.toggle("hidden", tabName !== "books");
    //if tab name is not users, hide users tab
    document.getElementById("tab-users")
        ?.classList.toggle("hidden", tabName !== "users");
}

//logout admin
async function adminLogout() {
    //try to logout admin
    try {
        await fetch(API.logout, {
            method: "POST"
        });
        //if error, log error
    } catch (error) {
        console.error(error);
    }

    //redirect to front url
    window.location.href = FRONT_URL;
}

//load books
async function loadBooks() {
    //try to fetch books
    const response = await fetch(API.adminBooksList);
    const data = await response.json();

    //if response is not ok, alert error
    if (!response.ok) {
        alert(data.message ?? "Unable to load books");
        return;
    }

    //set admin books
    adminBooks = Array.isArray(data.books) ? data.books : [];
    renderBooksTable();
}

//append text cell
function appendTextCell(row, text) {
    //create text cell
    const cell = document.createElement("td");
    cell.textContent = text;
    //append text cell to row
    row.appendChild(cell);
    //return text cell
    return cell;
}

//render books table
function renderBooksTable() {
    //get tbody element
    const tbody = document.querySelector("#books-table tbody");
    if (!tbody) return;

    //clear tbody
    tbody.replaceChildren();

    adminBooks.forEach(book => {
        const tr = document.createElement("tr");

        appendTextCell(tr, String(book.id));
        appendTextCell(tr, book.title ?? "");
        appendTextCell(tr, book.author ?? "");
        appendTextCell(tr, book.isbn ?? "—");
        appendTextCell(tr, book.season ?? "");
        appendTextCell(tr, Number(book.price).toFixed(2));
        appendTextCell(tr, String(Number(book.bonus)));

        const actions = document.createElement("td");
        actions.className = "actions";

        const editButton = document.createElement("button");
        editButton.type = "button";
        editButton.dataset.edit = String(book.id);
        editButton.textContent = "Edit";
        actions.appendChild(editButton);

        const deleteButton = document.createElement("button");
        deleteButton.type = "button";
        deleteButton.dataset.delete = String(book.id);
        deleteButton.textContent = "Delete";
        actions.appendChild(deleteButton);

        tr.appendChild(actions);
        tbody.appendChild(tr);
    });

    tbody.querySelectorAll("[data-edit]").forEach(button => {
        button.addEventListener("click", () => {
            startEditBook(Number(button.dataset.edit));
        });
    });

    tbody.querySelectorAll("[data-delete]").forEach(button => {
        button.addEventListener("click", () => {
            deleteBook(Number(button.dataset.delete));
        });
    });
}
//start edit book
function startEditBook(bookId) {
    //find book in admin books
    const book = adminBooks.find(item => Number(item.id) === bookId);
    //if book is not found, return
    if (!book) return;
    //set editing book id

    editingBookId = bookId;
    //set book id
    document.getElementById("book-id").value = String(book.id);
    //set book title
    document.getElementById("book-title").value = book.title ?? "";
    //set book author
    document.getElementById("book-author").value = book.author ?? "";
    //set book isbn
    document.getElementById("book-isbn").value = book.isbn ?? "";
    //set book category
    document.getElementById("book-category").value = String(book.category_id);
    //set book price
    document.getElementById("book-price").value = String(book.price);
    //set book bonus
    document.getElementById("book-bonus").value = String(book.bonus);
    document.getElementById("book-image-file").value = "";
    setBookImagePreview(book.image ?? BOOK_DEFAULT_IMAGE);
    document.getElementById("book-description").value = book.description ?? "";
    document.getElementById("book-submit").textContent = "Save changes";
    //set book cancel class
    document.getElementById("book-cancel").classList.remove("hidden");
    //scroll to book form
    document.getElementById("book-form").scrollIntoView({ behavior: "smooth" });
}

//reset book form
function resetBookForm() {
    //set editing book id to null
    editingBookId = null;
    //reset book form
    document.getElementById("book-form")?.reset();
    //set book id to empty string
    document.getElementById("book-id").value = "";
    //set book price to 1
    document.getElementById("book-price").value = "1";
    //set book bonus to 1
    document.getElementById("book-bonus").value = "1";
    document.getElementById("book-image-file").value = "";
    setBookImagePreview(BOOK_DEFAULT_IMAGE);
    document.getElementById("book-submit").textContent = "Add book";
    document.getElementById("book-cancel").classList.add("hidden");
}

//save book
async function saveBook(event) {
    //prevent default
    event.preventDefault();

    const formData = new FormData();
    formData.append("bookId", editingBookId ? String(editingBookId) : "");
    formData.append(
        "title",
        document.getElementById("book-title").value.trim()
    );
    formData.append(
        "author",
        document.getElementById("book-author").value.trim()
    );
    formData.append(
        "isbn",
        document.getElementById("book-isbn").value.trim()
    );
    formData.append(
        "categoryId",
        document.getElementById("book-category").value
    );
    formData.append(
        "price",
        document.getElementById("book-price").value
    );
    formData.append(
        "bonus",
        document.getElementById("book-bonus").value
    );
    formData.append(
        "image",
        document.getElementById("book-image").value.trim()
    );
    formData.append(
        "description",
        document.getElementById("book-description").value.trim()
    );

    const imageFile =
        document.getElementById("book-image-file").files?.[0];

    if (imageFile) {
        formData.append("imageFile", imageFile);
    }

    //set endpoint
    const endpoint = editingBookId
        ? API.adminBooksUpdate
        : API.adminBooksCreate;

    try {
        //try to fetch endpoint
        const response = await fetch(endpoint, {
            method: "POST",
            body: formData
        });

        //get data
        const data = await response.json();

        //if response is not ok, alert error
        if (!response.ok) {
            alert(data.message ?? "Unable to save book");
            return;
        }

        //reset book form
        resetBookForm();
        //load books
        await loadBooks();
        alert(data.message ?? "Saved");

    } catch (error) {
        console.error(error);
        alert("Unable to connect to the server");
    }
}

//delete book
async function deleteBook(bookId) {
    //if user does not confirm, return
    if (!confirm(`Delete book #${bookId}?`)) {
        return;
    }

    try {
        //try to fetch endpoint
        const response = await fetch(API.adminBooksDelete, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ bookId })
        });

        //get data
        const data = await response.json();

        //if response is not ok, alert error
        if (!response.ok) {
            alert(data.message ?? "Unable to delete book");
            return;
        }

        //if editing book id is the same as book id, reset book form
        if (editingBookId === bookId) {
            resetBookForm();
        }

        //load books
        await loadBooks();

    } catch (error) {
        console.error(error);
        alert("Unable to connect to the server");
    }
}

//load users
async function loadUsers() {
    //try to fetch users
    const response = await fetch(API.adminUsersList);
    const data = await response.json();

    //if response is not ok, alert error
    if (!response.ok) {
        alert(data.message ?? "Unable to load users");
        return;
    }

    //get tbody element
    const tbody = document.querySelector("#users-table tbody");
    //if tbody is not found, return
    if (!tbody) return;
    //clear tbody
    tbody.replaceChildren();
    //loop through users

    (data.users ?? []).forEach(user => {
        const tr = document.createElement("tr");

        appendTextCell(tr, String(user.id));
        appendTextCell(
            tr,
            `${user.first_name} ${user.last_name}`
        );
        appendTextCell(tr, user.email ?? "");
        appendTextCell(tr, user.role ?? "");
        appendTextCell(tr, String(user.login_count));
        appendTextCell(tr, String(user.bonus));
        appendTextCell(tr, String(user.purchases_count));

        const actions = document.createElement("td");
        actions.className = "actions";

        const purchasesButton = document.createElement("button");
        purchasesButton.type = "button";
        purchasesButton.dataset.purchases = String(user.id);
        purchasesButton.textContent = "View purchases";
        actions.appendChild(purchasesButton);

        tr.appendChild(actions);
        tbody.appendChild(tr);
    });

    tbody.querySelectorAll("[data-purchases]").forEach(button => {
        button.addEventListener("click", () => {
            loadPurchases(Number(button.dataset.purchases));
        });
    });
}

//load purchases
async function loadPurchases(userId) {
    //get box element
    const box = document.getElementById("user-purchases");
    //get title element
    const title = document.getElementById("user-purchases-title");
    //get tbody element
    const tbody = document.querySelector("#purchases-table tbody");

    try {
        //try to fetch endpoint
        const response = await fetch(
            `${API.adminUsersPurchases}?user_id=${userId}`
        );
        //get data
        const data = await response.json();

        //if response is not ok, alert error
        if (!response.ok) {
            alert(data.message ?? "Unable to load purchases");
            return;
        }

        //get user
        const user = data.user;
        //set title
        title.textContent =
            `Purchases of ${user.first_name} ${user.last_name} (${user.email})`;

        //clear tbody
        tbody.replaceChildren();

        //if purchases is not an array or is empty, create no purchases row
        if (!data.purchases?.length) {
            //create no purchases row
            const tr = document.createElement("tr");
            //create cell
            const cell = document.createElement("td");
            //set cell colspan to 6
            cell.colSpan = 6;
            //set cell text content to "No purchases"
            cell.textContent = "No purchases";
            //append cell to row
            tr.appendChild(cell);
            //append row to tbody
            tbody.appendChild(tr);
            //return
            return;
        } else {
            //loop through purchases
            data.purchases.forEach(item => {
                //create row
                const tr = document.createElement("tr");
                //append text cell to row
                appendTextCell(tr, item.title ?? "");
                //append text cell to row
                appendTextCell(tr, item.author ?? "");
                //append text cell to row
                appendTextCell(tr, item.isbn ?? "—");
                appendTextCell(tr, Number(item.price).toFixed(2));
                //append text cell to row
                appendTextCell(tr, String(item.bonus));
                //append text cell to row
                appendTextCell(
                    tr,
                    formatDateTime(item.purchased_at)
                );
                //append row to tbody
                tbody.appendChild(tr);
            });
        }

        //remove hidden class from box
        box.classList.remove("hidden");
        //scroll to box
        box.scrollIntoView({ behavior: "smooth" });
        //return
        return;

    } catch (error) {
        console.error(error);
        alert("Unable to connect to the server");
    }
}
