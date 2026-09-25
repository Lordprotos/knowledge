<?php

namespace App\Tests\Unit;

use App\Entity\Curriculum;
use App\Entity\Purchase;
use App\Entity\Theme;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class PurchaseTest extends TestCase
{
    public function testPurchaseRecordsThePurchasedCurriculumAndAmount(): void
    {
        $user = new User('client@example.test', 'hash');
        $theme = new Theme('Musique');
        $curriculum = new Curriculum($theme, 'Guitare', 5000);
        $purchase = new Purchase($user, 5000, $curriculum);
        self::assertSame($user, $purchase->getUser());
        self::assertSame($curriculum, $purchase->getCurriculum());
        self::assertSame(5000, $purchase->getAmountCents());
        self::assertNull($purchase->getLesson());
    }
}
