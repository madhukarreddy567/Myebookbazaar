<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Phase 4: thrown when a coupon cannot be consumed (race lost, limits
 * hit between validation and order creation). The caller rolls the
 * order transaction back and shows the message to the customer.
 */
class CouponException extends RuntimeException {}
