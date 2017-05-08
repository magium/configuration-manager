<?php

namespace Magium\Configuration\View\Controllers;

use Magium\Configuration\Config\MergedStructure;
use Magium\Configuration\File\Context\AbstractContextConfigurationFile;
use Magium\Configuration\MagiumConfigurationFactoryInterface;
use Magium\Configuration\View\Controllers\Helpers\ContextRepository;
use Magium\Configuration\View\ViewConfiguration;
use Psr\Http\Message\ServerRequestInterface;
use Zend\View\Model\ViewModel;

class Layout implements ControllerInterface
{

    protected $viewConfiguration;
    protected $contextFile;
    protected $jqueryScript;
    protected $factory;
    protected $mergedStructure;

    public function __construct(
        ViewConfiguration $viewConfiguration,
        AbstractContextConfigurationFile $contextFile,
        MagiumConfigurationFactoryInterface $factory,
        MergedStructure $mergedStructure
    )
    {
        $this->viewConfiguration = $viewConfiguration;
        $this->contextFile = $contextFile;
        $this->factory = $factory;
        $this->mergedStructure = $mergedStructure;
    }

    public function execute(ServerRequestInterface $request)
    {
        $viewModel = new ViewModel();
        $viewModel->setVariable('sections', $this->provideSections());
        $viewModel->setVariable('contexts', $this->provideContexts());
        $viewModel->setVariable('provideHtmlWrapper', $this->viewConfiguration->getProvideWrapperHtml());
        $viewModel->setVariable('jqueryUrl', $this->viewConfiguration->getJqueryUrl());
        $viewModel->setVariable('bootstrapCssUrl', $this->viewConfiguration->getBootstrapCssUrl());
        $viewModel->setVariable('bootstrapJsUrl', $this->viewConfiguration->getBootstrapJsUrl());
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

    public function provideContexts()
    {
        return (new ContextRepository($this->contextFile))->getContextHierarchy();
    }

    public function getMergedStructure()
    {
        return $this->mergedStructure;
    }

    /**
     * @return array A key=>value list of groups
     */

    protected function provideSections()
    {
        $configuration = $this->getMergedStructure();
        $returnSections = [];
        foreach ($configuration->section as $section) {
            if (isset($section['hidden']) && (string)$section['hidden'] == 'yes') {
                continue;
            }
            $sectionId = $sectionName = (string)$section['identifier'];
            if (isset($section['label']))  {
                $sectionName = (string)$section['label'];
            }
            $returnSections[$sectionId] = [
                'label' => $sectionName
            ];
            if (isset($section['glyphicon'])) {
                $returnSections[$sectionId]['glyphicon'] = (string)$section['glyphicon'];
            } else if (isset($section['font-awesome'])) {
                $returnSections[$sectionId]['font-awesome'] = (string)$section['font-awesome'];
            }
        }
        return $returnSections;
    }

}
