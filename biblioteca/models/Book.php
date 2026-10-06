<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Model.php';

class Book extends Model implements JsonSerializable
{
    public const DEFAULT_IMAGE = '../front/assets/images/bookDefault.png';

    protected ?int $id = null;
    protected string $title = '';
    protected string $author = '';
    protected ?string $isbn = null;
    protected string $description = '';
    protected ?int $category_id = null;
    protected ?string $season = null;
    protected string $image = self::DEFAULT_IMAGE;
    protected int $bonus = 1;
    protected ?float $price = 1.00;
    protected ?float $average_rating = null;
    protected int $reviews_count = 0;

    private PDO $db;
    //Constructor to set database connection
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    //Resolve image path
    public static function resolveImage(?string $image): string
    {
        $image = trim((string) $image);

        return $image !== '' ? $image : self::DEFAULT_IMAGE;
    }

    //Convert book to array
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'season' => $this->season,
            'image' => self::resolveImage($this->image),
            'bonus' => $this->bonus,
            'price' => $this->price,
            'average_rating' => $this->average_rating,
            'reviews_count' => $this->reviews_count
        ];
    }

    //Convert book to JSON
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    //Find book by ID
    public function findById(int $id): ?self
    {
        $stmt = $this->db->prepare(
            'SELECT
                id,
                title,
                author,
                isbn,
                description,
                category_id,
                image,
                bonus,
                price
             FROM books
             WHERE id = :id'
        );

        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->mapRow($row) : null;
    }

    //Find book for purchase
    public function findForPurchase(int $id): ?self
    {
        $stmt = $this->db->prepare(
            'SELECT
                id,
                title,
                author,
                bonus,
                price
             FROM books
             WHERE id = :id'
        );

        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->mapRow($row) : null;
    }

    //Search for books
    /** @return self[] */
    public function search(string $query): array
    {
        $isbnDigits = preg_replace('/[^0-9Xx]/', '', $query);

        $sql = "
            SELECT
                b.id,
                b.title,
                b.author,
                b.isbn,
                b.description,
                b.image,
                b.bonus,
                b.price,
                ROUND(AVG(r.rating), 1) AS average_rating,
                COUNT(r.id) AS reviews_count
            FROM books b
            LEFT JOIN reviews r
                ON r.book_id = b.id
            WHERE b.title LIKE :query
               OR b.author LIKE :query
               OR b.isbn LIKE :query
               OR (
                    :isbn_digits <> ''
                    AND REPLACE(REPLACE(b.isbn, '-', ''), ' ', '') LIKE :isbn_like
               )
            GROUP BY
                b.id,
                b.title,
                b.author,
                b.isbn,
                b.description,
                b.image,
                b.bonus,
                b.price
            ORDER BY b.title
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'query' => '%' . $query . '%',
            'isbn_digits' => $isbnDigits,
            'isbn_like' => '%' . $isbnDigits . '%'
        ]);

        return $this->mapRows($stmt->fetchAll());
    }

    //List books by season
    /** @return self[] */
    public function listBySeason(?string $season = null): array
    {
        $sql = "
            SELECT
                b.id,
                b.title,
                b.author,
                b.isbn,
                b.description,
                c.season,
                b.image,
                b.bonus,
                b.price,
                ROUND(AVG(r.rating), 1) AS average_rating,
                COUNT(r.id) AS reviews_count
            FROM books b
            INNER JOIN books_category c
                ON c.id = b.category_id
            LEFT JOIN reviews r
                ON r.book_id = b.id
        ";

        $params = [];

        if ($season !== null && $season !== '') {
            $sql .= ' WHERE c.season = :season';
            $params['season'] = $season;
        }

        $sql .= '
            GROUP BY
                b.id,
                b.title,
                b.author,
                b.isbn,
                b.description,
                c.season,
                b.image,
                b.bonus,
                b.price
            ORDER BY b.id
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $this->mapRows($stmt->fetchAll());
    }

    //List books for admin
    /** @return self[] */
    public function listForAdmin(): array
    {
        $sql = "
            SELECT
                b.id,
                b.title,
                b.author,
                b.isbn,
                b.description,
                b.category_id,
                c.season,
                b.image,
                b.bonus,
                b.price
            FROM books b
            INNER JOIN books_category c
                ON c.id = b.category_id
            ORDER BY b.id
        ";

        return $this->mapRows($this->db->query($sql)->fetchAll());
    }

    //Create book
    public function create(): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO books
                (title, author, isbn, description, category_id, image, bonus, price)
             VALUES
                (:title, :author, :isbn, :description, :category_id, :image, :bonus, :price)'
        );

        $this->image = self::resolveImage($this->image);

        $stmt->execute([
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'image' => $this->image,
            'bonus' => $this->bonus,
            'price' => $this->price
        ]);

        $this->id = (int) $this->db->lastInsertId();

        return $this->id;
    }

    //Update book
    public function update(): void
    {
        if ($this->id === null) {
            throw new RuntimeException('Book id is required for update');
        }

        $stmt = $this->db->prepare(
            'UPDATE books
             SET
                title = :title,
                author = :author,
                isbn = :isbn,
                description = :description,
                category_id = :category_id,
                image = :image,
                bonus = :bonus,
                price = :price
             WHERE id = :id'
        );

        $this->image = self::resolveImage($this->image);

        $stmt->execute([
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'image' => $this->image,
            'bonus' => $this->bonus,
            'price' => $this->price,
            'id' => $this->id
        ]);
    }

    //Delete book
    public function delete(?int $id = null): bool
    {
        $bookId = $id ?? $this->id;

        if ($bookId === null) {
            return false;
        }

        $stmt = $this->db->prepare(
            'DELETE FROM books WHERE id = :id'
        );

        $stmt->execute(['id' => $bookId]);

        return $stmt->rowCount() > 0;
    }

    //Map rows to books
    /** @param array<int, array<string, mixed>> $rows */
    private function mapRows(array $rows): array
    {
        return array_map(
            fn(array $row) => $this->mapRow($row),
            $rows
        );
    }

    private function mapRow(array $row): self
    {
        $book = new self($this->db);
        $book->id = isset($row['id']) ? (int) $row['id'] : null;
        $book->title = (string) ($row['title'] ?? '');
        $book->author = (string) ($row['author'] ?? '');
        $book->isbn = $row['isbn'] ?? null;
        $book->description = (string) ($row['description'] ?? '');
        $book->category_id = isset($row['category_id'])
            ? (int) $row['category_id']
            : null;
        $book->season = $row['season'] ?? null;
        $book->image = self::resolveImage($row['image'] ?? null);
        $book->bonus = (int) ($row['bonus'] ?? 1);
        $book->price = (float) ($row['price'] ?? 1);
        $book->reviews_count = (int) ($row['reviews_count'] ?? 0);
        $book->average_rating = isset($row['average_rating']) && $row['average_rating'] !== null
            ? (float) $row['average_rating']
            : null;

        return $book;
    }
}
