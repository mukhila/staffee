<?php

namespace App\Services\Import;

use RuntimeException;

/** A row that cannot be imported, with the reason in plain words. */
class RowError extends RuntimeException
{
}
