<?php

namespace App\Repositories;

use App\Database;
use App\Models\Education;
use PDO;

class EducationRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    /**
     * @return Education[]
     */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM educations ORDER BY started_date DESC, id DESC');
        $rows = $stmt->fetchAll();
        return array_map([Education::class, 'fromRow'], $rows);
    }

    public function find(int $id): ?Education
    {
        $stmt = $this->db->prepare('SELECT * FROM educations WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? Education::fromRow($row) : null;
    }

    public function findBySchoolName(string $schoolName): ?Education
    {
        $stmt = $this->db->prepare('SELECT * FROM educations WHERE school_name = ? LIMIT 1');
        $stmt->execute([$schoolName]);
        $row = $stmt->fetch();
        return $row ? Education::fromRow($row) : null;
    }

    public function existsBySchoolName(string $schoolName, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM educations WHERE school_name = ?';
        $params = [$schoolName];

        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }

        $stmt = $this->db->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public function create(Education $edu): Education
    {
        $sql = 'INSERT INTO educations (school_name, address, grades, started_date, end_date)
                VALUES (:school_name, :address, :grades, :started_date, :end_date)';

        $stmt = $this->db->prepare($sql);
        $data = $edu->toArray();
        $stmt->execute($data);

        $edu->id = (int) $this->db->lastInsertId();
        return $edu;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM educations WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
