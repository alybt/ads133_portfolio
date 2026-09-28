<?php

namespace App\Repositories;

use App\Models\User;


interface UserRepositoryInterface {
    public function all():array;

    public function find(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function existsByFullName(string $first, ?string $middle, string $last, ?int $exceptId = null): bool;

    public function existsByEmail(string $email, ?int $exceptId = null): bool;

    public function create(User $user): User;

    public function update(User $user): bool;

    public function delete(int $id):bool;


}