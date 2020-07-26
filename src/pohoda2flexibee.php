<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

$importer = new \FlexiPeeHP\Pohoda\EngineXSLT();

$xmlFile = $argv[1];


$importer->import($xmlFile);

