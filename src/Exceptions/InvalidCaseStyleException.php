<?php

namespace SalemC\TypeScriptifyLaravelModels\Exceptions;

use Exception;

class InvalidCaseStyleException extends Exception {
    /**
     * Construct this class.
     */
    public function __construct() {
        $this->message = 'That\'s not a valid case style!';
    }
}
