<?php
declare(strict_types=1);

class UserModel
{
        private const COLUMNS = 'id, username, email, password, full_name, bio, profile_image, anon_name, anon_mode, created_at';
    public function __construct(private PDO $pdo) {}

    public function findById(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM users WHERE id = :id');
        $st->execute([':id' => $id]);
        return $st->fetch() ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $st = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM users WHERE username = :u');
        $st->execute([':u' => $username]);
        return $st->fetch() ?: null;
    }

    //Login: email address or username.
    public function findByIdentifier(string $identifier): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT ' . self::COLUMNS . ' FROM users WHERE email = :e OR username = :u LIMIT 1'
        );
        $st->execute([':e' => $identifier, ':u' => $identifier]);
        return $st->fetch() ?: null;
    }

    public function usernameExists(string $username): bool
    {
        $st = $this->pdo->prepare('SELECT 1 FROM users WHERE username = :u LIMIT 1');
        $st->execute([':u' => $username]);
        return (bool) $st->fetchColumn();
    }

    public function emailExists(string $email): bool
    {
        $st = $this->pdo->prepare('SELECT 1 FROM users WHERE email = :e LIMIT 1');
        $st->execute([':e' => $email]);
        return (bool) $st->fetchColumn();
    }

    /** Returns the new user's id. Throws PDOException (code 23000) on duplicates. */
    public function create(string $username, string $email, string $passwordHash): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO users (username, email, password)
             VALUES (:username, :email, :password)'
        );
        $st->execute([
            ':username' => $username,
            ':email'    => $email,
            ':password' => $passwordHash,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateProfile(int $id, string $fullName, ?string $bio, bool $setImage, ?string $image): void
    {
        if ($setImage) {
            $st = $this->pdo->prepare(
                'UPDATE users SET full_name = :n, bio = :b, profile_image = :i WHERE id = :id'
            );
            $st->execute([':n' => $fullName, ':b' => $bio, ':i' => $image, ':id' => $id]);
        } else {
            $st = $this->pdo->prepare('UPDATE users SET full_name = :n, bio = :b WHERE id = :id');
            $st->execute([':n' => $fullName, ':b' => $bio, ':id' => $id]);
        }
    }

    public function search(string $like, int $limit = 20): array
    {
        $st = $this->pdo->prepare(
            "SELECT id, username, full_name, bio, profile_image
               FROM users
              WHERE username LIKE :a ESCAPE '!' OR full_name LIKE :b ESCAPE '!'
              ORDER BY full_name ASC
              LIMIT :lim"
        );
        $st->bindValue(':a', $like);
        $st->bindValue(':b', $like);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public function anonNameTaken(string $name, int $exceptId): bool
    {
        $st = $this->pdo->prepare('SELECT 1 FROM users WHERE anon_name = :n AND id <> :id LIMIT 1');
        $st->execute([':n' => $name, ':id' => $exceptId]);
        return (bool) $st->fetchColumn();
    }

    public function setAnonName(int $id, string $name): void
    {
        $st = $this->pdo->prepare('UPDATE users SET anon_name = :n WHERE id = :id');
        $st->execute([':n' => $name, ':id' => $id]);
    }

    public function setAnonMode(int $id, bool $on): void
    {
        $st = $this->pdo->prepare('UPDATE users SET anon_mode = :m WHERE id = :id');
        $st->execute([':m' => $on ? 1 : 0, ':id' => $id]);
    }

        public function isAnonymous(int $id): bool
    {
        $st = $this->pdo->prepare(
            "SELECT 1 FROM users
              WHERE id = :id AND anon_mode = 1 AND anon_name IS NOT NULL AND anon_name <> '' LIMIT 1"
        );
        $st->execute([':id' => $id]);
        return (bool) $st->fetchColumn();
    }
}