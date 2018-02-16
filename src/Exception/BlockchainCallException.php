<?php
namespace BTCZ\Insight\Exception;

class BlockchainCallException extends \Exception
{
    public function __construct($message = "Blockchain call exception.", $code = 0)
    {
        parent::__construct($message, $code);
    }
}