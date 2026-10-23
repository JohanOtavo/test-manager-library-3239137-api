<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters;

use App\Adapters\Security\NativePasswordHasher;
use PHPUnit\Framework\TestCase;

final class NativePasswordHasherTest extends TestCase
{
    public function test_password_hash_matches_only_the_original_password(): void
    {
        $hasher = new NativePasswordHasher();
        $hash = $hasher->hash('correct horse battery staple');

        self::assertNotSame('correct horse battery staple', $hash);
        self::assertTrue($hasher->matches('correct horse battery staple', $hash));
        self::assertFalse($hasher->matches('wrong password', $hash));
    }
}
