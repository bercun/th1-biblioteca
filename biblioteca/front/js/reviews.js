// Book reviews: profile star rating + average on cards

// Format average rating
function formatAverageRating(value) {
    // Get amount
    const amount = Number(value);

    // If amount is not a finite number or is less than or equal to 0, return null
    if (!Number.isFinite(amount) || amount <= 0) {
        return null;
    }

    // Return amount rounded to 1 decimal place
    return amount.toFixed(1);
}

// Create book rating element
function createBookRatingElement(book) {
    const element = document.createElement("p");
    element.className = "book-rating";
    element.dataset.bookId = String(Number(book.id));

    const average = formatAverageRating(
        book.average_rating ?? book.averageRating
    );

    // Get reviews count
    const count = Number(
        book.reviews_count ?? book.reviewsCount ?? 0
    );

    // If average rating is not set, return "★ No rating"
    if (!average) {
        element.textContent = "★ No rating";
        return element;
    }

    // Get reviews label
    const reviewsLabel =
        count === 1 ? "review" : "reviews";

    element.append(`★ ${average}`);

    const countElement = document.createElement("span");
    countElement.className = "book-rating-count";
    countElement.textContent =
        ` (${count} ${reviewsLabel})`;
    element.appendChild(countElement);

    return element;
}

// Create profile star rating
function createProfileStarRating(bookId, userRating) {
    // Create wrapper element
    const wrapper = document.createElement("div");
    wrapper.className = "profile-star-rating";
    wrapper.dataset.bookId = String(bookId);

    // Get current rating
    const current = Number(userRating) || 0;

    // Loop through values 1 to 5
    for (let value = 1; value <= 5; value += 1) {
        // Create button element
        const button = document.createElement("button");
        // Set button type
        button.type = "button";
        // Set button class
        button.className = "profile-star";
        // Set button data value
        button.dataset.value = String(value);
        // Set button aria label
        button.setAttribute(
            "aria-label",
            `Rate ${value} star${value === 1 ? "" : "s"}`
        );
        // Set button text content
        button.textContent = "★";

        // If value is less than or equal to current, add filled class
        if (value <= current) {
            button.classList.add("filled");
        }

        // Append button to wrapper
        wrapper.appendChild(button);
    }

    // Return wrapper
    return wrapper;
}

// Paint profile stars
function paintProfileStars(wrapper, rating) {
    // If wrapper is not found, return
    if (!wrapper) return;

    // Get current rating
    const current = Number(rating) || 0;

    // Loop through profile stars
    wrapper
        .querySelectorAll(".profile-star")
        .forEach(star => {
            // Get star value
            const value = Number(star.dataset.value);

            // Toggle filled class
            star.classList.toggle(
                "filled",
                value <= current
            );
        });
}

// Update book cards average
function updateBookCardsAverage(bookId, averageRating, reviewsCount) {
    // Get average rating
    const average = formatAverageRating(averageRating);
    // Get reviews count
    const count = Number(reviewsCount ?? 0);

    // Loop through book cards
    document
        .querySelectorAll(
            `.book-rating[data-book-id="${bookId}"]`
        )
        .forEach(element => {
            element.replaceChildren();

            // If average rating is not set, return "★ No rating"
            if (!average) {
                element.textContent = "★ No rating";
                return;
            }

            // Get reviews label
            const reviewsLabel =
                count === 1 ? "review" : "reviews";

            element.append(`★ ${average}`);

            // Create count element
            const countElement =
                document.createElement("span");
            countElement.className =
                "book-rating-count";
            countElement.textContent =
                ` (${count} ${reviewsLabel})`;
            element.appendChild(countElement);
        });
}

// Save book review
async function saveBookReview(bookId, rating) {
    // Fetch response
    const response = await fetch(
        API.booksReview,
        {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                bookId: Number(bookId),
                rating: Number(rating)
            })
        }
    );

    // Read JSON response
    const data = await readJsonResponse(response);
    // If response is not ok, throw error

    if (!response.ok) {
        throw new Error(
            data.message ?? "Unable to save review"
        );
    }

    return data;
}

// Add event listeners to my books
document.addEventListener("DOMContentLoaded", function () {
    // Get my books element
    const myBooks = document.getElementById("my-books");

    // If my books element is not found, return
    if (!myBooks) return;

    // Add click event listener to my books
    myBooks.addEventListener("click", async function (event) {
        // Get star element
        const star = event.target.closest(".profile-star");

        // If star element is not found, return
        if (!star) return;

        // Get wrapper element
        const wrapper = star.closest(".profile-star-rating");
        // Get book id
        const bookId = wrapper?.dataset.bookId;
        // Get rating
        const rating = Number(star.dataset.value);
        // If book id or rating is not valid, return

        if (!bookId || rating < 1 || rating > 5) {
            // If book id or rating is not valid, return
            return;
        }

        // If current user is not found, show login modal
        if (!currentUser) {
            showLoginModal();
            return;
        }

        try {
            // Save book review
            const data = await saveBookReview(
                bookId,
                rating
            );

            // Paint profile stars
            paintProfileStars(wrapper, data.rating);

            // If current user has books, update book
            if (Array.isArray(currentUser.books)) {
                // Find book
                const book = currentUser.books.find(
                    item =>
                        typeof item === "object" &&
                        String(item.id) === String(bookId)
                );

                // If book is found, update book
                if (book) {
                    // Update book user rating
                    book.user_rating = data.rating;
                    book.userRating = data.rating;
                }
            }

            updateBookCardsAverage(
                data.bookId,
                data.averageRating,
                data.reviewsCount
            );

        } catch (error) {
            console.error(error);
            alert(
                error.message ??
                "Unable to save review"
            );
        }
    });
// Add mouseover event listener to my books
    myBooks.addEventListener("mouseover", function (event) {
        // Get star element
        const star = event.target.closest(".profile-star");
        // If star element is not found, return
        if (!star) return;

        // Get wrapper element
        const wrapper = star.closest(".profile-star-rating");
        // Get hover value
        const hoverValue = Number(star.dataset.value);

        // Loop through profile stars
        wrapper
            ?.querySelectorAll(".profile-star")
            .forEach(item => {
                // Get star value
                const value = Number(item.dataset.value);
                // Toggle hover class
                item.classList.toggle(
                    "hover",
                    value <= hoverValue
                );
            });
    });

// Add mouseout event listener to my books
    myBooks.addEventListener("mouseout", function (event) {
        // Get wrapper element
        const wrapper = event.target.closest(
            ".profile-star-rating"
        );

        // If wrapper element is not found, return
        if (!wrapper) return;
        
        // Loop through profile stars

        wrapper
            .querySelectorAll(".profile-star")
            .forEach(item => {
                // Remove hover class
                item.classList.remove("hover");
            });
    });
});
