<?php

namespace App\Repositories;

use App\Database;
use App\Models\Certificate;
use PDO;

class CertificateRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    /**
     * @return Certificate[]
     */
    public function all(): array
    {
        $sql = 'SELECT c.*, d.id AS description_id, d.description
                FROM certificates c
                LEFT JOIN certificate_description cd ON cd.certificate_id = c.id
                LEFT JOIN descriptions d ON d.id = cd.description_id
                ORDER BY c.received_date DESC, c.id DESC';

        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll();
        return array_map([Certificate::class, 'fromRow'], $rows);
    }

    public function find(int $id): ?Certificate
    {
        $sql = 'SELECT c.*, d.id AS description_id, d.description
                FROM certificates c
                LEFT JOIN certificate_description cd ON cd.certificate_id = c.id
                LEFT JOIN descriptions d ON d.id = cd.description_id
                WHERE c.id = ?
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? Certificate::fromRow($row) : null;
    }

    public function existsByTitle(string $title, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM certificates WHERE title = ?';
        $params = [$title];

        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }

        $stmt = $this->db->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public function create(Certificate $cert): Certificate
    {
        $this->db->beginTransaction();

        try {
            // 1. Insert into certificates
            $stmt = $this->db->prepare(
                'INSERT INTO certificates (title, company, received_date)
                 VALUES (:title, :company, :received_date)'
            );
            $stmt->execute($cert->toArray());
            $cert->id = (int) $this->db->lastInsertId();

            // 2. Insert description if provided
            if (!empty($cert->description)) {
                $descStmt = $this->db->prepare(
                    'INSERT INTO descriptions (title, description) VALUES (:title, :description)'
                );
                $descStmt->execute([
                    'title'       => mb_substr($cert->title, 0, 100),
                    'description' => $cert->description,
                ]);
                $descId = (int) $this->db->lastInsertId();
                $cert->descriptionId = $descId;

                $linkStmt = $this->db->prepare(
                    'INSERT INTO certificate_description (certificate_id, description_id)
                     VALUES (:certificate_id, :description_id)'
                );
                $linkStmt->execute([
                    'certificate_id' => $cert->id,
                    'description_id' => $descId,
                ]);
            }

            $this->db->commit();
            return $cert;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        $this->db->beginTransaction();

        try {
            // Find linked description IDs
            $stmt = $this->db->prepare(
                'SELECT description_id FROM certificate_description WHERE certificate_id = ?'
            );
            $stmt->execute([$id]);
            $descIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Delete link
            $delLink = $this->db->prepare('DELETE FROM certificate_description WHERE certificate_id = ?');
            $delLink->execute([$id]);

            // Delete descriptions if found
            if (!empty($descIds)) {
                $in = implode(',', array_fill(0, count($descIds), '?'));
                $delDesc = $this->db->prepare("DELETE FROM descriptions WHERE id IN ($in)");
                $delDesc->execute($descIds);
            }

            // Delete certificate
            $delCert = $this->db->prepare('DELETE FROM certificates WHERE id = ?');
            $res = $delCert->execute([$id]);

            $this->db->commit();
            return $res;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
