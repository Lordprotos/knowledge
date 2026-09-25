<?php

namespace App\Tests\Unit;

use App\Entity\User;
use App\Security\VerifiedUserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

final class UserAccountTest extends TestCase
{
    public function testNewAccountHasClientRole(): void
    {
        $user = new User('client@example.test', 'hashed-password');
        self::assertSame(['ROLE_CLIENT'], $user->getRoles());
        self::assertFalse($user->isVerified());
    }

    public function testUnverifiedAccountCannotAuthenticate(): void
    {
        $this->expectException(CustomUserMessageAccountStatusException::class);
        (new VerifiedUserChecker())->checkPostAuth(new User('client@example.test', 'hash'));
    }

    public function testVerifiedAccountCanAuthenticate(): void
    {
        $user = new User('client@example.test', 'hash');
        $user->setVerificationToken('token');
        $user->verify();
        (new VerifiedUserChecker())->checkPostAuth($user);
        self::assertTrue($user->isVerified());
    }
}
