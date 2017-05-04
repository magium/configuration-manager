<?php

namespace Magium\Configuration\View\Controllers;

use Interop\Container\ContainerInterface;
use Magium\Configuration\Config\BuilderInterface;
use Magium\Configuration\Config\Repository\ConfigInterface;
use Magium\Configuration\Config\Repository\ConfigurationRepository;
use Magium\Configuration\Config\MergedStructure;
use Magium\Configuration\Config\Storage\StorageInterface;
use Magium\Configuration\Source\SourceInterface;
use Magium\Configuration\View\UnableToCreateInstanceException;
use Magium\Configuration\View\ViewConfiguration;
use Psr\Http\Message\ServerRequestInterface;
use Zend\View\Model\ViewModel;

class View implements ControllerInterface
{

    protected $viewConfiguration;
    protected $builder;
    protected $mergedConfiguration;
    protected $storage;
    protected $container;
    protected $context;

    public function __construct(
        ViewConfiguration $viewConfiguration,
        BuilderInterface $builder,
        MergedStructure $mergedConfiguration,
        StorageInterface $storage,
        $context,
        ContainerInterface $container = null
    )
    {
        $this->viewConfiguration = $viewConfiguration;
        $this->mergedConfiguration = $mergedConfiguration;
        $this->builder = $builder;
        $this->container = $container;
        $this->context = $context;
        $this->storage = $storage;
    }

    public function execute(ServerRequestInterface $request)
    {
        $params = $request->getQueryParams();
        if (!isset($params['context'])) {
            $params['context'] = ConfigurationRepository::CONTEXT_DEFAULT;
        }
        $groups = $this->buildSectionArray($params['section']);
        $viewModel = new ViewModel([
            'groups' => $groups,
            'section' => $params['section']
        ]);
        $viewModel->setTemplate($this->viewConfiguration->getViewFile());
        return $viewModel;
    }

    /**
     * @param $class
     * @return SourceInterface
     * @throws UnableToCreateInstanceException
     */

    public function getSource($class)
    {
        if ($this->container instanceof ContainerInterface) {
            if ($this->container->has($class)) {
                return $this->container->get($class);
            }
        }
        $reflectionClass = new \ReflectionClass($class);
        if (!$reflectionClass->implementsInterface(SourceInterface::class)) {
            throw new UnableToCreateInstanceException('Source model must implement ' . SourceInterface::class);
        }
        if (!$reflectionClass->getConstructor() || count($reflectionClass->getConstructor()->getParameters()) == 0) {
            return $reflectionClass->newInstance();
        }
        throw new UnableToCreateInstanceException('If a source model requires constructor parameters a service manager or DI container must be provided');
    }

    public function getStorage()
    {
        return $this->storage;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function buildSectionArray($section)
    {
        $xpath = sprintf('//s:section[@identifier="%s" and not(@hidden="yes")]/s:group[not(@hidden="yes")]/s:element[not(@hidden="yes")]', $section);
        $this->mergedConfiguration->registerXPathNamespace('s', 'http://www.magiumlib.com/Configuration');
        $nodes = $this->mergedConfiguration->xpath($xpath);
        $groups = [];
        foreach ($nodes as $node) {
            /* @var $node \SimpleXMLElement */
            $group = $node->xpath('..')[0];
            $groupIdentifier = $name = (string)$group['identifier'];
            if (!isset($groups[$groupIdentifier])) {
                if (isset($group['label'])) {
                    $name = (string)$group['label'];
                }
                $groups[$groupIdentifier] = [
                    'label' => $name,
                    'children' => []
                ];
            }
            $identifier = $label = (string)$node['identifier'];
            $permittedValues = $source = [];
            $description = '';
            $type = 'text';
            if (isset($node['label'])) {
                $label = (string)$node['label'];
            }
            if (isset($node->description)) {
                $description = (string)$node->description;
            }
            if (isset($node->permittedValues)) {
                foreach ($node->permittedValues->value as $value) {
                    $permittedValues[] = (string)$value;
                }
            }
            if (isset($node['type'])) {
                $type = (string)$node['type'];
            }
            if (isset($node['source'])) {
                $source = $this->getSource((string)$node['source'])->getSourceData();
            }
            $path = $this->generatePath($node);
            $value = $this->getStorage()->getValue($path, $this->getContext());

            $groups[$groupIdentifier]['children'][$identifier] = [
                'path' => $path,
                'value' => $value,
                'source' => $source,
                'permittedValues' => $permittedValues,
                'type' => $type,
                'description' => $description,
                'label' => $label
            ];
        }
        return $groups;
    }

    protected function generatePath(\SimpleXMLElement $node)
    {
        $element = (string)$node['identifier'];

        $node = $node->xpath('..')[0];
        $group = (string)$node['identifier'];

        $node = $node->xpath('..')[0];
        $section = (string)$node['identifier'];

        return sprintf('%s/%s/%s', $section, $group, $element);
    }
}
