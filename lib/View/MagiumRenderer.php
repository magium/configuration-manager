<?php
/**
 * Created by PhpStorm.
 * User: kschr
 * Date: 3/20/2017
 * Time: 4:54 PM
 */

namespace Magium\Configuration\View;

use Zend\Form\Element;
use Zend\Form\View\Helper\FormElement;
use Zend\Form\View\Helper\FormInput;
use Zend\View\Helper\AbstractHelper;

class MagiumRenderer extends AbstractHelper
{
    function __invoke($section, $group, $id, $options)
    {
        $type = 'text';
        $options = ['value' => $options['value']];
        if (isset($options['type'])) {
            $type = $options['type'];
        }
        $viewClass = 'Zend\Form\View\Helper\Form' . ucfirst(strtolower($type));
        $formClass = 'Zend\Form\Element\\' . ucfirst(strtolower($type));
        $reflectionClass = new \ReflectionClass($viewClass);
        if (!$reflectionClass->isSubclassOf(AbstractHelper::class)) {
            throw new InvalidViewConfigurationException('Invalid setting input type');
        }

        $instance = $reflectionClass->newInstance();
        if ($instance instanceof FormInput) {
            $instance->setView($this->getView());
            $formInstance = new \ReflectionClass($formClass);
            $name = sprintf('%s_%s_%s', $section, $group, $id);
            $formElement = $formInstance->newInstance($name, $options);
            /* @var $formElement Element */
            $formElement->setValue($options['value']);
            $formElement->setAttribute('id', $id);
            $output = $instance->render($formElement);
            return $output;
        }
        throw new InvalidViewConfigurationException('Invalid form input type');
    }


}
