<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Model.php';

class Review extends Model implements JsonSerializable
{
    protected ?int $id = null;
    protected ?int $user_id = null;
    protected ?int $book_id = null;
    protected int $rating = 0;
    protected ?float $average_rating = null;
    protected int $reviews_count = 0;

    private PDO $db;
    //Constructor to set database connection
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    //Convert review to array
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'book_id' => $this->book_id,
            'rating' => $this->rating,
            'average_rating' => $this->average_rating,
            'reviews_count' => $this->reviews_count
        ];
    }

    //Convert review to JSON
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    //Save review
    public function save(
        ?int $userId = null,
        ?int $bookId = null,
        ?int $rating = null
    ): void {
        $this->user_id = $userId ?? $this->user_id;
        $this->book_id = $bookId ?? $this->book_id;
        $this->rating = $rating ?? $this->rating;

        $stmt = $this->db->prepare(
            'INSERT INTO reviews (user_id, book_id, rating)
             VALUES (:user_id, :book_id, :rating)
             ON DUPLICATE KEY UPDATE
                rating = VALUES(rating),
                updated_at = CURRENT_TIMESTAMP'
        );

        $stmt->execute([
            'user_id' => $this->user_id,
            'book_id' => $this->book_id,
            'rating' => $this->rating
        ]);
    }

    //Get book stats
    public function getBookStats(?int $bookId = null): self
    {
        $targetBookId = $bookId ?? $this->book_id;

        $stmt = $this->db->prepare(
            'SELECT
                ROUND(AVG(rating), 1) AS average_rating,
                COUNT(*) AS reviews_count
             FROM reviews
             WHERE book_id = :book_id'
        );

        $stmt->execute(['book_id' => $targetBookId]);
        $stats = $stmt->fetch() ?: [];

        //Create new review
        $review = new self($this->db);
        $review->book_id = $targetBookId;
        $review->average_rating = isset($stats['average_rating']) && $stats['average_rating'] !== null
            ? (float) $stats['average_rating']
            : null;
        $review->reviews_count = (int) ($stats['reviews_count'] ?? 0);

        return $review;
    }
}
