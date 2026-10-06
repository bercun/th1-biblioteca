<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Model.php';

class User extends Model implements JsonSerializable
{
    protected ?int $id = null;
    protected string $first_name = '';
    protected string $last_name = '';
    protected string $email = '';
    protected ?string $password = null;
    protected int $login_count = 0;
    protected int $bonus = 0;
    protected string $role = 'user';
    protected int $purchases_count = 0;

    private PDO $db;

    //Constructor to set database connection
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    //Convert user to array
    public function toArray(bool $includePassword = false): array
    {
        $data = [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'login_count' => $this->login_count,
            'bonus' => $this->bonus,
            'role' => $this->role,
            'purchases_count' => $this->purchases_count
        ];

        if ($includePassword) {
            $data['password'] = $this->password;
        }

        return $data;
    }

    //Convert user to JSON
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    //Find user by ID
    public function findById(int $id): ?self
    {
        $stmt = $this->db->prepare(
            'SELECT
                id,
                first_name,
                last_name,
                email,
                login_count,
                bonus,
                role
             FROM users
             WHERE id = :id'
        );

        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->mapRow($row) : null;
    }

    //Find user by email
    public function findByEmail(string $email): ?self
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM users WHERE email = :email'
        );

        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row ? $this->mapRow($row) : null;
    }

    //Check if email exists
    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM users WHERE email = :email'
        );

        $stmt->execute(['email' => $email]);

        return (bool) $stmt->fetch();
    }

    public function create(): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users
                (first_name, last_name, email, password, login_count)
             VALUES
                (:first_name, :last_name, :email, :password, 1)'
        );

        $stmt->execute([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'password' => $this->password
        ]);

        $this->id = (int) $this->db->lastInsertId();
        $this->login_count = 1;
        $this->role = 'user';

        return $this->id;
    }

    //Increment login count
    public function incrementLoginCount(?int $id = null): void
    {
        $userId = $id ?? $this->id;

        if ($userId === null) {
            return;
        }

        $this->db->prepare(
            'UPDATE users
             SET login_count = login_count + 1
             WHERE id = :id'
        )->execute(['id' => $userId]);
    }

    //Add bonus
    public function addBonus(int $bonus, ?int $id = null): void
    {
        $userId = $id ?? $this->id;

        if ($userId === null) {
            return;
        }

        $this->db->prepare(
            'UPDATE users
             SET bonus = bonus + :bonus
             WHERE id = :id'
        )->execute([
            'bonus' => $bonus,
            'id' => $userId
        ]);
    }

    //Get bonus
    public function getBonus(?int $id = null): int
    {
        $userId = $id ?? $this->id;

        if ($userId === null) {
            return 0;
        }

        $stmt = $this->db->prepare(
            'SELECT bonus FROM users WHERE id = :id'
        );

        $stmt->execute(['id' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    //Check if user is admin
    public function isAdmin(?int $id = null): bool
    {
        if ($id === null) {
            return $this->role === 'admin';
        }

        $user = $this->findById($id);

        return $user !== null && $user->role === 'admin';
    }

    //List users for admin
    /** @return self[] */
    public function listForAdmin(): array
    {
        $sql = "
            SELECT
                u.id,
                u.first_name,
                u.last_name,
                u.email,
                u.login_count,
                u.bonus,
                u.role,
                COUNT(ub.book_id) AS purchases_count
            FROM users u
            LEFT JOIN user_books ub
                ON ub.user_id = u.id
            GROUP BY
                u.id,
                u.first_name,
                u.last_name,
                u.email,
                u.login_count,
                u.bonus,
                u.role
            ORDER BY u.id
        ";

        $rows = $this->db->query($sql)->fetchAll();

        return array_map(
            fn(array $row) => $this->mapRow($row),
            $rows
        );
    }

    //Map row to user
    private function mapRow(array $row): self
    {
        $user = new self($this->db);
        $user->id = isset($row['id']) ? (int) $row['id'] : null;
        $user->first_name = (string) ($row['first_name'] ?? '');
        $user->last_name = (string) ($row['last_name'] ?? '');
        $user->email = (string) ($row['email'] ?? '');
        $user->password = $row['password'] ?? null;
        $user->login_count = (int) ($row['login_count'] ?? 0);
        $user->bonus = (int) ($row['bonus'] ?? 0);
        $user->role = (string) ($row['role'] ?? 'user');
        $user->purchases_count = (int) ($row['purchases_count'] ?? 0);

        return $user;
    }
}
