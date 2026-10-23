<?php

declare(strict_types=1);

namespace App\Adapters\Security;

use App\Application\Port\Clock;
use App\Application\Port\TokenIssuer;
use App\Application\Shared\AuthenticatedUser;
use App\Application\Shared\IssuedToken;
use App\Domain\Shared\Role;

/**
 * JWT HS256 firmado con la clave del entorno (D-11).
 *
 * Son tres piezas en base64url separadas por puntos; la firma es un HMAC del
 * texto de las dos primeras. Dentro va el sujeto y el rol, nada personal
 * (api-contract §4). No hace falta una librería para esto.
 */
final readonly class JwtTokenIssuer implements TokenIssuer
{
    public function __construct(
        private Clock $clock,
        private string $secret,
        private int $ttlSeconds,
    ) {}

    public function issue(string $userId, Role $role): IssuedToken
    {
        $issuedAt = $this->clock->now()->getTimestamp();

        $header = $this->encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = $this->encode([
            'sub' => $userId,
            'role' => $role->value,
            'iat' => $issuedAt,
            'exp' => $issuedAt + $this->ttlSeconds,
        ]);

        $token = $header.'.'.$payload;

        return new IssuedToken($token.'.'.$this->sign($token), $this->ttlSeconds);
    }

    public function verify(string $token): ?AuthenticatedUser
    {
        $pieces = explode('.', $token);

        if (count($pieces) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $pieces;

        $headerClaims = json_decode($this->decode($header), true);

        if (! is_array($headerClaims) || ($headerClaims['alg'] ?? null) !== 'HS256') {
            return null;
        }

        // hash_equals compara en tiempo constante: no se filtra la firma a base
        // de medir cuánto tarda en fallar.
        if (! hash_equals($this->sign($header.'.'.$payload), $signature)) {
            return null;
        }

        $claims = json_decode($this->decode($payload), true);

        if (! is_array($claims) || ! isset($claims['sub'], $claims['role'], $claims['exp'])) {
            return null;
        }

        if ((int) $claims['exp'] <= $this->clock->now()->getTimestamp()) {
            return null;
        }

        $role = Role::tryFrom((string) $claims['role']);

        return $role === null ? null : new AuthenticatedUser((string) $claims['sub'], $role);
    }

    /** @param array<string, mixed> $data */
    private function encode(array $data): string
    {
        return rtrim(strtr(base64_encode((string) json_encode($data)), '+/', '-_'), '=');
    }

    private function decode(string $value): string
    {
        $padded = str_pad(strtr($value, '-_', '+/'), (int) (ceil(strlen($value) / 4) * 4), '=');
        $decoded = base64_decode($padded, true);

        return $decoded === false ? '' : $decoded;
    }

    private function sign(string $token): string
    {
        return rtrim(strtr(base64_encode(hash_hmac('sha256', $token, $this->secret, true)), '+/', '-_'), '=');
    }
}
