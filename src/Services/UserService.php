<?php
namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use App\Support\Validator;
use InvalidArgumentException;
use RuntimeException;

class UserService
{
    public function __construct(
        private UserRepositoryInterface $repo,
        private string $uploadDir = BASE_PATH . '/public/uploads/users',
        private string $uploadUrl = 'uploads/users'
    ) {}

    /** @return User[] */
    public function listAll(): array
    {
        return $this->repo->all();
    }

    public function get(int $id): ?User
    {
        return $this->repo->find($id);
    }

    /**
     * Validate + create a user.
     * @return array{user?: User, errors: array}
     */
    public function create(array $input, ?array $file = null): array
    {
        $v = $this->validate($input, isCreating: true);
        if ($v->fails()) {
            return ['errors' => $v->errors()];
        }

        if ($this->repo->existsByEmail($input['email'])) {
            return ['errors' => ['email' => 'Email is already registered.']];
        }
        if ($this->repo->existsByFullName(
            $input['first_name'],
            $input['middle_name'] ?? null,
            $input['last_name']
        )) {
            return ['errors' => ['first_name' => 'A user with this full name already exists.']];
        }

        $user = $this->hydrate(new User(), $input);
        $user->password = password_hash($input['password'], PASSWORD_DEFAULT);
        $user->photoPath = $this->storePhoto($file);

        return ['user' => $this->repo->create($user), 'errors' => []];
    }

    public function update(int $id, array $input, ?array $file = null): array
    {
        $user = $this->repo->find($id);
        if (!$user) {
            return ['errors' => ['_global' => 'User not found.']];
        }

        $v = $this->validate($input, isCreating: false);
        if ($v->fails()) {
            return ['errors' => $v->errors()];
        }

        if ($this->repo->existsByEmail($input['email'], exceptId: $id)) {
            return ['errors' => ['email' => 'Email is already taken.']];
        }
        if ($this->repo->existsByFullName(
            $input['first_name'],
            $input['middle_name'] ?? null,
            $input['last_name'],
            exceptId: $id
        )) {
            return ['errors' => ['first_name' => 'Another user already has this full name.']];
        }

        $this->hydrate($user, $input);

        if (!empty($input['password'])) {
            $user->password = password_hash($input['password'], PASSWORD_DEFAULT);
        }

        $newPhoto = $this->storePhoto($file);
        if ($newPhoto) {
            $this->deletePhoto($user->photoPath);
            $user->photoPath = $newPhoto;
        }

        $this->repo->update($user);
        return ['user' => $user, 'errors' => []];
    }

    public function delete(int $id): bool
    {
        $user = $this->repo->find($id);
        if (!$user) return false;

        $this->deletePhoto($user->photoPath);
        return $this->repo->delete($id);
    }

    /* ------------------------------------------------------------------ */

    private function validate(array $input, bool $isCreating): Validator
    {
        $v = new Validator($input);

        $v->required('first_name', 'First name')->max('first_name', 100)
          ->required('last_name',  'Last name')->max('last_name', 100)
          ->max('middle_name', 100)
          ->required('email', 'Email')->email('email')->max('email', 250)
          ->max('phoneno', 200)
          ->max('address', 250)
          ->date('dob');

        if ($isCreating || !empty($input['password'])) {
            $v->required('password', 'Password')->min('password', 8);
        }

        return $v;
    }

    private function hydrate(User $user, array $input): User
    {
        $user->firstName  = trim($input['first_name']);
        $user->middleName = !empty($input['middle_name']) ? trim($input['middle_name']) : null;
        $user->lastName   = trim($input['last_name']);
        $user->email      = trim($input['email']);
        $user->phoneNo    = $input['phoneno'] ?? null;
        $user->dob        = !empty($input['dob']) ? $input['dob'] : null;
        $user->address    = $input['address'] ?? null;
        return $user;
    }

    private function storePhoto(?array $file): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Photo upload failed (code ' . $file['error'] . ').');
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            throw new RuntimeException('Photo must be under 2 MB.');
        }

        $mime = mime_content_type($file['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($allowed[$mime])) {
            throw new RuntimeException('Only JPG, PNG, or WebP allowed.');
        }

        if (!is_dir($this->uploadDir) && !mkdir($this->uploadDir, 0775, true)) {
            throw new RuntimeException("Cannot create upload dir: {$this->uploadDir}");
        }

        $name = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
        $dest = $this->uploadDir . '/' . $name;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new RuntimeException('Failed to save uploaded file.');
        }

        return $this->uploadUrl . '/' . $name;
    }

    private function deletePhoto(?string $path): void
    {
        if (!$path) return;
        $abs = BASE_PATH . '/public/' . ltrim($path, '/');
        if (is_file($abs)) @unlink($abs);
    }
}