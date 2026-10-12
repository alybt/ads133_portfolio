<?php

namespace App\Models;

class Project
{
    public ?int $id = null;
    public string $title = '';
    public ?string $startedDate = null;
    public ?string $endDate = null;
    public ?string $description = null;
    public ?int $descriptionId = null;
    public ?int $photoId = null;
    public ?string $photoName = null;
    public ?string $photoPath = null;
    public ?int $linkId = null;
    public ?string $linkName = null;
    public ?string $linkUrl = null;

    public static function fromRow(array $row): self
    {
        $project = new self();
        $project->id            = isset($row['id']) ? (int) $row['id'] : null;
        $project->title         = $row['title'] ?? '';
        $project->startedDate   = $row['started_date'] ?? null;
        $project->endDate       = $row['end_date'] ?? null;
        $project->description   = $row['description'] ?? null;
        $project->descriptionId = isset($row['description_id']) ? (int) $row['description_id'] : null;
        $project->photoId       = isset($row['photo_id']) ? (int) $row['photo_id'] : null;
        $project->photoName     = $row['photo_name'] ?? null;
        $project->photoPath     = $row['photo_path'] ?? null;
        $project->linkId        = isset($row['link_id']) ? (int) $row['link_id'] : null;
        $project->linkName      = $row['link_name'] ?? null;
        $project->linkUrl       = $row['link_url'] ?? $row['link'] ?? null;
        return $project;
    }

    public function toArray(): array
    {
        return [
            'title'        => $this->title,
            'started_date' => $this->startedDate,
            'end_date'     => $this->endDate,
        ];
    }
}
