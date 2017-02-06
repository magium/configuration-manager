<?php

namespace Magium\Configuration\Tests\Config;

use Magium\Configuration\Config\Builder;
use Magium\Configuration\Config\Config;
use Magium\Configuration\Config\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;

class BuilderTest extends TestCase
{

    public function testBuildConfigurationStructureMerges()
    {
        $base = $this->getStructureXml();
        $merge = $this->getMergedStructureXml();
        $builder = $this->getMockConfigurationBuilder(null, 0);

        $builder->mergeStructure($base, $merge);
        $xml = $base->asXML();
        $base->registerXPathNamespace('s', 'http://www.magiumlib.com/Configuration');
        $paths = $base->xpath('/*/s:section[@id="sectionId"]/s:group[@id="groupId"]/s:element[@id="elementId2"]');
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

    protected function getMockConfigurationBuilder($value = null, $atLeast = 1)
    {
        $storageBuilder = $this->getMockBuilder(StorageInterface::class);
        $storage = $storageBuilder->setMethods(['getValue'])->getMock();
        $storage->expects(self::atLeast($atLeast))->method('getValue')->willReturn($value);
        $cacheStorage = $this->getMockBuilder(\Zend\Cache\Storage\StorageInterface::class)->getMock();
        if (!$cacheStorage instanceof \Zend\Cache\Storage\StorageInterface) {
            throw new \Exception('You created the wrong kind of mock, buddy');
        }
        if (!$storage instanceof StorageInterface) {
            throw new \Exception('You created the wrong kind of mock, buddy');
        }

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


}