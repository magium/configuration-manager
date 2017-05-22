<?php

namespace Magium\Configuration\View\Controllers;

use Magium\Configuration\Config\BuilderInterface;
use Magium\Configuration\Config\Repository\ConfigInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\View\Model\JsonModel;

class Save implements ControllerInterface
{

    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';

    protected $builder;

    public function __construct(
        BuilderInterface $builder
    )
    {
        $this->builder = $builder;
    }

    public function execute(ServerRequestInterface $request)
    {
        try {
            $json = $this->read($request);
            $this->process($json);
            return new JsonModel([[
                'status'    => self::STATUS_SUCCESS,
                'message'   => sprintf(
                    '%d setting%s saved in the context: %s',
                        count($json['values']),
                        count($json['values'])>1?'s':'',
                        $json['context'])
            ]]);
        } catch (\Exception $e) {
            return new JsonModel([[
                'status'    => self::STATUS_ERROR,
                'message'   => $e->getMessage()
            ]]);
        }
    }

    public function process(array $json)
    {
        $context = $json['context'];

        foreach ($json['values'] as $path => $value) {
            $this->builder->setValue($path, $value, $context);
        }
    }

    public function read(ServerRequestInterface $request)
    {
        $header = $request->getHeader('content-type');
        if (is_array($header)) {
            $header = array_shift($header);
        }
        if (strpos($header,  'application/json') === false) {
            throw new InvalidRequestException('MCM save operation requires an application/json content type');
        }
        $body = $request->getBody();
        $body->rewind();
        $json  = json_decode($body->getContents(), true);
        if ($json === false) {
            throw new InvalidRequestException('Unable to read JSON string');
        }

        if (!isset($json['values'])) {
            throw new InvalidRequestException('Missing required values key');
        }

        if (!isset($json['context'])) {
            $json['context'] = ConfigInterface::CONTEXT_DEFAULT;
        }

        return $json;
    }

}
