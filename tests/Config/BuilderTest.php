<?php

namespace Magium\Configuration\Tests\Config;

use Interop\Container\ContainerInterface;
use Magium\Configuration\Config\Builder;
use Magium\Configuration\Config\BuilderInterface;
use Magium\Configuration\Config\InsufficientContainerException;
use Magium\Configuration\Config\InvalidArgumentException;
use Magium\Configuration\Config\InvalidConfigurationLocationException;
use Magium\Configuration\Config\InvalidDirectoryException;
use Magium\Configuration\Config\MergedStructure;
use Magium\Configuration\Config\MissingConfigurationException;
use Magium\Configuration\Config\Repository\ConfigInterface;
use Magium\Configuration\Config\Repository\ConfigurationRepository;
use Magium\Configuration\Config\Storage\StorageInterface;
use Magium\Configuration\Config\UncallableCallbackException;
use Magium\Configuration\File\Configuration\ConfigurationFileRepository;
use Magium\Configuration\File\Configuration\UnsupportedFileTypeException;
use Magium\Configuration\File\Configuration\XmlFile;
use Magium\Configuration\File\InvalidFileException;
use Magium\Configuration\InvalidConfigurationException;
use Magium\Configuration\Tests\Container\ModelInjected;
use PHPUnit\Framework\TestCase;

class BuilderTest extends TestCase
{

    protected function tearDown()
    {
        ConfigurationFileRepository::reset();
        parent::tearDown();
    }

    public function testCachePassesThrough()
    {
        $cache = $this->createMock(\Zend\Cache\Storage\StorageInterface::class);

        $builder = new Builder(
            $cache,
            $this->createMock(\Magium\Configuration\Config\Storage\StorageInterface::class),
            $this->createMock(ConfigurationFileRepository::class)
        );
        self::assertInstanceOf(StorageInterface::class, $builder->getStorage());
    }

    public function testBuilderThrowsExceptionForInvalidSettingsFile()
    {
        $this->expectException(InvalidFileException::class);
        $builder = $this->getMockBuilder(Builder::class)->disableOriginalConstructor()->setMethods([
            'getRegisteredConfigurationFiles'
        ])->getMock();
        $builder->expects(self::once())->method('getRegisteredConfigurationFiles')->willReturn(
            ['not an object']
        );
        /** @var $builder Builder */
        $builder->getMergedStructure();
    }

    public function testBuildConfigurationStructureMerges()
    {
        $base = $this->getStructureXml();
        $merge = $this->getMergedStructureXml();
        $builder = $this->getMockConfigurationBuilder(null, 0);

        $builder->mergeStructure($base, $merge);
        $base->registerXPathNamespace('s', 'http://www.magiumlib.com/Configuration');
        $paths = $base->xpath('/*/s:section[@identifier="sectionId"]/s:group[@identifier="groupId"]/s:element[@identifier="elementId2"]');
        self::assertCount(1, $paths);
        self::assertEquals('Test Value 2', (string)$paths[0]->value);
    }

    public function testInvalidConfigurationFileThrowsException()
    {
        $this->expectException(InvalidConfigurationException::class);
        $builder = $this->getMockBuilder(Builder::class)->disableOriginalConstructor()->setMethods(
            ['getMergedStructure']
        )->getMock();
        $builder->expects(self::once())->method('getMergedStructure')->willReturn(null);
        $builder->build();
    }

    public function testEnsureThatElementChildrenAreIncluded()
    {
        $repository = ConfigurationFileRepository::getInstance(
            [__DIR__],
            [
                realpath(__DIR__ . '/xml/config-merge-1.xml'),
                realpath(__DIR__ . '/xml/config-merge-2.xml')
            ]
        );
        $builder = new Builder(
            $this->getCacheStorageMock(),
            $this->getPersistenceStorageMock(),
            $repository
        );
        $merged = $builder->getMergedStructure();
        $merged->registerXPathNamespace('s', 'http://www.magiumlib.com/Configuration');
        $thatThing = $merged->xpath('//s:element[@identifier="title"]/descendant::s:value[@identifier]');
        self::assertCount(0, $thatThing);
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
        $paths = $base->xpath('/*/s:section[@identifier="sectionId2"]/s:group[@identifier="groupId2"]/s:element[@identifier="elementId2"]');
        self::assertCount(1, $paths);
        self::assertEquals('Test Value 2', (string)$paths[0]->value);
    }

    public function testValuesInjectedOnNullData()
    {
        $structure = $this->getStructureXml();
        $config = new ConfigurationRepository('<config />');

        $builder = $this->getMockConfigurationBuilder();
        $builder->buildConfigurationObject($structure, $config);
        $value = (string)$config->sectionId->groupId->elementId;
        self::assertEquals('Test Value', $value);
    }

    public function testStorageValuesInjectedOnNotNullData()
    {
        $structure = $this->getStructureXml();
        $config = new ConfigurationRepository('<config />');

        $builder = $this->getMockConfigurationBuilder('Storage Value');
        $builder->buildConfigurationObject($structure, $config);
        $value = (string)$config->sectionId->groupId->elementId;
        self::assertEquals('Storage Value', $value);
    }

    public function testConfigurationFilesPassedIntoConstructor()
    {
        $repository = ConfigurationFileRepository::getInstance([__DIR__], [realpath(__DIR__ . '/xml/config-merge-2.xml')]);
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
        $repository = ConfigurationFileRepository::getInstance([__DIR__], [__DIR__ . '/no-location/config-merge-2.xml']);
        new Builder(
            $this->getCacheStorageMock(),
            $this->getPersistenceStorageMock(),
            $repository
        );
    }

    public function testUnsupportedConfigurationFilesPassedIntoConstructorThrowsException()
    {
        $this->expectException(UnsupportedFileTypeException::class);
        $repository = ConfigurationFileRepository::getInstance([__DIR__], [realpath('.') . '/not-supported/test.unsupported']);
        new Builder(
            $this->getCacheStorageMock(),
            $this->getPersistenceStorageMock(),
            $repository
        );
    }

    public function testConfigurationObjectResolution()
    {
        $structure = $this->getStructureXml();
        $config = new ConfigurationRepository('<config />');

        $builder = $this->getMockConfigurationBuilder();
        $builder->buildConfigurationObject($structure, $config);

        $value = $config->getValue('sectionId/groupId/elementId');
        self::assertEquals('Test Value', $value);
    }

    public function testConfigurationReturnsNullForNoValue()
    {
        $structure = $this->getStructureXml();
        $config = new ConfigurationRepository('<config />');

        $builder = $this->getMockConfigurationBuilder();
        $builder->buildConfigurationObject($structure, $config);

        $value = $config->getValue('sectionId/groupId/notExists');
        self::assertNull($value);
    }

    public function testConfigurationObjectFlagResolution()
    {
        $structure = $this->getStructureXml();
        $config = new ConfigurationRepository('<config />');

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
        $repository = ConfigurationFileRepository::getInstance([__DIR__]);
        self::assertArraySubset([__DIR__], $repository->getSecureBases());
    }

    public function testAddingInvalidSecureBaseViaConstructorThrowsException()
    {
        $this->expectException(InvalidDirectoryException::class);
        $cacheStorage = $this->getCacheStorageMock();
        $persistenceStorage = $this->getPersistenceStorageMock();
        $repository = ConfigurationFileRepository::getInstance(['boogers']);

        new Builder(
            $cacheStorage,
            $persistenceStorage,
            $repository
        );
    }

    public function testFunctionCallback()
    {
        $builder = $this->getMockConfigurationBuilder('some lowercase value');
        $config = new ConfigurationRepository('<config />');
        $builder->buildConfigurationObject($this->getFunctionCallbackStructureXml(), $config);
        $value = $config->getValue('section/group/element');
        self::assertNotNull($value);
        self::assertEquals(strtoupper($value), $value); // strtoupper() is what we're expecting
    }

    public function testInvalidFunctionCallbackThrowsException()
    {
        $this->expectException(InsufficientContainerException::class);
        $builder = $this->getMockConfigurationBuilder('some lowercase value');
        $config = new ConfigurationRepository('<config />');
        $builder->buildConfigurationObject($this->getFunctionCallbackStructureXml('notexistingfunction'), $config);
    }

    public function testUncallableFunctionCallbackThrowsException()
    {
        $this->expectException(UncallableCallbackException::class);
        $builder = $this->getMockConfigurationBuilder('some lowercase value');
        $config = new ConfigurationRepository('<config />');
        $builder->buildConfigurationObject($this->getFunctionCallbackStructureXml(ModelInjected::class), $config);
    }

    public function testFunctionalityRequiringObjectContainerThrowsExceptionWhenInsufficient()
    {
        $this->expectException(InsufficientContainerException::class);
        $builder = $this->getMockConfigurationBuilder('some lowercase value');
        $config = new ConfigurationRepository('<config />');
        $builder->buildConfigurationObject($this->getMethodCallbackStructureXml(InsufficientCallback::class), $config);
    }

    public function testBuilderAddsNecessaryDefaultsToContainer()
    {
        $builder = $this->getMockConfigurationBuilder('some value', 0);
        $container = $builder->getContainer();
        self::assertTrue($container->has(BuilderInterface::class), 'Missing ' . BuilderInterface::class);
        self::assertTrue($container->has(StorageInterface::class), 'Missing ' . StorageInterface::class);
        self::assertTrue(
            $container->has(ConfigurationFileRepository::class),
            'Missing ' . ConfigurationFileRepository::class
        );

        $file = new XmlFile(realpath(__DIR__ . '/xml/config-merge-1.xml'));
        $builder->getConfigurationRepository()->addSecureBase(__DIR__);
        $builder->getConfigurationRepository()->registerConfigurationFile($file);
        $builder->build();
        self::assertTrue($container->has(ConfigInterface::class), 'Missing ' . ConfigInterface::class);
        self::assertTrue($container->has(MergedStructure::class), 'Missing ' . MergedStructure::class);
    }

    public function testObjectCallback()
    {
        $builder = $this->getMockConfigurationBuilder('some lowercase value', 1);
        $config = new ConfigurationRepository('<config />');
        $builder->buildConfigurationObject($this->getMethodCallbackStructureXml(), $config);
        $value = $config->getValue('section/group/element');
        self::assertNotNull($value);
        self::assertEquals(strtoupper($value), $value); // strtoupper() is what we're expecting
    }

    public function testObjectCallbackWithDefaultValue()
    {
        $builder = $this->getMockConfigurationBuilder();
        $config = new ConfigurationRepository('<config />');
        $builder->buildConfigurationObject(
            $this->getUniqueStructureXml(
                sprintf(
                    'callbackFromStorage="%s"',
                    Callback::class
                )
            ),
            $config
        );
        $value = $config->getValue('sectionId2/groupId2/elementId2');
        self::assertNotNull($value);
        self::assertEquals(strtoupper('Test Value 2'), $value); // strtoupper() is what we're expecting
    }

    public function testSetValueWithRestrictionWorks()
    {
        $config = $this->getStructureXml();
        $builder = $this->getMockBuilder(Builder::class)->disableOriginalConstructor()->setMethods([
            'getMergedStructure', 'getStorage'
        ])->getMock();
        $builder->expects(self::once())->method('getMergedStructure')->willReturn($config);
        $builder->expects(self::once())->method('getStorage')->willReturn($this->createMock(StorageInterface::class));

        $builder->setValue('sectionId/groupId/elementId', 'to blave');
    }

    public function testSetInvalidValueWithRestrictionThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $config = $this->getStructureXml();
        $builder = $this->getMockBuilder(Builder::class)->disableOriginalConstructor()->setMethods([
            'getMergedStructure', 'getStorage'
        ])->getMock();
        $builder->expects(self::once())->method('getMergedStructure')->willReturn($config);
        $builder->expects(self::never())->method('getStorage');

        $builder->setValue('sectionId/groupId/elementId', 'true love');
    }

    public function testSetValueWithOnePathThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $builder = $this->getMockBuilder(Builder::class)->disableOriginalConstructor()->setMethods(null)->getMock();
        $builder->setValue('path', 'value');
    }

    public function testSetValueWithTwoPathsThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $builder = $this->getMockBuilder(Builder::class)->disableOriginalConstructor()->setMethods(null)->getMock();
        $builder->setValue('path/subpath', 'value');
    }

    public function testSetValueWithFourPathsThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $builder = $this->getMockBuilder(Builder::class)->disableOriginalConstructor()->setMethods(null)->getMock();
        $builder->setValue('path/subpath/deet/deedee', 'value');
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
        $storage = $storageBuilder->setMethods(['getValue', 'create', 'setValue'])->getMock();
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
            ConfigurationFileRepository::getInstance(),
            $container
        );
        return $builder;
    }

    protected function getStructureXml()
    {
        $schemaFile = realpath(__DIR__ . '/../../assets/configuration-element.xsd');
        return new MergedStructure(<<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.magiumlib.com/Configuration $schemaFile">
    <section identifier="sectionId">
      <group identifier="groupId">
          <element identifier="elementId">
              <value>Test Value</value>
              <permittedValues>
                <value>Test Value</value>
                <value>to blave</value>
            </permittedValues>
          </element>
          <element identifier="elementFlagId">
              <value>1</value>
          </element>
      </group>
    </section>
</magiumConfiguration>
XML
        );
    }

    protected function getMergedStructureXml()
    {
        $schemaFile = realpath(__DIR__ . '/../../assets/configuration-element.xsd');
        return new MergedStructure(<<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.magiumlib.com/Configuration $schemaFile">
    <section identifier="sectionId">
      <group identifier="groupId">
          <element identifier="elementId2">
              <value>Test Value 2</value>
          </element>
      </group>
    </section>
</magiumConfiguration>
XML
        );
    }


    protected function getUniqueStructureXml($option = '')
    {
        $schemaFile = realpath(__DIR__ . '/../../assets/configuration-element.xsd');
        return new MergedStructure(<<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.magiumlib.com/Configuration $schemaFile">
    <section identifier="sectionId2">
      <group identifier="groupId2">
          <element identifier="elementId2" $option>
              <value>Test Value 2</value>
          </element>
      </group>
    </section>
</magiumConfiguration>
XML
        );
    }

    protected function getFunctionCallbackStructureXml($callback = 'strtoupper')
    {
        $schemaFile = realpath(__DIR__ . '/../../assets/configuration-element.xsd');
        return new MergedStructure(<<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.magiumlib.com/Configuration $schemaFile">
    <section identifier="section">
      <group identifier="group">
          <element identifier="element" callbackFromStorage="$callback"/>
      </group>
    </section>
</magiumConfiguration>
XML
        );
    }

    protected function getMethodCallbackStructureXml($class = null)
    {
        if ($class == null) {
            $class = Callback::class;
        }
        $schemaFile = realpath(__DIR__ . '/../../assets/configuration-element.xsd');
        return new MergedStructure(<<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.magiumlib.com/Configuration $schemaFile">
    <section identifier="section">
      <group identifier="group">
          <element identifier="element" callbackFromStorage="$class" />
      </group>
    </section>
</magiumConfiguration>
XML
        );
    }


}
