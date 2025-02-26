<?php

use Rennes\Scripts\Indexation;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

$index = new Indexation();
$index->run();
