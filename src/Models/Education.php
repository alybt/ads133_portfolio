<?php

namespace App\Models;

class Education
{
    public ?int $id = null;
    public string $schoolName = '';
    public ?string $address = null;
    public ?string $grades = null;
    public ?string $startedDate = null;
    public ?string $endDate = null;

    public static function fromRow(array $row): self
    {
        $edu = new self();
        $edu->id          = isset($row['id']) ? (int) $row['id'] : null;
        $edu->schoolName  = $row['school_name'] ?? '';
        $edu->address     = $row['address'] ?? null;
        $edu->grades      = isset($row['grades']) ? (string) $row['grades'] : null;
        $edu->startedDate = $row['started_date'] ?? null;
        $edu->endDate     = $row['end_date'] ?? null;
        return $edu;
    }

    public function toArray(): array
    {
        return [
            'school_name'  => $this->schoolName,
            'address'      => $this->address,
            'grades'       => $this->grades,
            'started_date' => $this->startedDate,
            'end_date'     => $this->endDate,
        ];
    }
}
