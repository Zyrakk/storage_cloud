<?php
namespace App;

class User {
    public int $id;
    public string $username;
    public string $password_hash;
    public ?string $totp_secret;

    public static function findByUsername(string $u): ?self {
        $stmt = \App\getDb()->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$u]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$data) return null;
        $user = new self();
        foreach ($data as $k => $v) $user->$k = $v;
        return $user;
    }

    public static function create(string $u, string $pw, string $secret): self {
        $hash = password_hash($pw, PASSWORD_BCRYPT);
        $db = \App\getDb();
        $stmt = $db->prepare('INSERT INTO users (username,password_hash,totp_secret) VALUES (?,?,?) RETURNING id');
        $stmt->execute([$u, $hash, $secret]);
        $user = new self();
        $user->id = (int)$stmt->fetchColumn();
        $user->username = $u;
        $user->password_hash = $hash;
        $user->totp_secret = $secret;
        return $user;
    }
}
