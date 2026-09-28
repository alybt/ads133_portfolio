<?php
namespace App\Repositories;

use App\Database;
use App\Models\User;
use PDO;

class UserRepository implements UserRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::connection();
    }

    public function all(): array {
        $rows = $this->db->query('SELECT * FROM users ORDER BY last_name, first_name')->fetchAll();
        return array_map([User::class, 'fromRow'], $rows);
    }

    public function find(int $id): ?User {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? User::fromRow($row) : null;
    }

    public function findByEmail(string $email): ?User {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ? User::fromRow($row) : null;
    }

    public function existsByFullName(string $first, ?string $middle, string $last, ?int $exceptId = null): bool {
        $middle = $middle ?? '';

        $sql = 'SELECT 1 FROM users
                WHERE first_name = ? AND COALESCE(middle_name, \'\') = ? AND last_name = ?';
        $params = [$first, $middle, $last];

        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }

        $stmt = $this->db->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public function existsByEmail(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM users WHERE email = ?';
        $params = [$email];

        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }

        $stmt = $this->db->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public function create(User $user): User
    {
        $sql = 'INSERT INTO users
                (first_name, middle_name, last_name, email, password, phoneno, dob, address, photo_path)
                VALUES (:first_name, :middle_name, :last_name, :email, :password, :phoneno, :dob, :address, :photo_path)';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($user->toArray());

        $user->id = (int) $this->db->lastInsertId();
        return $user;
    }

    public function update(User $user): bool
    {
        $sql = 'UPDATE users SET
                    first_name  = :first_name,
                    middle_name = :middle_name,
                    last_name   = :last_name,
                    email       = :email,
                    password    = :password,
                    phoneno     = :phoneno,
                    dob         = :dob,
                    address     = :address,
                    photo_path  = :photo_path
                WHERE id = :id';

        $data = $user->toArray();
        $data['id'] = $user->id;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = ?');
        return $stmt->execute([$id]);
    }

    
}