<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters;

use App\Adapters\Security\JwtTokenIssuer;
use App\Application\Shared\AuthenticatedUser;
use App\Domain\Shared\Role;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fake\FixedClock;

final class JwtTokenIssuerTest extends TestCase
{
    private FixedClock $clock;

    private JwtTokenIssuer $issuer;

    protected function setUp(): void
    {
        $this->clock = new FixedClock(new DateTimeImmutable('2026-10-22T12:00:00Z'));
        $this->issuer = new JwtTokenIssuer($this->clock, 'a-test-only-secret', 28800);
    }

    public function test_emite_y_verifica_un_token_con_usuario_y_rol(): void
    {
        $issued = $this->issuer->issue('user-123', Role::Librarian);

        $this->assertSame(28800, $issued->expiresInSeconds);
        $this->assertEquals(new AuthenticatedUser('user-123', Role::Librarian), $this->issuer->verify($issued->accessToken));
    }

    public function test_rechaza_una_firma_modificada(): void
    {
        $issued = $this->issuer->issue('user-123', Role::Admin);
        $tampered = substr($issued->accessToken, 0, -1).(str_ends_with($issued->accessToken, 'a') ? 'b' : 'a');

        $this->assertNull($this->issuer->verify($tampered));
    }

    public function test_rechaza_un_token_vencido(): void
    {
        $issued = $this->issuer->issue('user-123', Role::Admin);
        $this->clock->travelTo('2026-10-22T20:00:00Z');

        $this->assertNull($this->issuer->verify($issued->accessToken));
    }

    public function test_rechaza_un_algoritmo_distinto_de_hs256_aunque_la_firma_sea_valida(): void
    {
        $header = self::base64Url(json_encode(['alg' => 'HS512', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $payload = self::base64Url(json_encode([
            'sub' => 'user-123',
            'role' => 'admin',
            'iat' => $this->clock->now()->getTimestamp(),
            'exp' => $this->clock->now()->getTimestamp() + 28800,
        ], JSON_THROW_ON_ERROR));
        $unsigned = $header.'.'.$payload;
        $signature = self::base64Url(hash_hmac('sha256', $unsigned, 'a-test-only-secret', true));

        $this->assertNull($this->issuer->verify($unsigned.'.'.$signature));
    }

    private static function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
