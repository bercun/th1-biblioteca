// Search books by title or author

// Add event listeners to search button and input
document.addEventListener("DOMContentLoaded", function () {
    const searchButton =
        document.getElementById("search-button");

    const searchInput =
        document.getElementById("search-input");

    searchButton?.addEventListener(
        "click",
        searchBooks
    );

    searchInput?.addEventListener(
        "keydown",
        function (event) {
            if (event.key === "Enter") {
                event.preventDefault();
                searchBooks();
            }
        }
    );
});

// Render search results
function renderSearchResults(books) {
    const container =
        document.getElementById(
            "search-results"
        );

    // If container element is not found, return
    if (!container) return;
    // clear container
    container.replaceChildren();

    // If books is not an array or is empty, return
    if (
        !Array.isArray(books) ||
        books.length === 0
    ) {
        setContainerMessage(container, "No books found");
        return;
    }

    books.forEach(book => {
        container.appendChild(
            createBookCardElement(
                book,
                "favorites-items search-book"
            )
        );
    });

    // If current user is defined and has books, add owned books to container
    if (
        typeof currentUser !== "undefined" &&
        currentUser &&
        Array.isArray(currentUser.books)
    ) {

        // Create set of owned book IDs
        const ownedIds =
            new Set(
                currentUser.books
                    .filter(
                        book =>
                            typeof book === "object" &&
                            book.id != null
                    )
                    .map(
                        book =>
                            String(book.id)
                    )
            );

        // Get all buy buttons
        container
            .querySelectorAll(
                ".buy-before-login[data-book-id]"
            )
            .forEach(button => {

                // If book is not owned, return
                if (
                    !ownedIds.has(
                        String(
                            button.dataset.bookId
                        )
                    )
                ) {
                    return;
                }

                // Get card element
                const card =
                    button.closest(
                        ".favorites-items"
                    );

                // Get own button
                const own =
                    card?.querySelector(
                        ".own"
                    );

                // Hide buy button
                button.style.display =
                    "none";

                // If own button exists, show it
                if (own) {
                    own.style.display =
                        "block";
                }
            });
    }
}

// Search books
async function searchBooks() {
    // Get input element
    const input =
        document.getElementById(
            "search-input"
        );

    // Get container element
    const container =
        document.getElementById(
            "search-results"
        );

    // If input or container element is not found, return
    if (
        !input ||
        !container
    ) {
        return;
    }

    // Get query
    const query =
        input.value.trim();

    if (query === "") {
        container.replaceChildren();
        return;
    }

    setContainerMessage(container, "Searching...");

    try {
        const response =
            await fetch(
                `${API.booksSearch}?q=${encodeURIComponent(query)}`
            );

        const data =
            await response.json();

        if (!response.ok) {
            setContainerMessage(container, "Search error");
            console.error(data);
            return;
        }

        renderSearchResults(data);

    } catch (error) {
        console.error(error);
        setContainerMessage(
            container,
            "Unable to connect to the server"
        );
    }
}
