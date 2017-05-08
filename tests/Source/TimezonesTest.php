<?php

namespace Magium\Configuration\Tests\Source;

use Magium\Configuration\Source\Datetime\Timezones;
use PHPUnit\Framework\TestCase;

class TimezonesTest extends TestCase
{

    public function testTimezones()
    {
        $timezones = new Timezones();
        $allTimezones= $timezones->getSourceData();
        self::assertContains('America/Chicago', $allTimezones);
    }

}
