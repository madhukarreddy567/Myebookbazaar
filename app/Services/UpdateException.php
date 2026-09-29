<?php

namespace App\Services;

/**
 * Phase 7: thrown when an update cannot proceed or fails mid-flight.
 * The UpdateService always rolls back before this reaches the admin.
 */
class UpdateException extends \RuntimeException {}
