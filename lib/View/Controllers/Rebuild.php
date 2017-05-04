<?php

namespace Magium\Configuration\View\Controllers;

use Interop\Container\ContainerInterface;
use Magium\Configuration\Config\BuilderInterface;
use Magium\Configuration\Config\Repository\ConfigInterface;
use Magium\Configuration\Config\MergedStructure;
use Magium\Configuration\Config\Repository\ConfigurationRepository;
use Magium\Configuration\Config\Storage\StorageInterface;
use Magium\Configuration\File\Context\AbstractContextConfigurationFile;
use Magium\Configuration\View\Controllers\Helpers\ContextRepository;
use Magium\Configuration\View\ViewConfiguration;
use Psr\Http\Message\ServerRequestInterface;
use Zend\View\Model\JsonModel;

class Rebuild implements ControllerInterface
{

    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';

    protected $builder;
    protected $configurationFile;

    public function __construct(
        BuilderInterface $builder,
        AbstractContextConfigurationFile $configurationFile
    )
    {
        $this->builder = $builder;
        $this->configurationFile = $configurationFile;
    }

    public function execute(ServerRequestInterface $request)
    {
        $messages = [];
        try {
            $contexts = $this->getContexts();
            foreach ($contexts as $context => $name) {
                $this->builder->build($context);
                $messages[] = [
                    'status' => self::STATUS_SUCCESS,
                    'message' => sprintf('Context "%s (%s)" rebuilt', $context, $name)
                ];
            }
        } catch (\Exception $e) {
            $messages[] = [
                'status'    => self::STATUS_ERROR,
                'message'   => $e->getMessage()
            ];
        }
        return new JsonModel($messages);
    }

    public function getContexts()
    {
        return (new ContextRepository($this->configurationFile))->getContextList();
    }
}
