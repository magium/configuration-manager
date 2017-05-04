<?php

namespace Magium\Configuration\Tests\View;

use Magium\Configuration\Config\Repository\ConfigInterface;
use Magium\Configuration\File\Context\XmlFile;
use Magium\Configuration\View\Controllers\Helpers\ContextRepository;
use Magium\Configuration\View\Controllers\Layout;
use PHPUnit\Framework\TestCase;

class ContextsHelperTest extends TestCase
{


    public function testProvideContexts()
    {
        $provider = new ContextRepository(
            new XmlFile(realpath(__DIR__ . '/standalone/settings/contexts.xml'))
        );
        $contexts = $provider->getContextHierarchy();
        self::assertCount(1, $contexts);
        self::assertArrayHasKey('children', $contexts[0]);
        self::assertArrayHasKey('label', $contexts[0]);
        self::assertArrayHasKey('id', $contexts[0]);
        self::assertEquals('Default', $contexts[0]['label']);

        self::assertCount(3, $contexts[0]['children']);
        self::assertArrayHasKey('label', $contexts[0]['children'][0]);
        self::assertArrayHasKey('id', $contexts[0]['children'][0]);
        self::assertArrayHasKey('children', $contexts[0]['children'][0]);
        self::assertEquals('english', $contexts[0]['children'][0]['id']);
        self::assertEquals('English', $contexts[0]['children'][0]['label']);

        self::assertCount(2, $contexts[0]['children'][0]['children']);
        self::assertEquals('brit-english', $contexts[0]['children'][0]['children'][1]['id']);
        self::assertArrayHasKey('label', $contexts[0]['children'][0]['children'][1]);
        self::assertArrayHasKey('children', $contexts[0]['children'][0]['children'][1]);
        self::assertEquals('British English', $contexts[0]['children'][0]['children'][1]['label']);
        self::assertCount(0, $contexts[0]['children'][0]['children'][1]['children']);
    }

    public function testGetContextList()
    {
        $provider = new ContextRepository(
            new XmlFile(realpath(__DIR__ . '/standalone/settings/contexts.xml'))
        );
        $contexts = $provider->getContextList();
        self::assertArrayHasKey(ConfigInterface::CONTEXT_DEFAULT, $contexts);
        self::assertArrayHasKey('english', $contexts);
        self::assertArrayHasKey('american-english', $contexts);
        self::assertEquals('Default', $contexts['default']);
        self::assertEquals('English', $contexts['english']);
        self::assertEquals('American English', $contexts['american-english']);
    }

}
