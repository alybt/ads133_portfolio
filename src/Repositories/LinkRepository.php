<?php

namespace App\Repositories;

use App\Database;
use App\Models\Link;
use PDO;

class LinkRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    /**
     * @return Link[]
     */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM links ORDER BY id DESC');
        $rows = $stmt->fetchAll();
        return array_map([Link::class, 'fromRow'], $rows);
    }

    public function find(int $id): ?Link
    {
        $stmt = $this->db->prepare('SELECT * FROM links WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? Link::fromRow($row) : null;
    }

    public function existsByName(string $name, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM links WHERE name = ?';
        $params = [$name];

        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }

        $stmt = $this->db->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public function create(Link $link): Link
    {
        $stmt = $this->db->prepare(
            'INSERT INTO links (name, link) VALUES (:name, :link)'
        );
        $stmt->execute($link->toArray());
        $link->id = (int) $this->db->lastInsertId();
        return $link;
    }

    public function delete(int $id): bool
    {
        $this->db->beginTransaction();

        try {
            // Disconnect from project_description if linked to prevent foreign key errors
            $disconnectStmt = $this->db->prepare(
                'UPDATE project_description SET link_id = NULL WHERE link_id = ?'
            );
            $disconnectStmt->execute([$id]);

            // Delete link
            $delStmt = $this->db->prepare('DELETE FROM links WHERE id = ?');
            $res = $delStmt->execute([$id]);

            $this->db->commit();
            return $res;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
