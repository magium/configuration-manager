#!/usr/bin/env php
<?php

require __DIR__.'/../vendor/autoload.php';

$application = new \Symfony\Component\Console\Application();
$commandList = new \Magium\Configuration\Console\Symfony\CommandList();
$commandList->addCommands($application);
$application->run();
