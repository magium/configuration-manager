<?php

namespace Magium\Configuration\Config\Repository;

class ConfigurationRepository extends \SimpleXMLElement implements ConfigInterface
{

    public function hasValue($path)
    {
        list($section, $group, $element) = explode('/', $path);
        $xpath = sprintf('/*/%s/%s/%s', $section, $group, $element);
        $element = $this->xpath($xpath);
        return !empty($element);
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
        foreach (self::ALLOWED_TRUES as $true) {
            if ($value === $true) {
                return true;
            }
        }
        return false;
    }

}
