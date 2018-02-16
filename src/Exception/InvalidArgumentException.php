<?php
namespace BTCZ\Insight\Exception;

class InvalidArgumentException extends \Exception
{
    public function __construct($message = "Invalid insight argument", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
