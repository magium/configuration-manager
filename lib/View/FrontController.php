<?php

namespace Magium\Configuration\View;

use Interop\Container\ContainerInterface;
use Magium\Configuration\Config\Repository\ConfigurationRepository;
use Magium\Configuration\MagiumConfigurationFactoryInterface;
use Magium\Configuration\View\Controllers\Layout;
use Magium\Configuration\View\Controllers\Rebuild;
use Magium\Configuration\View\Controllers\Save;
use Magium\Configuration\View\Controllers\View;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver\TemplatePathStack;

class FrontController
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
        $request = $this->getRequest();
        $params = $request->getQueryParams();
        $viewModel = null;

        // This pseudo router section is because I don't want to create yet-another-dependency in the project.
        if ($request->getMethod() == 'POST' && isset($params['rebuild'])) {
            $rebuild = new Rebuild($this->getBuilder(), $this->getContextFile());
            $viewModel = $rebuild->execute($request);
        } else if ($request->getMethod() == 'GET' && !isset($params['section'])) {
            $layout = new Layout(
                $this->getViewConfiguration(),
                $this->getContextFile(),
                $this->getConfigurationFactory(),
                $this->getMergedStructure()
            );
            $viewModel = $layout->execute($request);
        } else if ($request->getMethod() == 'GET' && isset($params['section'])) {
            $context = ConfigurationRepository::CONTEXT_DEFAULT;
            if (isset($params['context'])) {
                $context = $params['context'];
            }
            $view = new View(
                $this->getViewConfiguration(),
                $this->getBuilder(),
                $this->getMergedStructure(),
                $this->getStorage(),
                $context,
                $this->getDiContainer()
            );
            $viewModel = $view->execute($request);
        } else if ($request->getMethod() == 'POST') {
            $save = new Save(
                $this->getConfigurationFactory()->getBuilder()
            );
            $viewModel = $save->execute($request);
        } else {
            $this->getResponse()->withStatus(404);
        }

        $content = '';

        if ($viewModel instanceof JsonModel) {
            $content = $viewModel->serialize();
        } else if ($viewModel instanceof ViewModel) {
            $renderer = new PhpRenderer();
            $mRenderer = new MagiumRenderer();
            $mRenderer->setView($renderer);
            $renderer->getHelperPluginManager()->setService('magiumRenderer', $mRenderer);

            $mRenderer = new MagiumRecursiveContextRenderer();
            $mRenderer->setView($renderer);
            $renderer->getHelperPluginManager()->setService('magiumRecursiveContextRenderer', $mRenderer);

            $renderer->setResolver(new TemplatePathStack(['script_paths' => [$this->getViewConfiguration()->getViewDirectory()]]));
            $content = $renderer->render($viewModel);
        }

        $response = $this->getResponse();
        $response->getBody()->write($content);
        return $response;
    }

    public function getResponse()
    {
        return $this->viewConfiguration->getResponse();
    }

    public function getRequest()
    {
        return $this->viewConfiguration->getRequest();
    }

    public function getConfigurationFactory()
    {
        return $this->magiumConfigurationFactory;
    }

    public function getDiContainer()
    {
        return $this->diContainer;
    }

    public function getViewConfiguration()
    {
        return $this->viewConfiguration;
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

    public function getStorage()
    {
        return $this->magiumConfigurationFactory->getBuilder()->getStorage();
    }

}
