<?php

namespace SalemC\TypeScriptifyLaravelModels\Exceptions;

use Exception;

class UnsupportedDatabaseConnection extends Exception {
    /**
     * Construct this class.
     *
     * @param array<string> $supportedDatabaseConnections The supported database connections.
     */
    public function __construct(array $supportedDatabaseConnections) {
        $this->message = 'Your database connection is currently unsupported! The following database connections are supported: ' . implode(', ', $supportedDatabaseConnections);
    }
}
