<?php 

namespace App\Models;

class User {
    public ?int $id = null;
    public string $firstName = '';
    public ?string $middleName = null;
    public string $lastName = '';
    public string $email = '';
    public string $password = '';
    public ?string $phoneNo = null;
    public ?string $dob = null;
    public ?string $address = null;
    public ?string $photoPath = null;


    public static function fromRow(array $row): self {
        $u = new self();
        $u->id         = isset($row['id'])         ? (int) $row['id']       : null;
        $u->firstName  = $row['first_name']        ?? '';
        $u->middleName = $row['middle_name']       ?? null;
        $u->lastName   = $row['last_name']         ?? '';
        $u->email      = $row['email']             ?? '';
        $u->password   = $row['password']          ?? '';
        $u->phoneNo    = $row['phoneno']           ?? null;
        $u->dob        = $row['dob']               ?? null;
        $u->address    = $row['address']           ?? null;
        $u->photoPath  = $row['photo_path']        ?? null;
        return $u;
    }


    public function toArray(): array {
        return [
            'first_name'  => $this->firstName,
            'middle_name' => $this->middleName,
            'last_name'   => $this->lastName,
            'email'       => $this->email,
            'password'    => $this->password,
            'phoneno'     => $this->phoneNo,
            'dob'         => $this->dob,
            'address'     => $this->address,
            'photo_path'  => $this->photoPath,

        ];
    }

    public function fullName(): string{
        $parts = array_filter(
            [$this->firstName, $this->middleName, $this->lastName],
            fn($p) => $p !== null && $p !== ''
        );
        return implode(' ', $parts);
    }



}


?>