<?php

namespace Magium\Configuration\Tests\Source;

use Magium\Configuration\Source\Datetime\Locales;
use PHPUnit\Framework\TestCase;

class LocalesTest extends TestCase
{

    public function testLocale()
    {
        $locales = new Locales();
        $allLocales = $locales->getSourceData();
        self::assertArrayHasKey('en_US', $allLocales);
        self::assertEquals('English (United States)', $allLocales['en_US']);
    }

}
