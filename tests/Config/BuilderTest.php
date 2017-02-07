<?php

namespace Magium\Configuration\Tests\Config;

use Magium\Configuration\Config\Builder;
use Magium\Configuration\Config\Config;
use Magium\Configuration\Config\InvalidConfigurationLocationException;
use Magium\Configuration\Config\InvalidDirectoryException;
use Magium\Configuration\Config\MissingConfigurationException;
use Magium\Configuration\Config\Storage\StorageInterface;
use Magium\Configuration\File\InvalidFileException;
use Magium\Configuration\File\XmlFile;
use PHPUnit\Framework\TestCase;

class BuilderTest extends TestCase
{

    public function testBuildConfigurationStructureMerges()
    {
        $base = $this->getStructureXml();
        $merge = $this->getMergedStructureXml();
        $builder = $this->getMockConfigurationBuilder(null, 0);

        $builder->mergeStructure($base, $merge);
        $base->registerXPathNamespace('s', 'http://www.magiumlib.com/Configuration');
        $paths = $base->xpath('/*/s:section[@id="sectionId"]/s:group[@id="groupId"]/s:element[@id="elementId2"]');
        self::assertCount(1, $paths);
        self::assertEquals('Test Value 2', (string)$paths[0]->value);
    }

    public function testBuildConfigurationNewChildren()
    {
        /*
         * This code is almost exactly the same as the previous test except that new XML nodes are created whereas
         * previously they were merged.  This difference is in the result of the $merge variable
         */

        $base = $this->getStructureXml();
        $merge = $this->getUniqueStructureXml();
        $builder = $this->getMockConfigurationBuilder(null, 0);

        $builder->mergeStructure($base, $merge);
        $base->registerXPathNamespace('s', 'http://www.magiumlib.com/Configuration');
        $paths = $base->xpath('/*/s:section[@id="sectionId2"]/s:group[@id="groupId2"]/s:element[@id="elementId2"]');
        self::assertCount(1, $paths);
        self::assertEquals('Test Value 2', (string)$paths[0]->value);
    }

    public function testValuesInjectedOnNullData()
    {
        $structure = $this->getStructureXml();
        $config = new Config('<config />');

        $builder = $this->getMockConfigurationBuilder();
        $builder->buildConfigurationObject($structure, $config);
        $value = (string)$config->sectionId->groupId->elementId;
        self::assertEquals('Test Value', $value);
    }

    public function testStorageValuesInjectedOnNotNullData()
    {
        $structure = $this->getStructureXml();
        $config = new Config('<config />');

        $builder = $this->getMockConfigurationBuilder('Storage Value');
        $builder->buildConfigurationObject($structure, $config);
        $value = (string)$config->sectionId->groupId->elementId;
        self::assertEquals('Storage Value', $value);
    }

    public function testConfigurationObjectResolution()
    {
        $structure = $this->getStructureXml();
        $config = new Config('<config />');

        $builder = $this->getMockConfigurationBuilder();
        $builder->buildConfigurationObject($structure, $config);

        $value = $config->getValue('sectionId/groupId/elementId');
        self::assertEquals('Test Value', $value);
    }

    public function testConfigurationReturnsNullForNoValue()
    {
        $structure = $this->getStructureXml();
        $config = new Config('<config />');

        $builder = $this->getMockConfigurationBuilder();
        $builder->buildConfigurationObject($structure, $config);

        $value = $config->getValue('sectionId/groupId/notExists');
        self::assertNull($value);
    }

    public function testConfigurationObjectFlagResolution()
    {
        $structure = $this->getStructureXml();
        $config = new Config('<config />');

        $builder = $this->getMockConfigurationBuilder();
        $builder->buildConfigurationObject($structure, $config);

        $value = $config->getValueFlag('sectionId/groupId/elementFlagId');
        self::assertInternalType('bool', $value);
        self::assertTrue($value);
    }

    public function testConfigOutsideSecureBaseFails()
    {
        $this->expectException(InvalidConfigurationLocationException::class);
        $builder = $this->getMockConfigurationBuilder(null, 0);
        $file = new XmlFile(realpath(__DIR__ . '/xml/config-merge-1.xml'));
        $builder->registerConfigurationFile($file);
        $builder->build();
    }

    public function testXmlFileMerge()
    {
        $builder = $this->getMockConfigurationBuilder();
        $base = realpath(__DIR__ . '/xml');
        $builder->addSecureBase($base);

        foreach (['/xml/config-merge-1.xml', '/xml/config-merge-2.xml'] as $file) {
            $file = new XmlFile(realpath(__DIR__ . $file));
            $builder->registerConfigurationFile($file);
        }
        $config = $builder->build();
        $title = $config->getValue('general/website/title');
        self::assertEquals('My Homepage', $title);
    }

    public function testBuilderThrowsExceptionWhenNoFilesHaveBeenProvided()
    {
        $this->expectException(MissingConfigurationException::class);
        $builder = $this->getMockConfigurationBuilder(null, 0);
        $builder->build();
    }

    public function testExceptionThrownWhenFileAdapterIsWrongType()
    {
        $this->expectException(InvalidFileException::class);

        $builder = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRegisteredConfigurationFiles'])
            ->getMock();
        $builder->expects(self::atLeast(1))->method('getRegisteredConfigurationFiles')->willReturn(['a string']);
        $builder->build();
    }

    public function testAddingSecureBaseViaConstructor()
    {
        $cacheStorage = $this->getCacheStorageMock();
        $persistenceStorage = $this->getPersistenceStorageMock();

        $builder = new Builder(
            $cacheStorage,
            $persistenceStorage,
            [__DIR__]
        );

        self::assertArraySubset([__DIR__], $builder->getSecureBases());
    }

    public function testAddingInvalidSecureBaseViaConstructorThrowsException()
    {
        $this->expectException(InvalidDirectoryException::class);
        $cacheStorage = $this->getCacheStorageMock();
        $persistenceStorage = $this->getPersistenceStorageMock();

        new Builder(
            $cacheStorage,
            $persistenceStorage,
            ['boogers']
        );
    }

    protected function getCacheStorageMock()
    {
        $cacheStorage = $this->getMockBuilder(\Zend\Cache\Storage\StorageInterface::class)->getMock();
        if (!$cacheStorage instanceof \Zend\Cache\Storage\StorageInterface) {
            throw new \Exception('You created the wrong kind of mock, buddy');
        }
        return $cacheStorage;
    }

    protected function getPersistenceStorageMock()
    {
        $storageBuilder = $this->getMockBuilder(StorageInterface::class);
        $storage = $storageBuilder->setMethods(['getValue'])->getMock();
        if (!$storage instanceof StorageInterface) {
            throw new \Exception('You created the wrong kind of mock, buddy');
        }
        return $storage;
    }

    protected function getMockConfigurationBuilder($value = null, $atLeast = 1)
    {
        $storage = $this->getPersistenceStorageMock();
        $storage->expects(self::atLeast($atLeast))->method('getValue')->willReturn($value);
        $cacheStorage = $this->getCacheStorageMock();

        $builder = new Builder(
            $cacheStorage,
            $storage
        );
        return $builder;
    }

    protected function getStructureXml()
    {
        $schemaFile = realpath(__DIR__ . '/../../assets/configuration-element.xsd');
        return new \SimpleXMLElement(<<<XML
<configuration xmlns="http://www.magiumlib.com/Configuration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.magiumlib.com/Configuration $schemaFile">
    <section id="sectionId">
      <group id="groupId">
          <element id="elementId">
              <value>Test Value</value>
          </element>
          <element id="elementFlagId">
              <value>1</value>
          </element>
      </group>
    </section>
</configuration>
XML
        );
    }

    protected function getMergedStructureXml()
    {
        $schemaFile = realpath(__DIR__ . '/../../assets/configuration-element.xsd');
        return new \SimpleXMLElement(<<<XML
<configuration xmlns="http://www.magiumlib.com/Configuration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.magiumlib.com/Configuration $schemaFile">
    <section id="sectionId">
      <group id="groupId">
          <element id="elementId2">
              <value>Test Value 2</value>
          </element>
      </group>
    </section>
</configuration>
XML
        );
    }


    protected function getUniqueStructureXml()
    {
        $schemaFile = realpath(__DIR__ . '/../../assets/configuration-element.xsd');
        return new \SimpleXMLElement(<<<XML
<configuration xmlns="http://www.magiumlib.com/Configuration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.magiumlib.com/Configuration $schemaFile">
    <section id="sectionId2">
      <group id="groupId2">
          <element id="elementId2">
              <value>Test Value 2</value>
          </element>
      </group>
    </section>
</configuration>
XML
        );
    }


}