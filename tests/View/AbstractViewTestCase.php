<?php

namespace Magium\Configuration\Tests\View;

use Magium\Configuration\View\ViewConfiguration;
use PHPUnit\Framework\TestCase;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Psr7Bridge\Psr7Response;
use Zend\Psr7Bridge\Psr7ServerRequest;

abstract class AbstractViewTestCase extends TestCase
{

    public static function assertXpathExists(\SimpleXMLElement $element, $xpath)
    {
        $node = $element->xpath($xpath);
        self::assertCount(1, $node);
    }
    public static function assertXpathNotExists(\SimpleXMLElement $element, $xpath)
    {
        $node = $element->xpath($xpath);
        self::assertCount(0, $node);
    }

    public function getViewConfiguration()
    {
        $request = Psr7ServerRequest::fromZend(new Request());

        $response = Psr7Response::fromZend(new Response());
        $viewConfiguration = new ViewConfiguration($request, $response);
        return $viewConfiguration;
    }

    public function getViewContent(ViewConfiguration $viewConfiguration)
    {
        $response = $viewConfiguration->getResponse();
        $stream = $response->getBody();
        $stream->rewind();
        $content = $stream->getContents();
        return $content;
    }


}
