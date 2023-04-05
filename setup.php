<?php

declare(strict_types=1);

use App\Parsers\Parser;

require_once __DIR__ . '/vendor/autoload.php';

$parser = (new Parser('https://freshmart.com.ua/uk/catalog/action.html'))->parse();