<?php

/*
 * Copyright (c) 2025. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

declare(strict_types=1);

namespace App\Helpers;

use PDO;

/**
 * Service responsible for issuing, validating and revoking refresh tokens.
 */
final class RefreshTokenService
{
    private PDO $db;
    private int $ttl;

    /**
     * @param PDO $db Database connection
     * @param int $ttl Refresh token time to live in seconds
     */
    public function __construct(PDO $db, int $ttl = 2592000)
    {
        $this->db = $db;
        $this->ttl = $ttl; // default 30 days
    }

    /**
     * Generate new refresh token for user and persist hash.
     *
     * @param int    $userId User identifier
     * @param string $jti    JWT identifier
     *
     * @return string Raw refresh token
     */
    public function generate(int $userId, string $jti): string
    {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expires = time() + $this->ttl;

        $stmt = $this->db->prepare('INSERT INTO refresh_tokens (user_id, token_hash, jti, expires_at) VALUES (:uid, :hash, :jti, :exp)');
        $stmt->execute([
            'uid' => $userId,
            'hash' => $hash,
            'jti' => $jti,
            'exp' => $expires,
        ]);

        return $token;
    }

    /**
     * Validate refresh token and return DB row when valid.
     *
     * @param string $token Raw refresh token
     * @return array<string,mixed>|null
     */
    public function validate(string $token): ?array
    {
        $hash = hash('sha256', $token);
        $stmt = $this->db->prepare('SELECT id, user_id, jti, expires_at, revoked FROM refresh_tokens WHERE token_hash = :hash LIMIT 1');
        $stmt->execute(['hash' => $hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        if ((int)$row['revoked'] === 1 || (int)$row['expires_at'] < time()) {
            return null;
        }
        return $row;
    }

    /**
     * Revoke refresh token.
     *
     * @param string $token Raw refresh token
     */
    public function revoke(string $token): void
    {
        $hash = hash('sha256', $token);
        $stmt = $this->db->prepare('UPDATE refresh_tokens SET revoked = 1 WHERE token_hash = :hash');
        $stmt->execute(['hash' => $hash]);
    }

    /**
     * Remove expired tokens from storage.
     *
     * @return int Number of deleted records
     */
    public function purgeExpired(): int
    {
        $stmt = $this->db->prepare('DELETE FROM refresh_tokens WHERE expires_at < :now');
        $stmt->execute(['now' => time()]);
        return $stmt->rowCount();
    }
}
