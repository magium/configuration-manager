<?php

namespace Magium\Configuration\Tests\Storage;

use Magium\Configuration\Config\Repository\ConfigInterface;
use Magium\Configuration\Config\Storage\Mongo;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection;
use MongoDB\Model\BSONDocument;
use PHPUnit\Framework\TestCase;

class MongoTest extends TestCase
{

    protected function setUp()
    {
        if (!extension_loaded('mongodb')) {
            $this->markTestSkipped('Mongo extension not installed');
        }
        parent::setUp();
    }

    public function testGetOnNullReturnsNull()
    {
        $collection = $this->getMockBuilder(Collection::class)->disableOriginalConstructor()
            ->setMethods(['findOne', 'insertOne'])->getMock();
        $collection->expects(self::once())->method('findOne')->willReturn(null)->with([
            'context' => ConfigInterface::CONTEXT_DEFAULT
        ]);
        $mongo = new Mongo($collection);
        $result = $mongo->getValue('a/b/c');
        self::assertNull($result);
    }

    public function testGetOnDocumentReturnsValue()
    {
        $storageDocument = new BSONDocument([
            '_id' => new ObjectId(),
            'document' => [
                'a' => ['b' => ['c' => 'test']]
            ]
        ]);
        $collection = $this->getMockBuilder(Collection::class)->disableOriginalConstructor()
            ->setMethods(['findOne', 'insertOne'])->getMock();
        $collection->expects(self::once())->method('findOne')->willReturn($storageDocument)->with([
            'context' => ConfigInterface::CONTEXT_DEFAULT
        ]);
        $mongo = new Mongo($collection);
        $result = $mongo->getValue('a/b/c');
        self::assertEquals('test', $result);
    }

    public function testSetOnNewDocument()
    {
        $collection = $this->getMockBuilder(Collection::class)->disableOriginalConstructor()
            ->setMethods(['findOne', 'insertOne'])->getMock();
        $collection->expects(self::once())->method('findOne')->willReturn(null)->with([
            'context' => ConfigInterface::CONTEXT_DEFAULT
        ]);
        $collection->expects(self::once())->method('insertOne')->willReturn(null)->with([
            'context' => ConfigInterface::CONTEXT_DEFAULT,
            'document' => [
                'a' => ['b' => ['c' => true]]
            ]
        ]);
        $mongo = new Mongo($collection);
        $mongo->setValue('a/b/c', true);
    }

    public function testSetOnExistingDocument()
    {
        $storageDocument = new BSONDocument([
            '_id' => new ObjectId(),
            'context' => ConfigInterface::CONTEXT_DEFAULT,
            'document' => [
                'a' => ['b' => ['c' => true]]
            ]
        ]);
        $storageDocumentTest = clone $storageDocument;
        $storageDocumentTest['document']['a']['b']['c'] = false;
        $collection = $this->getMockBuilder(Collection::class)->disableOriginalConstructor()
            ->setMethods(['findOne', 'replaceOne'])->getMock();
        $collection->expects(self::once())->method('findOne')->willReturn($storageDocument)->with(
            [
                'context' => ConfigInterface::CONTEXT_DEFAULT
            ]
        );
        $collection->expects(self::once())->method('replaceOne')->willReturn(null)->with(
            ['_id' => $storageDocument['_id']],
            $storageDocumentTest
        );
        $mongo = new Mongo($collection);
        $mongo->setValue('a/b/c', false);
    }

}

