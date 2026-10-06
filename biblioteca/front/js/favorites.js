// Dynamic Favorites: books by season from DB.
// Buy button uses the same data-book-id flow as Search.

//load favorite books
//add event listeners to the radio buttons
document.addEventListener("DOMContentLoaded", function () {
    const radios = document.querySelectorAll(
        'input[type=radio][name="season"]'
    );

    radios.forEach(radio => {
        radio.addEventListener("change", () => {
            showSeasonBooks(radio.value);
        });
    });

    loadFavoriteBooks();
});
//load favorite books
async function loadFavoriteBooks() {
    const container = document.getElementById(
        "favorites-items"
    );

    if (!container) return;

    setContainerMessage(container, "Loading...");

    try {
        const response = await fetch(
            API.booksFavorites
        );

        const books = await response.json();

        if (!response.ok) {
            setContainerMessage(
                container,
                "Unable to load favorites"
            );
            console.error(books);
            return;
        }

        renderFavoriteBooks(books);

        const selectedSeason =
            document.querySelector(
                'input[type=radio][name="season"]:checked'
            )?.value ?? "winter";

        showSeasonBooks(selectedSeason);

        if (
            typeof currentUser !== "undefined" &&
            currentUser &&
            typeof updateOwnedBooksInterface === "function"
        ) {
            updateOwnedBooksInterface(currentUser);
        }

    } catch (error) {
        console.error(error);
        setContainerMessage(
            container,
            "Unable to connect to the server"
        );
    }
}

//render favorite books
function renderFavoriteBooks(books) {
    const container = document.getElementById(
        "favorites-items"
    );

    if (!container) return;

    container.replaceChildren();

    if (!Array.isArray(books) || books.length === 0) {
        setContainerMessage(container, "No books found");
        return;
    }

    books.forEach(book => {
        const season =
            String(book.season ?? "")
                .toLowerCase();

        container.appendChild(
            createBookCardElement(
                book,
                `favorites-items ${season} hidden`
            )
        );
    });
}

function showSeasonBooks(season) {
    const allBooks = document.querySelectorAll(
        "#favorites-items .favorites-items"
    );

    allBooks.forEach(book => {
        book.classList.add("hidden");
    });

    document
        .querySelectorAll(
            `#favorites-items .favorites-items.${season}`
        )
        .forEach(book => {
            book.classList.remove("hidden");
        });
}
