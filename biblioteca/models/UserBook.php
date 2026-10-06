<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Model.php';

class UserBook extends Model implements JsonSerializable
{
    protected ?int $user_id = null;
    protected ?int $book_id = null;
    protected ?string $purchased_at = null;

    // Joined book fields for owned/purchase lists
    protected ?int $id = null;
    protected string $title = '';
    protected string $author = '';
    protected ?string $isbn = null;
    protected float $price = 0.0;
    protected int $bonus = 0;
    protected ?int $user_rating = null;

    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    //Convert user book to array
    public function toArray(): array
    {
        return [
            'id' => $this->id ?? $this->book_id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'price' => $this->price,
            'bonus' => $this->bonus,
            'purchased_at' => $this->purchased_at,
            'user_rating' => $this->user_rating
        ];
    }

    //Convert user book to JSON
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    //Check if user owns book
    public function userOwnsBook(?int $userId = null, ?int $bookId = null): bool
    {
        $ownerId = $userId ?? $this->user_id;
        $targetBookId = $bookId ?? $this->book_id;

        $stmt = $this->db->prepare(
            'SELECT user_id
             FROM user_books
             WHERE user_id = :user_id
               AND book_id = :book_id'
        );

        $stmt->execute([
            'user_id' => $ownerId,
            'book_id' => $targetBookId
        ]);

        return (bool) $stmt->fetch();
    }

    //Purchase book
    public function purchase(?int $userId = null, ?int $bookId = null): string
    {
        $this->user_id = $userId ?? $this->user_id;
        $this->book_id = $bookId ?? $this->book_id;

        $stmt = $this->db->prepare(
            'INSERT INTO user_books
                (user_id, book_id, purchased_at)
             VALUES
                (:user_id, :book_id, NOW())'
        );

        $stmt->execute([
            'user_id' => $this->user_id,
            'book_id' => $this->book_id
        ]);

        $stmt = $this->db->prepare(
            'SELECT purchased_at
             FROM user_books
             WHERE user_id = :user_id
               AND book_id = :book_id'
        );

        $stmt->execute([
            'user_id' => $this->user_id,
            'book_id' => $this->book_id
        ]);

        $this->purchased_at = (string) $stmt->fetchColumn();

        return $this->purchased_at;
    }

    //Count books by user
    public function countByUser(?int $userId = null): int
    {
        $ownerId = $userId ?? $this->user_id;

        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM user_books
             WHERE user_id = :user_id'
        );

        $stmt->execute(['user_id' => $ownerId]);

        return (int) $stmt->fetchColumn();
    }

    //List owned books by user
    /** @return self[] */
    public function listOwnedByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                books.id,
                books.title,
                books.author,
                books.bonus,
                user_books.purchased_at,
                reviews.rating AS user_rating
             FROM user_books
             INNER JOIN books
                ON books.id = user_books.book_id
             LEFT JOIN reviews
                ON reviews.book_id = user_books.book_id
               AND reviews.user_id = user_books.user_id
             WHERE user_books.user_id = :user_id
             ORDER BY user_books.purchased_at DESC, books.title'
        );

        $stmt->execute(['user_id' => $userId]);

        return array_map(
            function (array $row) use ($userId) {
                $item = new self($this->db);
                $item->user_id = $userId;
                $item->id = (int) $row['id'];
                $item->book_id = (int) $row['id'];
                $item->title = (string) $row['title'];
                $item->author = (string) $row['author'];
                $item->bonus = (int) $row['bonus'];
                $item->purchased_at = $row['purchased_at'] ?? null;
                $item->user_rating = $row['user_rating'] !== null
                    ? (int) $row['user_rating']
                    : null;

                return $item;
            },
            $stmt->fetchAll()
        );
    }

    //List purchases for admin
    /** @return self[] */
    public function listPurchasesForAdmin(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                b.id,
                b.title,
                b.author,
                b.isbn,
                b.price,
                b.bonus,
                ub.purchased_at
             FROM user_books ub
             INNER JOIN books b
                ON b.id = ub.book_id
             WHERE ub.user_id = :user_id
             ORDER BY ub.purchased_at DESC'
        );

        $stmt->execute(['user_id' => $userId]);

        return array_map(
            function (array $row) use ($userId) {
                $item = new self($this->db);
                $item->user_id = $userId;
                $item->id = (int) $row['id'];
                $item->book_id = (int) $row['id'];
                $item->title = (string) $row['title'];
                $item->author = (string) $row['author'];
                $item->isbn = $row['isbn'] ?? null;
                $item->price = (float) $row['price'];
                $item->bonus = (int) $row['bonus'];
                $item->purchased_at = $row['purchased_at'] ?? null;

                return $item;
            },
            $stmt->fetchAll()
        );
    }
}
