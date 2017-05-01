<?php
/**
 * Created by PhpStorm.
 * User: kschr
 * Date: 3/20/2017
 * Time: 4:54 PM
 */

namespace Magium\Configuration\View;

use Zend\Form\Element;
use Zend\Form\ElementInterface;
use Zend\Form\View\Helper\FormElement;
use Zend\Form\View\Helper\FormInput;
use Zend\Form\View\Helper\FormSelect;
use Zend\View\Helper\AbstractHelper;

class MagiumRenderer extends AbstractHelper
{
    function __invoke($section, $group, $id, $options)
    {
        $type = 'text';
        if (isset($options['type'])) {
            $type = $options['type'];
        }
        $value = $options['value'];

        $viewClass = 'Zend\Form\View\Helper\Form' . ucfirst(strtolower($type));
        $formClass = 'Zend\Form\Element\\' . ucfirst(strtolower($type));
        $reflectionClass = new \ReflectionClass($viewClass);
        if (!$reflectionClass->isSubclassOf(AbstractHelper::class)) {
            throw new InvalidViewConfigurationException('Invalid setting input type');
        }

        $instance = $reflectionClass->newInstance();
        $formInstance = new \ReflectionClass($formClass);

        $instance->setView($this->getView());
        $name = sprintf('%s_%s_%s', $section, $group, $id);
        $formElement = $formInstance->newInstance($name, $options);
        /* @var $formElement Element */
        if ($formElement instanceof Element\Select) {
            $formElement->setOptions(['options' => $options['source']]);
        }
        $formElement->setAttribute('class', 'form-control');
        $formElement->setValue($value);
        $formElement->setAttribute('id', $id);
        $output = $instance->render($formElement);
        return $output;
    }


}
