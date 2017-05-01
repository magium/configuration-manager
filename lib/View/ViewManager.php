<?php

namespace Magium\Configuration\View;

use Interop\Container\ContainerInterface;
use Magium\Configuration\Config\Repository\ConfigurationRepository;
use Magium\Configuration\MagiumConfigurationFactoryInterface;
use Magium\Configuration\View\Controllers\Layout;
use Magium\Configuration\View\Controllers\View;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver\TemplatePathStack;

class ViewManager
{

    protected $viewConfiguration;
    protected $magiumConfigurationFactory;
    protected $diContainer;
    protected $view;

    public function __construct(
        ViewConfiguration $viewConfiguration,
        MagiumConfigurationFactoryInterface $magiumConfigurationFactory,
        ContainerInterface $diContainer
    )
    {
        $this->viewConfiguration = $viewConfiguration;
        $this->magiumConfigurationFactory = $magiumConfigurationFactory;
        $this->diContainer = $diContainer;
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */

    public function render()
    {
        $request = $this->viewConfiguration->getRequest();
        $params = $request->getQueryParams();

        // This pseudo router section is because I don't want to create yet-another-dependency in the project.
        if ($request->getMethod() == 'GET' && !isset($params['section'])) {
            $layout = new Layout(
                $this->viewConfiguration,
                $this->getConfiguration(),
                $this->getContextFile(),
                $this->magiumConfigurationFactory
            );
            $viewModel = $layout->execute($request);
        } else if ($request->getMethod() == 'GET' && isset($params['section'])) {
            $context = ConfigurationRepository::CONTEXT_DEFAULT;
            if (isset($params['context'])) {
                $context = $params['context'];
            }
            $view = new View(
                $this->viewConfiguration,
                $this->getBuilder(),
                $this->getMergedStructure(),
                $this->getConfiguration($context),
                $this->diContainer
            );
            $viewModel = $view->execute($request);
        } else if ($request->getMethod() == 'POST') {

        }

        $renderer = new PhpRenderer();
        $mRenderer = new MagiumRenderer();
        $mRenderer->setView($renderer);
        $renderer->getHelperPluginManager()->setService('magiumRenderer', $mRenderer);
        $renderer->setResolver(new TemplatePathStack(['script_paths' => [$this->viewConfiguration->getViewDirectory()]]));
        $content = $renderer->render($viewModel);
        $this->viewConfiguration->getResponse()->getBody()->write($content);
        return $this->viewConfiguration->getResponse();
    }

    public function getBuilder()
    {
        return $this->magiumConfigurationFactory->getBuilder();
    }

    public function getConfiguration($context = ConfigurationRepository::CONTEXT_DEFAULT)
    {
        return $this->magiumConfigurationFactory->getManager()->getConfiguration($context);
    }

    public function getMergedStructure()
    {
        return $this->magiumConfigurationFactory->getBuilder()->getMergedStructure();
    }

    public function getContextFile()
    {
        return $this->magiumConfigurationFactory->getContextFile();
    }



}
