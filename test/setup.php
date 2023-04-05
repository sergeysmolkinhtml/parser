<?php

declare(strict_types=1);

use App\Parsers\Parser;

require_once __DIR__ . '/vendor/autoload.php';

$parser = (new Parser('https://velmart.ua/product-of-week/'))->parse();


