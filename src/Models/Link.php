<?php

namespace App\Models;

class Link
{
    public ?int $id = null;
    public string $name = '';
    public string $link = '';

    public static function fromRow(array $row): self
    {
        $link = new self();
        $link->id   = isset($row['id']) ? (int) $row['id'] : null;
        $link->name = $row['name'] ?? '';
        $link->link = $row['link'] ?? '';
        return $link;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'link' => $this->link,
        ];
    }
}
