<?php

namespace Magium\Configuration\Config;

class ConfigurationRepository extends \SimpleXMLElement implements ConfigInterface
{

    const CONTEXT_DEFAULT = 'default';

    protected static $allowedTrues = [
        true, 'true', 1, '1', 'on', 'yes'
    ];

    public function hasValue($path)
    {
        list($section, $group, $element) = explode('/', $path);
        $xpath = sprintf('/*/%s/%s/%s', $section, $group, $element);
        $element = $this->xpath($xpath);
        if ($element) {
            return true;
        }
        return false;
    }

    public function getValue($path)
    {
        list($section, $group, $element) = explode('/', $path);
        $xpath = sprintf('/*/%s/%s/%s', $section, $group, $element);
        $element = $this->xpath($xpath);
        if (empty($element)) {
            return null;
        }
        $value = (string)$element[0];
        return $value;
    }

    public function getValueFlag($path)
    {
        $value = $this->getValue($path);
        foreach (self::$allowedTrues as $true) {
            if ($value === $true) {
                return true;
            }
        }
        return false;
    }
}
