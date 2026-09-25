<?php

namespace App\Tests\Unit;

use App\Entity\Curriculum;
use App\Entity\Theme;
use App\Entity\User;
use App\Service\StripeCheckoutService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class StripeCheckoutServiceTest extends TestCase
{
    public function testCheckoutRequiresATestSecretKey(): void
    {
        $service = new StripeCheckoutService('', $this->createMock(UrlGeneratorInterface::class));
        $this->expectException(\LogicException::class);
        $service->create(
            new User('client@example.test', 'hash'),
            new Curriculum(new Theme('Art'), 'Cours', 1500),
        );
    }
}
