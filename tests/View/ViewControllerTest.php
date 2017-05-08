<?php

namespace Magium\Configuration\Tests\View;

use Interop\Container\ContainerInterface;
use Magium\Configuration\Config\BuilderInterface;
use Magium\Configuration\Config\MergedStructure;
use Magium\Configuration\Config\Repository\ConfigInterface;
use Magium\Configuration\Config\Repository\ConfigurationRepository;
use Magium\Configuration\Config\Storage\StorageInterface;
use Magium\Configuration\Source\Political\CanadianProvinces;
use Magium\Configuration\Tests\View\Source\ConstructorSource;
use Magium\Configuration\Tests\View\Source\ConstructorSourceContainer;
use Magium\Configuration\Tests\View\Source\InvalidSource;
use Magium\Configuration\View\Controllers\View;
use Magium\Configuration\View\UnableToCreateInstanceException;
use Magium\Configuration\View\ViewConfiguration;

class ViewControllerTest extends AbstractViewTestCase
{

    public function testBasicGroupFunction()
    {
        $mergedConfiguration = new MergedStructure(<<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration">
    <section identifier="sectionName">
        <group identifier="groupName">
            <element identifier="elementName">
                <description>This is a description</description>
                <permittedValues>
                    <value>One</value>
                    <value>Two</value>
                </permittedValues>
            </element>
            <element identifier="typedElement" type="datetime" />
        </group>
    </section>
</magiumConfiguration>
XML
        );
        $controller = $this->getBasicController($mergedConfiguration);
        $result = $controller->buildSectionArray('sectionName');
        self::assertArrayHasKey('groupName', $result);
        self::assertArrayHasKey('children', $result['groupName']);
        self::assertArrayHasKey('elementName', $result['groupName']['children']);
        self::assertArrayHasKey('label', $result['groupName']);
        self::assertEquals('groupName', $result['groupName']['label']);
        self::assertEquals('elementName', $result['groupName']['children']['elementName']['label']);
        self::assertEquals('This is a description', $result['groupName']['children']['elementName']['description']);
        self::assertEquals('value', $result['groupName']['children']['elementName']['value']);
        self::assertEquals('text', $result['groupName']['children']['elementName']['type']);
        self::assertEquals('datetime', $result['groupName']['children']['typedElement']['type']);
        self::assertContains('One', $result['groupName']['children']['elementName']['permittedValues']);
        self::assertContains('Two', $result['groupName']['children']['elementName']['permittedValues']);
        self::assertNotEmpty($result['groupName']['children']['elementName']['source']);
    }

    public function testLabelsAreRendered()
    {
        $mergedConfiguration = new MergedStructure(<<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration">
    <section identifier="sectionName">
        <group identifier="groupName" label="Group Name">
            <element identifier="elementName" label="Element Name"/>
        </group>
    </section>
</magiumConfiguration>
XML
        );
        $controller = $this->getBasicController($mergedConfiguration);
        $result = $controller->buildSectionArray('sectionName');
        self::assertEquals('Group Name', $result['groupName']['label']);
        self::assertEquals('Element Name', $result['groupName']['children']['elementName']['label']);
    }


    public function testDescriptionIsRenderedWhenProvided()
    {
        $mergedConfiguration = new MergedStructure(<<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration">
    <section identifier="sectionName">
        <group identifier="groupName" label="Group Name">
            <element identifier="elementName" label="Element Name"/>
        </group>
    </section>
</magiumConfiguration>
XML
        );
        $controller = $this->getBasicController($mergedConfiguration);
        $result = $controller->buildSectionArray('sectionName');
        self::assertEquals('Group Name', $result['groupName']['label']);
        self::assertEquals('Element Name', $result['groupName']['children']['elementName']['label']);
    }

    public function testSourceModelCalled()
    {
        $mergedConfiguration = $this->getSourceConfig(CanadianProvinces::class);
        $controller = $this->getBasicController($mergedConfiguration);
        $result = $controller->buildSectionArray('sectionName');
        self::assertArrayHasKey('MB', $result['groupName']['children']['elementName']['source']);
    }

    public function testSourceWithConstructorThrowsExceptionWhenNoContainerProvided()
    {
        $this->expectException(UnableToCreateInstanceException::class);
        $mergedConfiguration = $this->getSourceConfig(ConstructorSource::class);
        $controller = $this->getBasicController($mergedConfiguration);
        $controller->buildSectionArray('sectionName');
    }

    public function testSourceWithConstructorThrowsExceptionWhenSourceIsInvalid()
    {
        $this->expectException(UnableToCreateInstanceException::class);
        $mergedConfiguration = $this->getSourceConfig(InvalidSource::class);
        $controller = $this->getBasicController($mergedConfiguration);
        $controller->buildSectionArray('sectionName');
    }

    public function testSourceWithConstructorWorksWithContainer()
    {
        $mergedConfiguration = $this->getSourceConfig(ConstructorSource::class);
        $controller = $this->getController(
            $this->getViewConfiguration(),
            $this->createMock(BuilderInterface::class),
            $mergedConfiguration,
            $this->getStorage(),
            new ConstructorSourceContainer()
        );
        $result = $controller->buildSectionArray('sectionName');
        self::assertArrayHasKey('a', $result['groupName']['children']['elementName']['source']);
        self::assertEquals('a', $result['groupName']['children']['elementName']['source']['a']);
    }

    public function getBasicController(MergedStructure $mergedConfiguration)
    {
        return $this->getController(
            $this->getViewConfiguration(),
            $this->createMock(BuilderInterface::class),
            $mergedConfiguration,
            $this->getStorage()
        );
    }

    public function getSourceConfig($source)
    {
        return new MergedStructure(<<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration">
    <section identifier="sectionName">
        <group identifier="groupName">
            <element identifier="elementName" source="{$source}"/>
        </group>
    </section>
</magiumConfiguration>
XML
        );
    }

    public function getController(ViewConfiguration $viewConfiguration,
                                  BuilderInterface $builder,
                                  \SimpleXMLElement $mergedConfiguration,
                                  StorageInterface $storage,
                                  ContainerInterface $container = null)
    {

        $controller = $this->getMockBuilder(View::class)->setConstructorArgs([
            'viewConfiguration' => $viewConfiguration,
            'builder' => $builder,
            'mergedConfiguration' => $mergedConfiguration,
            'storage' => $storage,
            'context' => ConfigInterface::CONTEXT_DEFAULT,
            'container' => $container
        ])->setMethods(null)->getMock();
        /* @var $controller View */
        return $controller;
    }

    public function getStorage()
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('getValue')->willReturn('value');
        return $storage;
    }

    public function getConfiguration()
    {
        return new ConfigurationRepository(<<<XML
<config>
    <sectionName>
        <groupName>
            <elementName>value</elementName>   
        </groupName>
    </sectionName>
</config>
XML
        );
    }
}
