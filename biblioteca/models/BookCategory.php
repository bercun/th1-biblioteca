<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Model.php';

class BookCategory extends Model implements JsonSerializable
{
    protected ?int $id = null;
    protected string $season = '';

    private PDO $db;
    //Constructor to set database connection
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    //Convert category to array
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'season' => $this->season
        ];
    }

    //Convert category to JSON
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    //Check if category exists
    public function exists(?int $id = null): bool
    {
        $categoryId = $id ?? $this->id;

        if ($categoryId === null) {
            return false;
        }

        $stmt = $this->db->prepare(
            'SELECT id FROM books_category WHERE id = :id'
        );

        $stmt->execute(['id' => $categoryId]);

        return (bool) $stmt->fetch();
    }

    //List all categories
    /** @return self[] */
    public function findAll(): array
    {
        $rows = $this->db
            ->query(
                'SELECT id, season
                 FROM books_category
                 ORDER BY id'
            )
            ->fetchAll();

        return array_map(
            function (array $row) {
                $category = new self($this->db);
                $category->id = (int) $row['id'];
                $category->season = (string) $row['season'];

                return $category;
            },
            $rows
        );
    }
}
