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

class MagiumRecursiveContextRenderer extends AbstractHelper
{
    function __invoke(array $contexts, $padding = 0)
    {
        $output = sprintf('<ul class="nav nav-pills nav-stacked"%s>', $padding==0?' id="magium-contexts"':'');
        foreach ($contexts as $context) {
            $output .= sprintf(
                '<li
                            class="magium-context magium-requires-cursor"
                            data-context="%s"
                            id="magium-context-%s"
                            style="padding-left: %spx; "><a>%s</a>',
                    htmlspecialchars($context['id']),
                    htmlspecialchars($context['id']),
                    $padding,
                    htmlspecialchars($context['label'])
            );
            if (isset($context['children']) && count($context['children'])) {
                $output .= $this($context['children'], $padding + 10);
            }
            $output .= '</li>';
        }
        $output .= '</ul>';
        return $output;
    }


}
