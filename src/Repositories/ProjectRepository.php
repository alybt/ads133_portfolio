<?php

namespace App\Repositories;

use App\Database;
use App\Models\Project;
use PDO;

class ProjectRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    /**
     * @return Project[]
     */
    public function all(): array
    {
        $sql = 'SELECT p.*,
                       d.id AS description_id,
                       d.description,
                       ph.id AS photo_id,
                       ph.name AS photo_name,
                       ph.path AS photo_path,
                       l.id AS link_id,
                       l.name AS link_name,
                       l.link AS link_url
                FROM projects p
                LEFT JOIN project_description pd ON pd.project_id = p.id
                LEFT JOIN descriptions d ON d.id = pd.description_id
                LEFT JOIN photos ph ON ph.id = pd.path_id
                LEFT JOIN links l ON l.id = pd.link_id
                ORDER BY p.started_date DESC, p.id DESC';

        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll();
        return array_map([Project::class, 'fromRow'], $rows);
    }

    public function find(int $id): ?Project
    {
        $sql = 'SELECT p.*,
                       d.id AS description_id,
                       d.description,
                       ph.id AS photo_id,
                       ph.name AS photo_name,
                       ph.path AS photo_path,
                       l.id AS link_id,
                       l.name AS link_name,
                       l.link AS link_url
                FROM projects p
                LEFT JOIN project_description pd ON pd.project_id = p.id
                LEFT JOIN descriptions d ON d.id = pd.description_id
                LEFT JOIN photos ph ON ph.id = pd.path_id
                LEFT JOIN links l ON l.id = pd.link_id
                WHERE p.id = ?
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? Project::fromRow($row) : null;
    }

    public function existsByTitle(string $title, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM projects WHERE title = ?';
        $params = [$title];

        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }

        $stmt = $this->db->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public function create(Project $project): Project
    {
        $this->db->beginTransaction();

        try {
            // 1. Insert into projects
            $stmt = $this->db->prepare(
                'INSERT INTO projects (title, started_date, end_date)
                 VALUES (:title, :started_date, :end_date)'
            );
            $stmt->execute($project->toArray());
            $project->id = (int) $this->db->lastInsertId();

            $descId  = null;
            $photoId = null;
            $linkId  = null;

            // 2. Insert description if provided
            if (!empty($project->description)) {
                $descStmt = $this->db->prepare(
                    'INSERT INTO descriptions (title, description) VALUES (:title, :description)'
                );
                $descStmt->execute([
                    'title'       => mb_substr($project->title, 0, 100),
                    'description' => $project->description,
                ]);
                $descId = (int) $this->db->lastInsertId();
                $project->descriptionId = $descId;
            }

            // 3. Insert photo if provided
            if (!empty($project->photoPath)) {
                $photoName = !empty($project->photoName)
                    ? mb_substr($project->photoName, 0, 250)
                    : mb_substr($project->title . ' Thumbnail', 0, 250);

                $photoStmt = $this->db->prepare(
                    'INSERT INTO photos (name, path) VALUES (:name, :path)'
                );
                $photoStmt->execute([
                    'name' => $photoName,
                    'path' => mb_substr($project->photoPath, 0, 250),
                ]);
                $photoId = (int) $this->db->lastInsertId();
                $project->photoId = $photoId;
                $project->photoName = $photoName;
            }

            // 4. Insert link if provided
            if (!empty($project->linkUrl)) {
                $linkName = !empty($project->linkName)
                    ? mb_substr($project->linkName, 0, 100)
                    : mb_substr($project->title . ' Link', 0, 100);

                $linkStmt = $this->db->prepare(
                    'INSERT INTO links (name, link) VALUES (:name, :link)'
                );
                $linkStmt->execute([
                    'name' => $linkName,
                    'link' => mb_substr($project->linkUrl, 0, 250),
                ]);
                $linkId = (int) $this->db->lastInsertId();
                $project->linkId   = $linkId;
                $project->linkName = $linkName;
            }

            // 5. Link via project_description if description, photo, or link exists
            if ($descId !== null || $photoId !== null || $linkId !== null) {
                $linkStmt = $this->db->prepare(
                    'INSERT INTO project_description (project_id, description_id, path_id, link_id)
                     VALUES (:project_id, :description_id, :path_id, :link_id)'
                );
                $linkStmt->execute([
                    'project_id'     => $project->id,
                    'description_id' => $descId,
                    'path_id'        => $photoId,
                    'link_id'        => $linkId,
                ]);
            }

            $this->db->commit();
            return $project;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        $this->db->beginTransaction();

        try {
            // Find linked description, photo, and link IDs
            $stmt = $this->db->prepare(
                'SELECT description_id, path_id, link_id FROM project_description WHERE project_id = ?'
            );
            $stmt->execute([$id]);
            $links = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $descIds  = [];
            $photoIds = [];
            $linkIds  = [];
            foreach ($links as $link) {
                if (!empty($link['description_id'])) {
                    $descIds[] = (int) $link['description_id'];
                }
                if (!empty($link['path_id'])) {
                    $photoIds[] = (int) $link['path_id'];
                }
                if (!empty($link['link_id'])) {
                    $linkIds[] = (int) $link['link_id'];
                }
            }

            // Delete junction links
            $delLink = $this->db->prepare('DELETE FROM project_description WHERE project_id = ?');
            $delLink->execute([$id]);

            // Delete descriptions
            if (!empty($descIds)) {
                $in = implode(',', array_fill(0, count($descIds), '?'));
                $delDesc = $this->db->prepare("DELETE FROM descriptions WHERE id IN ($in)");
                $delDesc->execute($descIds);
            }

            // Delete photos and remove physical files if stored locally
            if (!empty($photoIds)) {
                $in = implode(',', array_fill(0, count($photoIds), '?'));
                $photoStmt = $this->db->prepare("SELECT path FROM photos WHERE id IN ($in)");
                $photoStmt->execute($photoIds);
                $filePaths = $photoStmt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($filePaths as $path) {
                    if ($path && !filter_var($path, FILTER_VALIDATE_URL)) {
                        $fullPath = defined('BASE_PATH') ? BASE_PATH . '/public/' . ltrim($path, '/') : null;
                        if ($fullPath && is_file($fullPath)) {
                            @unlink($fullPath);
                        }
                    }
                }

                $delPhotos = $this->db->prepare("DELETE FROM photos WHERE id IN ($in)");
                $delPhotos->execute($photoIds);
            }

            // Delete links
            if (!empty($linkIds)) {
                $in = implode(',', array_fill(0, count($linkIds), '?'));
                $delLinks = $this->db->prepare("DELETE FROM links WHERE id IN ($in)");
                $delLinks->execute($linkIds);
            }

            // Delete project
            $delProj = $this->db->prepare('DELETE FROM projects WHERE id = ?');
            $res = $delProj->execute([$id]);

            $this->db->commit();
            return $res;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
