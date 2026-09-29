<?php

namespace App\Validation;

use Illuminate\Validation\Validator;

/**
 * Application validator with the upstream security fix for
 * GHSA-5vg9-5847-vvmq / CVE-2026-48019 (CRLF injection in the default
 * `email` rule) backported, because no patched Laravel 11 release exists
 * (fixes only shipped in 12.60.0+ / 13.10.0+ and this project stays on 11.x).
 *
 * The default rule lets carriage-return / line-feed characters through;
 * combined with Symfony Mailer that allows header injection whenever a
 * user-supplied address reaches the mail transport (contact form reply-to,
 * registration, password reset). We reject CR/LF up front, exactly as the
 * upstream patch does.
 */
class SafeValidator extends Validator
{
    public function validateEmail($attribute, $value, $parameters)
    {
        if (is_string($value) && (str_contains($value, "\r") || str_contains($value, "\n"))) {
            return false;
        }

        return parent::validateEmail($attribute, $value, $parameters);
    }
}
