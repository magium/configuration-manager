<?php

namespace Magium\Configuration\Tests\View;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Magium\Configuration\Config\MergedStructure;
use Magium\Configuration\File\Context\AbstractContextConfigurationFile;
use Magium\Configuration\MagiumConfigurationFactoryInterface;
use Magium\Configuration\View\FrontController;
use Magium\Configuration\View\ViewConfiguration;

class SectionTest extends AbstractViewTestCase
{

    public function testOnlySectionsRendered()
    {
        $viewConfiguration = $this->getViewConfiguration();
        $viewManager = $this->getFrontController($viewConfiguration, <<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration">
<section identifier="section1">
    <group identifier="test"></group>
</section>
</magiumConfiguration>
XML
        );


        /* @var $viewManager FrontController */
        $result = $viewManager->render();
        $simpleXml = $this->getContent($result);
        self::assertXpathExists($simpleXml, '//title');
        self::assertXpathExists($simpleXml, '//nav/descendant::ul[@id="magium-sections"]/descendant::li[1]');
        self::assertXpathNotExists($simpleXml, '//nav/descendant::ul[@id="magium-sections"]/descendant::li[2]');
    }

    public function testSectionsRendered()
    {
        $viewConfiguration = $this->getViewConfiguration();
        $viewManager = $this->getFrontController($viewConfiguration, <<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration">
<section identifier="section1" label="Section Eins" />
<section identifier="section2" label="Section Zwei" />
</magiumConfiguration>
XML
        );

        /* @var $viewManager FrontController */
        $response = $viewManager->render();
        $simpleXml = $this->getContent($response);
        self::assertXpathExists($simpleXml, '//title');
        self::assertXpathExists($simpleXml, '//button[@id="magium-rebuild-configuration"]');
        self::assertXpathExists($simpleXml, '//nav/descendant::li[1]');
        self::assertXpathExists($simpleXml, '//nav/descendant::li[2]');
        self::assertXpathExists($simpleXml, '//nav/descendant::li[@data-section="section1" and contains(., "Section Eins")]');
        self::assertXpathExists($simpleXml, '//nav/descendant::li[@data-section="section2" and contains(., "Section Zwei")]');
    }

    public function testSectionsRenderedWithoutLabel()
    {
        $viewConfiguration = $this->getViewConfiguration();
        $viewManager = $this->getFrontController($viewConfiguration, <<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration">
<section identifier="section1" />
<section identifier="section2" />
</magiumConfiguration>
XML
        );

        /* @var $viewManager FrontController */
        $response = $viewManager->render();
        $simpleXml = $this->getContent($response);
        self::assertXpathExists($simpleXml, '//title');
        self::assertXpathExists($simpleXml, '//nav/descendant::ul[@id="magium-sections"]/descendant::li[1]');
        self::assertXpathExists($simpleXml, '//nav/descendant::ul[@id="magium-sections"]/descendant::li[2]');
        self::assertXpathExists($simpleXml, '//nav/descendant::ul[@id="magium-sections"]/descendant::li[@data-section="section1" and contains(., "section1")]');
        self::assertXpathExists($simpleXml, '//nav/descendant::ul[@id="magium-sections"]/descendant::li[@data-section="section2" and contains(., "section2")]');
    }

    public function testSectionsNotRendered()
    {

        $viewConfiguration = $this->getViewConfiguration();
        $viewManager = $this->getFrontController($viewConfiguration, <<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration">
<section identifier="section1" label="Section Eins" />
<section identifier="section2" label="Section Zwei" hidden="yes" />
</magiumConfiguration>
XML
        );

        /* @var $viewManager FrontController */
        $response = $viewManager->render();
        $simpleXml = $this->getContent($response);
        self::assertXpathExists($simpleXml, '//nav/descendant::ul[@id="magium-sections"]/descendant::li[@data-section="section1" and contains(., "Section Eins")]');
        self::assertXpathNotExists($simpleXml, '//nav/descendant::ul[@id="magium-sections"]/descendant::li[@data-section="section2" and contains(., "Section Zwei")]');
    }

    public function testGylphiconRendered()
    {

        $viewConfiguration = $this->getViewConfiguration();
        $viewManager = $this->getFrontController($viewConfiguration, <<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration">
<section identifier="section1" label="Section Eins" glyphicon="test"/>
</magiumConfiguration>
XML
        );

        /* @var $viewManager FrontController */
        $response = $viewManager->render();
        $simpleXml = $this->getContent($response);
        self::assertXpathExists($simpleXml, '//nav/descendant::ul[@id="magium-sections"]/descendant::li/i[contains(concat(" ",normalize-space(@class)," "), " glyphicon-test ")]');
    }
    public function testFontAwesomeRendered()
    {

        $viewConfiguration = $this->getViewConfiguration();
        $viewManager = $this->getFrontController($viewConfiguration, <<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration">
<section identifier="section1" label="Section Eins" font-awesome="test"/>
</magiumConfiguration>
XML
        );

        /* @var $viewManager FrontController */
        $response = $viewManager->render();
        $simpleXml = $this->getContent($response);
        self::assertXpathExists($simpleXml, '//nav/descendant::ul[@id="magium-sections"]/descendant::li/i[contains(concat(" ",normalize-space(@class)," "), " fa-test ")]');
    }

    public function testJqueryRendered()
    {
        $viewConfiguration = $this->getViewConfiguration();
        $viewManager = $this->getFrontController($viewConfiguration, <<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration" />
XML
        );

        /* @var $viewManager FrontController */
        $response = $viewManager->render();
        $simpleXml = $this->getContent($response);
        self::assertXpathExists($simpleXml, '/html/head/script[1]');
    }

    public function testScriptsAndCssNotRendered()
    {
        $viewConfiguration = $this->getViewConfiguration();
        $viewConfiguration->setJqueryUrl(false);
        $viewConfiguration->setBootstrapCssUrl(false);
        $viewConfiguration->setBootstrapJsUrl(false);
        $viewManager = $this->getFrontController($viewConfiguration, <<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration" />
XML
        );

        /* @var $viewManager FrontController */
        $response = $viewManager->render();
        $simpleXml = $this->getContent($response);
        self::assertXpathNotExists($simpleXml, '/html/head/script[1]');
        self::assertXpathNotExists($simpleXml, '/html/head/link[1]');
    }


    public function testWrapperNotRendered()
    {
        $viewConfiguration = $this->getViewConfiguration();
        $viewConfiguration->setProvideWrapperHtml(false);
        $viewManager = $this->getFrontController($viewConfiguration, <<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration" />
XML
        );

        /* @var $viewManager FrontController */
        $response = $viewManager->render();
        $body = $response->getBody();
        $body->rewind();
        $contents = $body->getContents();
        self::assertNotContains('<html', $contents);
        self::assertNotContains('<body', $contents);
        self::assertContains('<nav', $contents);
        self::assertContains('<main', $contents);
        self::assertContains('<header', $contents);
    }

    /**
     * @param ViewConfiguration $viewConfiguration
     * @param $xml
     * @return FrontController
     */

    public function getFrontController(ViewConfiguration $viewConfiguration, $xml)
    {
        $frontController = $this->getMockBuilder(FrontController::class)->setMethods(
            [
                'getContextFile',
                'provideContexts',
                'getMergedStructure',
                'getRequest',
                'getViewConfiguration',
                'getConfigurationFactory',
                'getResponse'
            ]
        )->disableOriginalConstructor()->getMock();
        $frontController->expects(self::atLeastOnce())->method('getRequest')->willReturn(
            new ServerRequest('GET', 'http://localhost/')
        );
        $frontController->expects(self::atLeastOnce())->method('getResponse')->willReturn(
            new Response()
        );
        $context = $this->getMockBuilder(AbstractContextConfigurationFile::class)->setMethods(
            ['toXml']
        )->disableOriginalConstructor()->getMock();
        $context->expects(self::any())->method('toXml')->willReturn(
                new \SimpleXMLElement(
                    <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<magiumDefaultContext xmlns="http://www.magiumlib.com/ConfigurationContext">
</magiumDefaultContext>
XML
                )
            );

        $frontController->expects(self::atLeastOnce())->method('getMergedStructure')->willReturn(new MergedStructure($xml));
        $frontController->expects(self::any())->method('provideContexts')->willReturn(['default' => ['label' => 'Default', 'children' => []]]);
        $frontController->expects(self::atLeastOnce())->method('getConfigurationFactory')->willReturn($this->createMock(MagiumConfigurationFactoryInterface::class));
        $frontController->expects(self::atLeastOnce())->method('getViewConfiguration')->willReturn($viewConfiguration);
        $frontController->expects(self::atLeastOnce())->method('getContextFile')->willReturn($context);
        return $frontController;
    }

    public function getContent(Response $response)
    {
        $body = $response->getBody();
        $body->rewind();
        $content = $response->getBody()->getContents();
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($content);
        $simpleXml = simplexml_import_dom($doc);
        return $simpleXml;
    }

}
