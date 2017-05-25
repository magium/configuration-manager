<?php

namespace Magium\Configuration\Tests\View;

use Interop\Container\ContainerInterface;
use Magium\Configuration\Config\BuilderInterface;
use Magium\Configuration\Config\MergedStructure;
use Magium\Configuration\Config\Repository\ConfigurationRepository;
use Magium\Configuration\Config\Storage\StorageInterface;
use Magium\Configuration\MagiumConfigurationFactoryInterface;
use Magium\Configuration\View\FrontController;
use Psr\Http\Message\ServerRequestInterface;

class ViewTest extends AbstractViewTestCase
{

    public function testViewRendering()
    {
        $viewConfiguration = $this->getViewConfiguration(['section' => 'section1']);
        $viewManager = $this->getMockBuilder(FrontController::class)->setMethods([
            'getConfiguration', 'getMergedStructure', 'getBuilder', 'getStorage'
        ])->setConstructorArgs([
            'viewConfiguration'             => $viewConfiguration,
            'magiumConfigurationFactory'    => $this->createMock(MagiumConfigurationFactoryInterface::class),
            'diConfiguration'               => $this->createMock(ContainerInterface::class)
        ])->getMock();

        $viewManager->expects(self::atLeastOnce())->method('getMergedStructure')->willReturn(new MergedStructure(<<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration">
<section identifier="section1" label="Section">
    <group identifier="test" label="Test">
        <element identifier="element" label="Element">
            <description>This is a description</description>
        </element>
        <element identifier="permitted" type="select">
            <permittedValues>
                <value>yes</value>
                <value>no</value>
            </permittedValues>
        </element>
    </group>
</section>
</magiumConfiguration>
XML
        ));
        $viewManager->method('getBuilder')->willReturn($this->createMock(BuilderInterface::class));
        $storage = $this->getMockBuilder(StorageInterface::class)->setMethods(
            ['getValue', 'setValue', 'create']
        )->getMock();
        // This parallels the XML from below.
        $storage->expects(self::any())->method('getValue')->willReturn('Element Value');
        $viewManager->method('getStorage')->willReturn($storage);
        $viewManager->method('getConfiguration')->willReturn(new ConfigurationRepository(<<<XML
<config>
    <section1>
        <test>
            <element>Element Value</element>
        </test>
    </section1>
</config>
XML
        ));

        /* @var $viewManager FrontController */
        $viewManager->render();
        $content = $this->getViewContent($viewConfiguration);
        $dom = new \DOMDocument();
        $dom->loadHTML(sprintf('<html><body>%s</body></html>', $content));
        $simpleXml = simplexml_import_dom($dom);
        self::assertXpathNotExists($simpleXml, '//title'); // Make sure the layout isn't called
        self::assertXpathExists($simpleXml, '//h2/span[.="Test"]');
        self::assertXpathExists($simpleXml, '//label[.="Element"]');
        self::assertXpathExists($simpleXml, '//input[@type="text" and @name="section1_test_element"]'); // we do this separately to test if the element exists apart from whether it has a value.
        self::assertXpathExists($simpleXml, '//input[@type="text" and @name="section1_test_element" and @value="Element Value"]');
        self::assertXpathExists($simpleXml, '//select[@name="section1_test_permitted"]');
        self::assertXpathExists($simpleXml, '//select[@name="section1_test_permitted"]/option[.="yes" and @value="yes"]');
        self::assertXpathExists($simpleXml, '//select[@name="section1_test_permitted"]/option[.="no" and @value="no"]');
    }

    public function getViewConfiguration(array $params = [], $method = 'GET')
    {
        $configuration = parent::getViewConfiguration();
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn($method);
        $request->method('getQueryParams')->willReturn($params);
        $configuration->setRequest($request);
        return $configuration;
    }

}
