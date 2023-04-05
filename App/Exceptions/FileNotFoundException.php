<?php

namespace App\Exceptions;

use Throwable;

class FileNotFoundException extends \Exception
{
    public function __construct($message = null, $code = 0, Throwable $previous = null)
    {
        if (!$message) {
            $message = 'File not found';
        }

        try {
            if (!file_exists('filename.txt')) {
                throw new FileNotFoundException();
            }
            // код для роботи з файлом
        } catch (FileNotFoundException $e) {
            echo $e->getMessage();
            // обробка помилки, наприклад, виведення повідомлення користувачу
        }
        parent::__construct($message, $code, $previous);
    }
}