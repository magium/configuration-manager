<?php

namespace Magium\Configuration\Tests\View;

use Interop\Container\ContainerInterface;
use Magium\Configuration\Config\Repository\ConfigurationRepository;
use Magium\Configuration\File\Context\AbstractContextConfigurationFile;
use Magium\Configuration\MagiumConfigurationFactoryInterface;
use Magium\Configuration\View\ViewConfiguration;
use Magium\Configuration\View\ViewManager;

class SectionTest extends AbstractViewTestCase
{

    public function testOnlySectionsRendered()
    {
        $viewConfiguration = $this->getViewConfiguration();
        $viewManager = $this->getViewManager($viewConfiguration, <<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration">
<section identifier="section1">
    <group identifier="test"></group>
</section>
</magiumConfiguration>
XML
        );


        /* @var $viewManager ViewManager */
        $viewManager->render();
        $content = $this->getViewContent($viewConfiguration);
        $simpleXml = new \SimpleXMLElement($content);
        self::assertXpathExists($simpleXml, '//title');
        self::assertXpathExists($simpleXml, '//nav/a[1]');
        self::assertXpathNotExists($simpleXml, '//nav/a[2]');
    }

    public function testSectionsRendered()
    {
        $viewConfiguration = $this->getViewConfiguration();
        $viewManager = $this->getViewManager($viewConfiguration,<<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration">
<section identifier="section1" label="Section Eins" />
<section identifier="section2" label="Section Zwei" />
</magiumConfiguration>
XML
        );

        /* @var $viewManager ViewManager */
        $viewManager->render();
        $content = $this->getViewContent($viewConfiguration);
        $simpleXml = new \SimpleXMLElement($content);
        self::assertXpathExists($simpleXml, '//title');
        self::assertXpathExists($simpleXml, '//button[@id="magium-rebuild-configuration"]');
        self::assertXpathExists($simpleXml, '//nav/a[1]');
        self::assertXpathExists($simpleXml, '//nav/a[2]');
        self::assertXpathExists($simpleXml, '//nav/a[@data-section="section1" and .="Section Eins"]');
        self::assertXpathExists($simpleXml, '//nav/a[@data-section="section2" and .="Section Zwei"]');
    }

    public function testSectionsRenderedWithoutLabel()
    {
        $viewConfiguration = $this->getViewConfiguration();
        $viewManager = $this->getViewManager($viewConfiguration,<<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration">
<section identifier="section1" />
<section identifier="section2" />
</magiumConfiguration>
XML
        );

        /* @var $viewManager ViewManager */
        $viewManager->render();
        $content = $this->getViewContent($viewConfiguration);
        $simpleXml = new \SimpleXMLElement($content);
        self::assertXpathExists($simpleXml, '//title');
        self::assertXpathExists($simpleXml, '//nav/a[1]');
        self::assertXpathExists($simpleXml, '//nav/a[2]');
        self::assertXpathExists($simpleXml, '//nav/a[@data-section="section1" and .="section1"]');
        self::assertXpathExists($simpleXml, '//nav/a[@data-section="section2" and .="section2"]');
    }

    public function testSectionsNotRendered()
    {

        $viewConfiguration = $this->getViewConfiguration();
        $viewManager = $this->getViewManager($viewConfiguration,<<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration">
<section identifier="section1" label="Section Eins" />
<section identifier="section2" label="Section Zwei" hidden="yes" />
</magiumConfiguration>
XML
        );

        /* @var $viewManager ViewManager */
        $viewManager->render();
        $content = $this->getViewContent($viewConfiguration);
        $simpleXml = new \SimpleXMLElement($content);
        self::assertXpathExists($simpleXml, '//nav/a[@data-section="section1" and .="Section Eins"]');
        self::assertXpathNotExists($simpleXml, '//nav/a[@data-section="section2" and .="Section Zwei"]');
    }

    public function testJqueryRendered()
    {
        $viewConfiguration = $this->getViewConfiguration();
        $viewManager = $this->getViewManager($viewConfiguration,<<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration" />
XML
        );

        /* @var $viewManager ViewManager */
        $viewManager->render();
        $content = $this->getViewContent($viewConfiguration);
        $simpleXml = new \SimpleXMLElement($content);
        self::assertXpathExists($simpleXml, '/html/head/script[1]');
    }

    public function testJqueryNotRendered()
    {
        $viewConfiguration = $this->getViewConfiguration();
        $viewConfiguration->setJqueryUrl(false);
        $viewManager = $this->getViewManager($viewConfiguration,<<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration" />
XML
        );

        /* @var $viewManager ViewManager */
        $viewManager->render();
        $content = $this->getViewContent($viewConfiguration);
        $simpleXml = new \SimpleXMLElement($content);
        self::assertXpathNotExists($simpleXml, '/html/head/script[1]');
    }

    public function testWrapperNotRendered()
    {
        $viewConfiguration = $this->getViewConfiguration();
        $viewConfiguration->setProvideWrapperHtml(false);
        $viewManager = $this->getViewManager($viewConfiguration,<<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration" />
XML
        );

        /* @var $viewManager ViewManager */
        $viewManager->render();
        $content = $this->getViewContent($viewConfiguration);
        $simpleXml = new \SimpleXMLElement(sprintf('<div>%s</div>', $content));
        self::assertXpathNotExists($simpleXml, '//html');
        self::assertXpathNotExists($simpleXml, '//body');
        self::assertXpathExists($simpleXml, '//nav');
        self::assertXpathExists($simpleXml, '//main');
        self::assertXpathExists($simpleXml, '//header');
    }

    /**
     * @param ViewConfiguration $viewConfiguration
     * @param $xml
     * @return ViewManager
     */

    public function getViewManager(ViewConfiguration $viewConfiguration, $xml)
    {
        $viewManager = $this->getMockBuilder(ViewManager::class)->setMethods(['getConfiguration', 'getContextFile'])->setConstructorArgs([
            'viewConfiguration'             => $viewConfiguration,
            'magiumConfigurationFactory'    => $this->createMock(MagiumConfigurationFactoryInterface::class),
            'diContainer'                   => $this->createMock(ContainerInterface::class)
        ])->getMock();
        $viewManager->expects(self::atLeastOnce())->method('getConfiguration')->willReturn(new ConfigurationRepository($xml));
        $viewManager->expects(self::atLeastOnce())->method('getContextFile')->willReturn($this->createMock(AbstractContextConfigurationFile::class));
        return $viewManager;
    }

}
