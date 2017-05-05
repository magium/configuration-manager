<?php

/**
 * This file is largely meant for testing CSS skinning and NOT for production.  To use it run
 */

$mc = realpath (__DIR__ . '/../settings/magium-configuration.xml');
$d = realpath (__DIR__ . '/../../../..');

if ($mc === false || $d === false) {
    echo <<<ERROR
Could not copy magium-configuration.xml file.  This copying is done because this is a test script and the unit 
tests would overwrite this file.  So we copy it on each request.  Make changes to {$mc} if you want to play around
with things.
ERROR;
    exit;
}

copy($mc, $d . '/magium-configuration.xml');

require_once __DIR__ . '/../../../../vendor/autoload.php';

$config = [
    'definition'  => [
        'class' => [
            \Magium\Configuration\Config\Repository\ConfigInterface::class => [
                'instantiator'  => [
                    \Magium\Configuration\MagiumConfigurationFactory::class,
                    'configurationFactory'
                ]
            ],
            \GuzzleHttp\Psr7\ServerRequest::class => [
                'instantiator' => [
                    \GuzzleHttp\Psr7\ServerRequest::class,
                    'fromGlobals'
                ]
            ]
        ]
    ],
    'instance'  => [
        'preferences' => [
            Psr\Http\Message\ServerRequestInterface::class => \GuzzleHttp\Psr7\ServerRequest::class,
            \Psr\Http\Message\MessageInterface::class => \GuzzleHttp\Psr7\Response::class,
            \Magium\Configuration\MagiumConfigurationFactoryInterface::class => \Magium\Configuration\MagiumConfigurationFactory::class
        ]
    ]
];

$di = new \Zend\Di\Di();
$diConfig = new \Zend\Di\Config($config);
$diConfig->configure($di);
$di->instanceManager()->setTypePreference(Interop\Container\ContainerInterface::class, [$di]);

//$request = $di->get(\GuzzleHttp\Psr7\ServerRequest::class);
try {
    $viewManager = $di->get(\Magium\Configuration\View\FrontController::class);
    /** @var $viewManager \Magium\Configuration\View\FrontController */
    $response = $viewManager->render();
    $response = \Zend\Psr7Bridge\Psr7Response::toZend($response);
    header($response->renderStatusLine(), true);
    foreach ($response->getHeaders() as $header) {
        header($header->toString(), true);
    }
    echo $response->getBody();
} catch (Exception $e) {
    header('Content-Type: text/plain', true);
    echo $e->getMessage();
    echo $e->getTraceAsString();
}
