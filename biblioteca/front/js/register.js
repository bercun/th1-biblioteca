// PHP/MySQL authentication and library logic

// Shared auth / purchase state (used by register, logout, favorites, search, reviews)
let currentUser = null;
let selectedBookId = null;
let selectedBookCard = null;

// Normalize user data
function normalizeUser(user) {
    if (!user) return null;

    return {
        id: Number(user.id),
        firstName: user.firstName ?? user.first_name ?? "",
        lastName: user.lastName ?? user.last_name ?? "",
        email: user.email ?? "",
        loginCount: Number(user.loginCount ?? user.login_count ?? 0),
        booksCount: Number(user.booksCount ?? user.books_count ?? 0),
        bonus: Number(user.bonus ?? 0),
        role: user.role ?? "user",
        books: Array.isArray(user.books) ? user.books : []
    };
}

// Read JSON response
async function readJsonResponse(response) {
    try {
        return await response.json();
    } catch {
        throw new Error("Server returned an invalid response");
    }
}

//login modal
function showLoginModal() {
    document.getElementById("overlay-buy")?.classList.remove("overlay-open");

    const buyCardModal = document.getElementById("modal-buy-book");

    if (buyCardModal) {
        buyCardModal.style.display = "none";
    }

    document.getElementById("overlay")?.classList.add("overlay-open");

    const loginModal = document.getElementById("modal-login");

    if (loginModal) {
        loginModal.style.display = "flex";
    }
}

//buy book modal
function showBuyBookModal(priceText) {
    document.getElementById("overlay-buy")?.classList.add("overlay-open");

    const modal = document.getElementById("modal-buy-book");

    if (modal) {
        modal.style.display = "block";
    }

    const priceElement = document.getElementById("price");

    if (priceElement) {
        priceElement.innerText =
            priceText && String(priceText).trim() !== ""
                ? String(priceText)
                : "$1";
    }
}

// Register
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("form-modal-register");

    if (!form) return;

    form.addEventListener("submit", async function (event) {
        event.preventDefault();

        const firstName =
            document.querySelector('[name="first"]')?.value.trim() ?? "";

        const lastName =
            document.querySelector('[name="last"]')?.value.trim() ?? "";

        const email =
            document.querySelector('[name="email-register"]')?.value.trim() ?? "";

        const password =
            document.querySelector('[name="password-register"]')?.value ?? "";

        try {
            const response = await fetch(
                API.register,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        firstName,
                        lastName,
                        email,
                        password
                    })
                }
            );

            const data = await readJsonResponse(response);

            if (!response.ok) {
                alert(data.message ?? "Registration error");
                return;
            }

            currentUser = normalizeUser(data.user);

            handleLogin(currentUser);

            form.reset();

            const register = document.getElementById("modal-register");

            if (register) {
                register.style.display = "none";
            }

            document
                .getElementById("overlay")
                ?.classList.remove("overlay-open");

        } catch (error) {
            console.error(error);
            alert("Unable to connect to the server");
        }
    });
});

// Login
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("form-modal-login");

    if (!form) return;

    form.addEventListener("submit", async function (event) {
        event.preventDefault();

        const email =
            document.querySelector('[name="email-login"]')?.value.trim() ?? "";

        const password =
            document.querySelector('[name="password-login"]')?.value ?? "";

        try {
            const response = await fetch(
                API.login,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        email,
                        password
                    })
                }
            );

            const data = await readJsonResponse(response);

            if (!response.ok) {
                alert(data.message ?? "Invalid credentials");
                return;
            }

            const user = await loadCurrentUser();

            if (!user) {
                alert("Unable to load user data");
                return;
            }

            form.reset();

            const login = document.getElementById("modal-login");

            if (login) {
                login.style.display = "none";
            }

            document
                .getElementById("overlay")
                ?.classList.remove("overlay-open");

        } catch (error) {
            console.error(error);
            alert("Unable to connect to the server");
        }
    });
});

// Restore PHP session after page reload
document.addEventListener("DOMContentLoaded", async function () {
    try {
        await loadCurrentUser();
    } catch (error) {
        console.error("Unable to restore session:", error);
    }
});

async function loadCurrentUser() {
    const response = await fetch(
        API.currentUser,
        {
            method: "GET",
            cache: "no-store"
        }
    );

    const data = await readJsonResponse(response);

    if (!response.ok || !data) {
        currentUser = null;
        return null;
    }

    currentUser = normalizeUser(data);

    handleLogin(currentUser);

    return currentUser;
}

// Logged-in interface
function handleLogin(user) {
    if (!user) return;

    const buttonUser = document.getElementById("button-user");

    if (buttonUser) {
        buttonUser.style.display = "none";
    }

    const dropmenu = document.getElementById("dropmenu");

    if (dropmenu) {
        dropmenu.style.display = "flex";
    }

    const fullName =
        `${user.firstName} ${user.lastName}`.trim();

    const username =
        document.getElementById("username");

    if (username) {
        username.value = fullName;
    }

    const profileName =
        document.getElementById("username-profile");

    if (profileName) {
        profileName.innerText = fullName;
    }

    const initials =
        (user.firstName[0] ?? "") +
        (user.lastName[0] ?? "");

    const avatar =
        document.getElementById("avatar");

    if (avatar) {
        avatar.innerText = initials;
    }

    const buttonUserActive =
        document.getElementById("button-user-active");

    if (buttonUserActive) {
        buttonUserActive.innerText = initials;
    }

    const visitCounterProfile =
        document.getElementById(
            "visit-counter-profile"
        );

    if (visitCounterProfile) {
        visitCounterProfile.innerText =
            user.loginCount;
    }

    const visitCounterCard =
        document.getElementById(
            "visit-counter-card"
        );

    if (visitCounterCard) {
        visitCounterCard.innerText =
            user.loginCount;
    }

    document
    .querySelectorAll(".bonuses")
    .forEach(element => {
        element.innerText = user.bonus;
    });

    const adminLink = document.getElementById(
        "user-menu-link-admin"
    );

    if (adminLink) {
        const isAdmin = user.role === "admin";
        adminLink.href = ADMIN_URL;
        adminLink.classList.toggle("hidden", !isAdmin);
        document
            .getElementById("user-active")
            ?.classList.toggle("has-admin-link", isAdmin);
    }

    updateOwnedBooksInterface(user);
}

function updateOwnedBooksInterface(user) {
    const myBooks =
        document.getElementById("my-books");

    if (myBooks) {
        myBooks.replaceChildren();
    }

    const books =
        Array.isArray(user.books)
            ? user.books
            : [];

    const booksCount =
        books.length > 0
            ? books.length
            : Number(user.booksCount ?? 0);

    document
        .querySelectorAll(".books-number")
        .forEach(element => {
            element.innerText = booksCount;
        });

    if (
        myBooks &&
        books.length > 0
    ) {
        books.forEach(book => {
            myBooks.appendChild(
                createOwnedBookListItem(book)
            );
        });
    }

    const ownedIds =
        new Set(
            books
                .filter(
                    book =>
                        typeof book === "object" &&
                        book.id != null
                )
                .map(
                    book => String(book.id)
                )
        );

    if (ownedIds.size > 0) {

        document
            .querySelectorAll(
                ".buy-before-login[data-book-id]"
            )
            .forEach(button => {

                if (
                    !ownedIds.has(
                        String(button.dataset.bookId)
                    )
                ) {
                    return;
                }

                markBookAsOwned(
                    button.closest(".favorites-items")
                );
            });
    }
}

// Buy a book
document.addEventListener("DOMContentLoaded", function () {
    const form =
        document.getElementById(
            "form-modal-buy-book"
        );

    if (!form) return;

    form.addEventListener(
        "submit",
        async function (event) {

            event.preventDefault();

            if (!selectedBookId) {

                    alert(
                        "No book selected"
                    );

                    return;
                }

            try {
                const success = await buyBook(
                    selectedBookId,
                    selectedBookCard
                );

                if (!success) {
                    return;
                }

                alert("Purchase completed successfully!");

                document
                    .getElementById(
                        "overlay-buy"
                    )
                    ?.classList.remove(
                        "overlay-open"
                    );

                const modal =
                    document.getElementById(
                        "modal-buy-book"
                    );

                if (modal) {
                    modal.style.display = "none";
                }

                form.reset();
                selectedBookId = null;
                selectedBookCard = null;

            } catch (error) {
                alert(
                    "Unable to complete purchase"
                );
            }
        }
    );
});


// for Buy button:
// works for Favorites and Search results
document.addEventListener("DOMContentLoaded", function () {
    document.addEventListener(
        "click",
        async function (event) {

            const button =
                event.target.closest(
                    ".buy-before-login"
                );

            if (!button) return;

            event.preventDefault();
            
            const card = button.closest(".favorites-items");

            if (!card) {return;
            }

            try {

                if (!currentUser) {
                    showLoginModal();
                    return;
                }

                let bookId =
                    button.dataset.bookId;

                if (!bookId) {
                    bookId =
                        await findBookIdForFavoriteCard(
                            card
                        );
                }

                if (!bookId) {
                    alert(
                        "Book was not found in the database"
                    );
                    return;
                }

                const alreadyOwned =
                    currentUser.books?.some(
                        book =>
                            String(book.id) ===
                            String(bookId)
                    );

                if (alreadyOwned) {

                    markBookAsOwned(card);

                    alert(
                        "You already own this book"
                    );

                    return;
                }

                selectedBookId =
                    Number(bookId);

                selectedBookCard =
                    card;

                const priceText =
                    button.dataset.bookPrice ||
                    card.querySelector(".book-price")
                        ?.innerText ||
                    "$1";

                showBuyBookModal(priceText);

            } catch (error) {
                console.error(error);

                alert(
                    "Unable to prepare purchase"
                );

            } finally {
                button.disabled = false;
            }
        }
    );
});

async function findBookIdForFavoriteCard(card) {
    const heading =
        card.querySelector("h3");

    if (!heading) {
        return null;
    }

    const lines =
        heading.innerText
            .split("\n")
            .map(
                line => line.trim()
            )
            .filter(Boolean);

    const title =
        lines[0] ?? "";

    const author =
        (lines[1] ?? "")
            .replace(
                /^By\s+/i,
                ""
            )
            .trim();

    if (!title) {
        return null;
    }

    const response =
        await fetch(
            `${API.booksSearch}?q=${encodeURIComponent(title)}`
        );

    const books =
        await readJsonResponse(response);

    if (
        !response.ok ||
        !Array.isArray(books)
    ) {
        return null;
    }

    const normalize = value =>
        value
            .toLowerCase()
            .replace(/\s+/g, " ")
            .trim();

    const exact =
        books.find(book =>
            normalize(book.title) ===
                normalize(title) &&
            (
                author === "" ||
                normalize(book.author) ===
                    normalize(author)
            )
        );

    return exact?.id ?? null;
}

async function buyBook(
    bookId,
    card
) {
    const response =
        await fetch(
            API.booksBuy,
            {
                method: "POST",

                headers: {
                    "Content-Type":
                        "application/json"
                },

                body: JSON.stringify({
                    bookId: Number(bookId)
                })
            }
        );

    const data =
        await readJsonResponse(response);

    if (response.status === 401) {
        showLoginModal();
        return false;
    }

    if (response.status === 409) {
        markBookAsOwned(card);
        alert(
            data.message ??
            "You already own this book"
        );
        return false;
    }

    if (!response.ok) {
        alert(
            data.message ??
            "Unable to add book"
        );
        return false;
    }

    markBookAsOwned(card);

    document
        .querySelectorAll(
            `.buy-before-login[data-book-id="${bookId}"]`
        )
        .forEach(button => {
            markBookAsOwned(
                button.closest(".favorites-items")
            );
        });

    document
        .querySelectorAll(".books-number")
        .forEach(element => {
            element.innerText =
                data.booksCount;
        });

    document
        .querySelectorAll(".bonuses")
        .forEach(element => {
            element.innerText = data.bonus;
        });

    const myBooks =
        document.getElementById(
            "my-books"
        );

    if (
        myBooks &&
        data.book
    ) {
        const ownedBook = {
            ...data.book,
            user_rating: null
        };

        myBooks.prepend(
            createOwnedBookListItem(ownedBook)
        );
    }

    if (
        currentUser && data.book
    ) {
        currentUser.booksCount =
            data.booksCount;
        
        currentUser.bonus = data.bonus;

        if (
            !Array.isArray(
                currentUser.books
            )
        ) {
            currentUser.books = [];
        }

        const alreadyPresent =
            currentUser.books.some(
                book =>
                    typeof book === "object" &&
                    String(book.id) ===
                        String(data.book.id)
            );

        if (!alreadyPresent) {
            currentUser.books.push({
                ...data.book,
                user_rating: null
            });
        }
    }

    return true;
}

function markBookAsOwned(card) {
    if (!card) return;

    const buyButton =
        card.querySelector(".buy");

    const ownButton =
        card.querySelector(".own");

    if (buyButton) {
        buyButton.style.display =
            "none";
    }

    if (ownButton) {
        ownButton.style.display =
            "block";
    }
}

function createOwnedBookListItem(book) {
    const li = document.createElement("li");
    li.className = "my-book-item";

    if (typeof book === "string") {
        li.textContent = book.replace("\n", "");
        return li;
    }

    const bookId = Number(book.id);
    li.dataset.bookId = String(bookId);

    const info = document.createElement("div");
    info.className = "my-book-info";

    const title = document.createElement("span");
    title.className = "my-book-title";
    title.textContent =
        `${book.title ?? ""}, ${book.author ?? ""}`
            .replace(/^, |, $/g, "");

    info.appendChild(title);

    const purchasedAt =
        book.purchased_at ?? book.purchasedAt ?? null;

    if (purchasedAt) {
        const date = document.createElement("span");
        date.className = "my-book-purchased-at";
        date.textContent = formatDateTime(purchasedAt);
        info.appendChild(date);
    }

    li.appendChild(info);

    if (
        bookId > 0 &&
        typeof createProfileStarRating === "function"
    ) {
        li.appendChild(
            createProfileStarRating(
                bookId,
                book.user_rating ?? book.userRating ?? null
            )
        );
    }

    return li;
}
