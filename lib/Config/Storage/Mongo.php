<?php

namespace Magium\Configuration\Config\Storage;

use Magium\Configuration\Config\Repository\ConfigurationRepository;
use MongoDB\Collection;


class Mongo implements StorageInterface
{
    const TABLE = 'magium_configuration_values';

    private $mongo;

    public function __construct(
        Collection $mongo
    )
    {
        $this->mongo = $mongo;
    }

    public function getCollection()
    {
        return $this->mongo;
    }

    public function getValue($path, $context = ConfigurationRepository::CONTEXT_DEFAULT)
    {
        $document = $this->mongo->findOne([
            'context' => $context
        ]);
        $paths = explode('/', $path);
        if ($document === null) {
            $document = [
                'context' => $context,
                'document'=> []
            ];
        }
        if (isset($document['document'][$paths[0]][$paths[1]][$paths[2]])) {
            return $document['document'][$paths[0]][$paths[1]][$paths[2]];
        }
        return null;
    }

    public function setValue($path, $value, $context = ConfigurationRepository::CONTEXT_DEFAULT)
    {
        $document = $this->mongo->findOne([
            'context' => $context
        ]);
        $paths = explode('/', $path);
        if ($document === null) {
            $document = [
                'context' => $context,
                'document'=> []
            ];
        }
        $document['document'][$paths[0]][$paths[1]][$paths[2]] = $value;
        if (isset($document['_id'])) {
            $this->mongo->replaceOne(['_id' => $document['_id']], $document);
            return;
        }
        $this->mongo->insertOne($document);
    }

    public function create()
    {
        // Not necessary
    }


}
