<?php

namespace Magium\Configuration\View\Controllers;

use Interop\Container\ContainerInterface;
use Magium\Configuration\Config\BuilderInterface;
use Magium\Configuration\Config\Repository\ConfigInterface;
use Magium\Configuration\Config\MergedStructure;
use Magium\Configuration\View\ViewConfiguration;
use Psr\Http\Message\ServerRequestInterface;

class Save implements ControllerInterface
{

    protected $viewConfiguration;
    protected $builder;
    protected $mergedConfiguration;
    protected $config;
    protected $container;

    public function __construct(
        ViewConfiguration $viewConfiguration,
        BuilderInterface $builder,
        MergedStructure $mergedConfiguration,
        ConfigInterface $config,
        ContainerInterface $container = null
    )
    {
        $this->viewConfiguration = $viewConfiguration;
        $this->config = $config;
        $this->mergedConfiguration = $mergedConfiguration;
        $this->builder = $builder;
        $this->container = $container;
    }

    public function execute(ServerRequestInterface $request)
    {

    }

}
