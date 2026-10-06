// Utility functions for the front page
//set container message
function setContainerMessage(container, text) {
    container.replaceChildren();
    const message = document.createElement("p");
    message.textContent = text;
    container.appendChild(message);
}

//create book card element
function createBookCardElement(book, className) {
    const element = document.createElement("div");
    element.className = className;

    const bonus = Number(book.bonus ?? 1);
    const price = `$${book.price}`;
    const title = document.createElement("h3");
    title.append(
        String(book.title ?? "").toUpperCase(),
        document.createElement("br"),
        `By ${book.author ?? ""}`
    );
    element.appendChild(title);

    if (book.isbn) {
        const isbn = document.createElement("p");
        isbn.className = "book-isbn";
        isbn.textContent = `ISBN: ${book.isbn}`;
        element.appendChild(isbn);
    }

    const description = document.createElement("p");
    description.className = "book-description";
    description.textContent = book.description ?? "";
    element.appendChild(description);

    const bonusElement = document.createElement("p");
    bonusElement.className = "book-bonus";
    bonusElement.textContent =
        `+${bonus} ${bonus === 1 ? "bonus" : "bonuses"}`;
    element.appendChild(bonusElement);

    const priceElement = document.createElement("p");
    priceElement.className = "book-price";
    priceElement.textContent = price;
    element.appendChild(priceElement);

    if (typeof createBookRatingElement === "function") {
        element.appendChild(createBookRatingElement(book));
    }

    const buyButton = document.createElement("button");
    buyButton.type = "button";
    buyButton.className = "buy buy-before-login";
    buyButton.dataset.bookId = String(Number(book.id));
    buyButton.dataset.bookPrice = price;
    buyButton.textContent = "Buy";
    element.appendChild(buyButton);

    const ownButton = document.createElement("button");
    ownButton.type = "button";
    ownButton.className = "own";
    ownButton.disabled = true;
    ownButton.textContent = "Own";
    element.appendChild(ownButton);

    const image = document.createElement("img");
    const imageSrc = String(book.image ?? "").trim();
    image.src = imageSrc || BOOK_DEFAULT_IMAGE;
    image.alt = book.title ?? "";
    image.className = "img-books";
    image.addEventListener("error", function onImageError() {
        image.removeEventListener("error", onImageError);
        image.src = BOOK_DEFAULT_IMAGE;
    });
    element.appendChild(image);

    return element;
}

//format date time
function formatDateTime(value) {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return String(value ?? "");
    }

    return date.toLocaleString(undefined, {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit"
    });
}
