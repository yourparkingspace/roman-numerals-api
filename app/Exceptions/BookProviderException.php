<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by a BookProviderInterface implementation when the upstream source fails.
 */
class BookProviderException extends RuntimeException {}
