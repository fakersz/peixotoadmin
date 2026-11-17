<?php
/** Model: users */

function user_find_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE LOWER(email) = LOWER(?)');
    $stmt->execute([$email]);
    return $stmt->fetch() ?: null;
}

function user_count(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
}

function user_create(string $name, string $email, string $password): int
{
    $stmt = db()->prepare(
        'INSERT INTO users (name, email, password_hash)
         VALUES (?, ?, ?) RETURNING id'
    );
    $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
    return (int) $stmt->fetchColumn();
}

function user_update_profile(int $id, string $name, string $email): void
{
    $stmt = db()->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
    $stmt->execute([$name, $email, $id]);
}

function user_update_password(int $id, string $password): void
{
    $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
}

/** Garante que o admin inicial exista quando o banco está vazio. */
function user_seed_admin(): void
{
    if (user_count() === 0) {
        $email = (string) env('SEED_ADMIN_EMAIL', '');
        $password = (string) env('SEED_ADMIN_PASSWORD', '');
        if ($email !== '' && $password !== '') {
            user_create((string) env('SEED_ADMIN_NAME', 'Admin'), $email, $password);
        }
    }
}
