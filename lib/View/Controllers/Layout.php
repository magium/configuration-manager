<?php

namespace Magium\Configuration\View\Controllers;

use Magium\Configuration\Config\Repository\ConfigInterface;
use Magium\Configuration\File\Context\AbstractContextConfigurationFile;
use Magium\Configuration\MagiumConfigurationFactoryInterface;
use Magium\Configuration\View\ViewConfiguration;
use Psr\Http\Message\ServerRequestInterface;
use Zend\View\Model\ViewModel;

class Layout implements ControllerInterface
{

    protected $viewConfiguration;
    protected $configuration;
    protected $contextFile;
    protected $jqueryScript;
    protected $factory;

    public function __construct(
        ViewConfiguration $viewConfiguration,
        ConfigInterface $config,
        AbstractContextConfigurationFile $contextFile,
        MagiumConfigurationFactoryInterface $factory
    )
    {
        $this->viewConfiguration = $viewConfiguration;
        $this->configuration = $config;
        $this->contextFile = $contextFile;
        $this->factory = $factory;
    }

    public function execute(ServerRequestInterface $request)
    {
        $viewModel = new ViewModel();
        $viewModel->setVariable('sections', $this->provideSections());
        $viewModel->setVariable('contexts', $this->provideContexts());
        $viewModel->setVariable('provideHtmlWrapper', $this->viewConfiguration->getProvideWrapperHtml());
        if ($this->viewConfiguration->getJqueryUrl()) {
            $viewModel->setVariable('jqueryUrl', $this->viewConfiguration->getJqueryUrl());
        } else {
            $viewModel->setVariable('jqueryUrl', null);
        }
        $viewModel->setTemplate($this->viewConfiguration->getLayoutFile());
        return $viewModel;
    }

    public function getContextFile()
    {
        return $this->contextFile;
    }

    /**
     * @return array
     */

    protected function provideContexts()
    {
        $contexts = $this->getContextFile();
    }

    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @return array A key=>value list of groups
     */

    protected function provideSections()
    {
        $configuration = $this->factory->getBuilder()->getMergedStructure();
//        $configuration = $this->getConfiguration();
        $a = $configuration->asXml();
        $returnSections = [];
        foreach ($configuration->section as $section) {
            if (isset($section['hidden']) && (string)$section['hidden'] == 'yes') {
                continue;
            }
            $sectionId = $sectionName = (string)$section['identifier'];
            if (isset($section['label']))  {
                $sectionName = (string)$section['label'];
            }
            $returnSections[$sectionId] = $sectionName;
        }
        return $returnSections;
    }

}
