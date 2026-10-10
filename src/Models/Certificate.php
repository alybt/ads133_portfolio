<?php

namespace App\Models;

class Certificate
{
    public ?int $id = null;
    public string $title = '';
    public ?string $company = null;
    public ?string $receivedDate = null;
    public ?string $description = null;
    public ?int $descriptionId = null;

    public static function fromRow(array $row): self
    {
        $cert = new self();
        $cert->id           = isset($row['id']) ? (int) $row['id'] : null;
        $cert->title        = $row['title'] ?? '';
        $cert->company      = $row['company'] ?? null;
        $cert->receivedDate = $row['received_date'] ?? null;
        $cert->description  = $row['description'] ?? null;
        $cert->descriptionId= isset($row['description_id']) ? (int) $row['description_id'] : null;
        return $cert;
    }

    public function toArray(): array
    {
        return [
            'title'         => $this->title,
            'company'       => $this->company,
            'received_date' => $this->receivedDate,
        ];
    }
}
