<?php

namespace Magium\Configuration\Tests\Config;

use Interop\Container\ContainerInterface;
use Magium\Configuration\Config\Builder;
use Magium\Configuration\Config\Config;
use Magium\Configuration\Config\InvalidConfigurationLocationException;
use Magium\Configuration\Config\InvalidDirectoryException;
use Magium\Configuration\Config\MissingConfigurationException;
use Magium\Configuration\Config\MissingContainerException;
use Magium\Configuration\Config\Storage\StorageInterface;
use Magium\Configuration\Config\UncallableCallbackException;
use Magium\Configuration\File\Configuration\ConfigurationFileRepository;
use Magium\Configuration\File\Configuration\UnsupportedFileTypeException;
use Magium\Configuration\File\InvalidFileException;
use Magium\Configuration\File\Configuration\XmlFile;
use PHPUnit\Framework\TestCase;
use Zend\EventManager\Exception\InvalidCallbackException;

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

    public function testConfigurationFilesPassedIntoConstructor()
    {
        $repository = new ConfigurationFileRepository([__DIR__], [realpath(__DIR__ . '/xml/config-merge-2.xml')]);
        $builder = new Builder(
            $this->getCacheStorageMock(),
            $this->getPersistenceStorageMock(),
            $repository
        );
        $config = $builder->build();
        $value = $config->getValue('general/website/title');
        self::assertEquals('My Homepage', $value);
    }

    public function testInvalidConfigurationFilesPassedIntoConstructorThrowsException()
    {
        $this->expectException(InvalidFileException::class);
        $repository = new ConfigurationFileRepository([__DIR__], [__DIR__  . '/no-location/config-merge-2.xml']);
        new Builder(
            $this->getCacheStorageMock(),
            $this->getPersistenceStorageMock(),
            $repository
        );
    }

    public function testUnsupportedConfigurationFilesPassedIntoConstructorThrowsException()
    {
        $this->expectException(UnsupportedFileTypeException::class);
        $repository = new ConfigurationFileRepository([__DIR__], [realpath('.') . '/not-supported/test.unsupported']);
        new Builder(
            $this->getCacheStorageMock(),
            $this->getPersistenceStorageMock(),
            $repository
        );
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

        $value = $config->getValueFlag('sectionId/groupId/noExists');
        self::assertInternalType('bool', $value);
        self::assertFalse($value);
    }

    public function testConfigOutsideSecureBaseFails()
    {
        $this->expectException(InvalidConfigurationLocationException::class);
        $builder = $this->getMockConfigurationBuilder(null, 0);
        $file = new XmlFile(realpath(__DIR__ . '/xml/config-merge-1.xml'));
        $builder->getConfigurationRepository()->registerConfigurationFile($file);
        $builder->build();
    }

    public function testXmlFileMerge()
    {
        $builder = $this->getMockConfigurationBuilder();
        $base = realpath(__DIR__ . '/xml');
        $repository = $builder->getConfigurationRepository();
        $repository->addSecureBase($base);

        foreach (['/xml/config-merge-1.xml', '/xml/config-merge-2.xml'] as $file) {
            $file = new XmlFile(realpath(__DIR__ . $file));
            $repository->registerConfigurationFile($file);
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

    public function testAddingSecureBaseViaConstructor()
    {
        $repository = new ConfigurationFileRepository([__DIR__]);
        self::assertArraySubset([__DIR__], $repository->getSecureBases());
    }

    public function testAddingInvalidSecureBaseViaConstructorThrowsException()
    {
        $this->expectException(InvalidDirectoryException::class);
        $cacheStorage = $this->getCacheStorageMock();
        $persistenceStorage = $this->getPersistenceStorageMock();
        $repository = new ConfigurationFileRepository(['boogers']);

        new Builder(
            $cacheStorage,
            $persistenceStorage,
            $repository
        );
    }

    public function testFunctionCallback()
    {
        $builder = $this->getMockConfigurationBuilder('some lowercase value');
        $config = new Config('<config />');
        $builder->buildConfigurationObject($this->getFunctionCallbackStructureXml(), $config);
        $value = $config->getValue('section/group/element');
        self::assertNotNull($value);
        self::assertEquals(strtoupper($value), $value); // strtoupper() is what we're expecting
    }

    public function testInvalidFunctionCallbackThrowsException()
    {
        $this->expectException(UncallableCallbackException::class);
        $builder = $this->getMockConfigurationBuilder('some lowercase value');
        $config = new Config('<config />');
        $builder->buildConfigurationObject($this->getFunctionCallbackStructureXml('notexistingfunction'), $config);
    }

    public function testFunctionalityRequiringObjectContainerThrowsExceptionWhenMissing()
    {
        $this->expectException(MissingContainerException::class);
        $builder = $this->getMockConfigurationBuilder('some lowercase value');
        $config = new Config('<config />');
        $builder->buildConfigurationObject($this->getMethodCallbackStructureXml(), $config);
    }

    public function testObjectCallback()
    {
        $container = $this->getMockBuilder(ContainerInterface::class)->setMethods(['get', 'has'])->getMock();
        $container->expects(self::atLeast(1))->method('get')->willReturn(new Callback());
        $builder = $this->getMockConfigurationBuilder('some lowercase value', 1, $container);
        $config = new Config('<config />');
        $builder->buildConfigurationObject($this->getMethodCallbackStructureXml(), $config);
        $value = $config->getValue('section/group/element');
        self::assertNotNull($value);
        self::assertEquals(strtoupper($value), $value); // strtoupper() is what we're expecting
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
        $storage = $storageBuilder->setMethods(['getValue','create', 'setValue'])->getMock();
        if (!$storage instanceof StorageInterface) {
            throw new \Exception('You created the wrong kind of mock, buddy');
        }
        return $storage;
    }

    protected function getMockConfigurationBuilder($value = null, $atLeast = 1, ContainerInterface $container = null)
    {
        $storage = $this->getPersistenceStorageMock();
        $storage->expects(self::atLeast($atLeast))->method('getValue')->willReturn($value);
        $cacheStorage = $this->getCacheStorageMock();

        $builder = new Builder(
            $cacheStorage,
            $storage,
            new ConfigurationFileRepository(),
            $container
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

    protected function getFunctionCallbackStructureXml($callback = 'strtoupper')
    {
        $schemaFile = realpath(__DIR__ . '/../../assets/configuration-element.xsd');
        return new \SimpleXMLElement(<<<XML
<configuration xmlns="http://www.magiumlib.com/Configuration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.magiumlib.com/Configuration $schemaFile">
    <section id="section">
      <group id="group">
          <element id="element" callbackFromStorage="$callback"/>
      </group>
    </section>
</configuration>
XML
        );
    }

    protected function getMethodCallbackStructureXml()
    {
        $class = Callback::class;
        $schemaFile = realpath(__DIR__ . '/../../assets/configuration-element.xsd');
        return new \SimpleXMLElement(<<<XML
<configuration xmlns="http://www.magiumlib.com/Configuration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.magiumlib.com/Configuration $schemaFile">
    <section id="section">
      <group id="group">
          <element id="element" callbackFromStorage="$class::strtoupper" />
      </group>
    </section>
</configuration>
XML
        );
    }


}
