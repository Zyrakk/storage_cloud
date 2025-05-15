<?php
namespace App;

class User {
    public int $id;
    public string $username;
    public string $password_hash;
    public ?string $totp_secret;

    /* Busca un usuario por username y devuelve un objeto User o null.*/
    public static function findByUsername(string $u): ?self {
        // Seleccionamos password AS password_hash para mapear correctamente
        $stmt = \getDb()->prepare(
            'SELECT id, username, password AS password_hash, totp_secret 
             FROM users 
             WHERE username = ?'
        );
        $stmt->execute([$u]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$data) {
            return null;
        }
        $user = new self();
        $user->id            = (int)$data['id'];
        $user->username      = $data['username'];
        $user->password_hash = $data['password_hash'];
        $user->totp_secret   = $data['totp_secret'];
        return $user;
    }

    /* Crea un nuevo usuario, devuelve el objeto User con su nuevo ID.*/
    public static function create(string $u, string $pw, ?string $secret): self {
        $hash = password_hash($pw, PASSWORD_BCRYPT);
        $db   = \getDb();
        $stmt = $db->prepare(
            'INSERT INTO users (username, password, totp_secret) 
             VALUES (:username, :password, :secret)
             RETURNING id'
        );
        $stmt->execute([
            ':username' => $u,
            ':password' => $hash,
            ':secret'   => $secret,
        ]);
        $newId = (int)$stmt->fetchColumn();

        $user = new self();
        $user->id            = $newId;
        $user->username      = $u;
        $user->password_hash = $hash;
        $user->totp_secret   = $secret;
        return $user;
    }
}
