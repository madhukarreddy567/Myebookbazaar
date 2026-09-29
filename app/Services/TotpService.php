<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Hash;

/**
 * Phase 6: TOTP two-factor authentication (RFC 6238, 6 digits, 30s
 * steps, SHA-1). Dependency-free except the QR renderer.
 *
 * Secrets are base32 strings. The Admin model encrypts the secret with
 * Laravel Crypt before it touches the database.
 */
class TotpService
{
    public const DIGITS = 6;
    public const PERIOD = 30;
    public const ISSUER = 'MyEbookBazaar';

    /**
     * Random base32 secret (160 bits — the RFC 4226 recommendation).
     */
    public function generateSecret(int $bytes = 20): string
    {
        return $this->base32Encode(random_bytes($bytes));
    }

    public function provisioningUri(string $secret, string $accountName): string
    {
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => self::ISSUER,
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ]);

        return 'otpauth://totp/'.rawurlencode(self::ISSUER.':'.$accountName).'?'.$params;
    }

    /**
     * Inline SVG QR code for the provisioning URI. Rendered server-side
     * so the secret never leaves the building in a third-party URL.
     */
    public function qrCodeSvg(string $uri, int $size = 220): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new SvgImageBackEnd()
        );

        return (new Writer($renderer))->writeString($uri);
    }

    /**
     * Verify a 6-digit code. Accepts the current, previous and next
     * 30-second window to tolerate small clock skew.
     */
    public function verify(string $secret, string $code): bool
    {
        $code = preg_replace('/\D/', '', $code ?? '');

        if (strlen($code) !== self::DIGITS) {
            return false;
        }

        $timestamp = (int) floor(time() / self::PERIOD);

        for ($offset = -1; $offset <= 1; $offset++) {
            if (hash_equals($this->codeFor($secret, $timestamp + $offset), $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The current valid code (used by automated tests).
     */
    public function currentCode(string $secret, ?int $at = null): string
    {
        $timestamp = (int) floor(($at ?? time()) / self::PERIOD);

        return $this->codeFor($secret, $timestamp);
    }

    /**
     * Generate $count recovery codes. Returns ['plain' => [...],
     * 'hashed' => [...]]. Plain codes are shown to the admin ONCE and
     * never stored; only bcrypt hashes are persisted.
     */
    public function generateRecoveryCodes(int $count = 10): array
    {
        $plain = [];
        $hashed = [];

        for ($i = 0; $i < $count; $i++) {
            // Human-friendly groups, e.g. "xkq7-2mdp-9qzt".
            $raw = strtolower(rtrim($this->base32Encode(random_bytes(9)), '='));
            $code = substr($raw, 0, 4).'-'.substr($raw, 4, 4).'-'.substr($raw, 8, 4);
            $plain[] = $code;
            $hashed[] = Hash::make($code);
        }

        return ['plain' => $plain, 'hashed' => $hashed];
    }

    /**
     * Check a recovery code against stored hashes. Returns the index of
     * the matching hash, or null.
     */
    public function matchRecoveryCode(array $hashedCodes, string $code): ?int
    {
        $code = strtolower(trim($code));

        foreach ($hashedCodes as $i => $hash) {
            if (Hash::check($code, $hash)) {
                return $i;
            }
        }

        return null;
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    protected function codeFor(string $secret, int $counter): string
    {
        $key = $this->base32Decode($secret);
        // 8-byte big-endian counter.
        $counterBytes = pack('N2', 0, $counter);
        $hmac = hash_hmac('sha1', $counterBytes, $key, true);

        // Dynamic truncation (RFC 4226 §5.3).
        $offset = ord($hmac[19]) & 0x0f;
        $value = ((ord($hmac[$offset]) & 0x7f) << 24)
            | (ord($hmac[$offset + 1]) << 16)
            | (ord($hmac[$offset + 2]) << 8)
            | ord($hmac[$offset + 3]);

        return str_pad((string) ($value % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    protected function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';

        foreach (str_split($data) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $out .= $alphabet[bindec($chunk)];
        }

        return $out;
    }

    protected function base32Decode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $data = strtoupper(rtrim($data, '='));
        $bits = '';

        foreach (str_split($data) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos === false) {
                throw new \InvalidArgumentException('Invalid base32 character.');
            }
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) < 8) {
                break;
            }
            $out .= chr(bindec($byte));
        }

        return $out;
    }
}
