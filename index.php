<?php

use Rennes\Scripts\Indexation;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

$index = new Indexation();
$index->run();
