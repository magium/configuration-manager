<?php

namespace Magium\Configuration\Config\Repository;

use Magium\Configuration\Config\InvalidArgumentException;

class ArrayConfigurationRepository implements ConfigInterface
{

    protected $data;

    public function __construct($data)
    {
        if (!is_array($data)) {
            throw new InvalidArgumentException('Configuration data must be in an array format');
        }
        $this->data = $data;
    }

    public function getValue($path)
    {
        return $this->xpath($path);
    }

    public function hasValue($path)
    {
        return $this->xpath($path) !== null;
    }

    public function getValueFlag($path)
    {
        $value = $this->getValue($path);
        return in_array($value, self::ALLOWED_TRUES);
    }

    public function xpath($xpath)
    {
        $parts = explode('/', $xpath);
        if (count($parts) != 3) {
            throw new InvalidArgumentException('Path must be in the form of "section/group/element"');
        }
        $process = $this->data;
        foreach ($parts as $part) {
            if (isset($process[$part])) {
                $process = $process[$part];
            } else {
                return null;
            }
        }
        return $process;
    }


}
