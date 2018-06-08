<?php

namespace FiradioPHP\Routing;

use FiradioPHP\F;
use \Exception;

/**
 * ModelException
 *
 * @author asheng
 */
class ModelException extends Exception {

    function __construct(string $message = "", int $code = 0, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }

}
