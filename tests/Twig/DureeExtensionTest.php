<?php

namespace App\Tests\Twig;

use App\Twig\DureeExtension;
use PHPUnit\Framework\TestCase;

/**
 * Test unitaire : pas de base de données, pas de Symfony, juste une méthode.
 */
class DureeExtensionTest extends TestCase
{
    public function testFormateLesMinutes(): void
    {
        $this->assertSame('0 min', DureeExtension::formater(0));
        $this->assertSame('45 min', DureeExtension::formater(45));
        $this->assertSame('2 h', DureeExtension::formater(120));
        $this->assertSame('2 h 05', DureeExtension::formater(125));
        $this->assertSame('5 h 36', DureeExtension::formater(336));
    }

    public function testUneValeurVideVautZero(): void
    {
        $this->assertSame('0 min', DureeExtension::formater(null));
        $this->assertSame('0 min', DureeExtension::formater(-10));
    }
}
