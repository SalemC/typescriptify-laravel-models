<?php

namespace SalemC\TypeScriptifyLaravelModels\Exceptions;

use Exception;

class InvalidModelException extends Exception {
    /**
     * Construct this class.
     */
    public function __construct() {
        $this->message = 'That\'s not a valid Model!';
    }
}
