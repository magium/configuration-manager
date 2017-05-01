<?php

namespace Magium\Configuration\View\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use Zend\View\Model\ViewModel;

interface ControllerInterface
{

    /**
     * @param ServerRequestInterface $request
     * @return ViewModel
     */

    public function execute(ServerRequestInterface $request);

}
