<?php

namespace Ilbee\Totp;

use Base32\Base32;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Represents a Time-based One-Time Password (TOTP) generator.
 *
 * @license MIT License
 * @author Julien PRIGENT
 * @email julien.prigent@dbmail.com
 * @link https://github.com/ilbee/totp The GitHub repository for this library
 */
final class Totp
{
    /**
     * @var string The TOTP secret key.
     */
    private $secret;

    /**
     * @var int The time period (in seconds) for OTP validity.
     */
    private $period;

    /**
     * @var string The hash algorithm used for TOTP generation (e.g., 'sha1', 'sha256', 'sha512').
     */
    private $algorithm;

    /**
     * @var int The number of digits in the OTP (default is 6).
     */
    private $digits;

    private const DEFAULT_PERIOD = 30;
    private const DEFAULT_DIGITS = 6;
    private const DEFAULT_ALGORITHM = 'sha1';

    /**
     * Constructs a TOTP generator with the provided secret and options.
     *
     * @param string $secret The TOTP secret key.
     * @param array $options An associative array of options (optional):
     *                      - 'digits': The number of digits in the OTP (default is 6).
     *                      - 'algorithm': The hash algorithm (default is 'sha1').
     *                      - 'period': The time period for OTP validity (default is 30 seconds).
     */
    public function __construct(string $secret, array $options = [])
    {
        $this->digits = $options['digits'] ?? self::DEFAULT_DIGITS;
        $this->algorithm = $options['algorithm'] ?? self::DEFAULT_ALGORITHM;
        $this->period = $options['period'] ?? self::DEFAULT_PERIOD;
        $this->secret = $secret;
    }

    /**
     * Generates the current One-Time Password (OTP) using the secret key.
     *
     * @return int The current OTP code.
     */
    public function now(): int
    {
        return $this->generateOTP();
    }

    /**
     * Generates an OTP using the secret key and a specific timestamp.
     *
     * @param int $timestamp The timestamp (in seconds) for which to calculate the OTP.
     *
     * @return int The OTP code at the specified timestamp.
     */
    public function at(int $timestamp): int
    {
        return $this->generateOTP($this->getTimeCode($timestamp));
    }

    /**
     * Verifies if the provided OTP matches any of the valid OTPs within the current, previous, or next time period.
     *
     * @param int $totp The OTP code to verify.
     * @return bool True if the OTP is valid, false otherwise.
     */
    public function verify(int $totp): bool
    {
        $timestamp = time();
        $values = [
            $this->now(),
            $this->at($timestamp - $this->getPeriod()),
            $this->at($timestamp + $this->getPeriod())
        ];

        return in_array($totp, $values, true);
    }

    /**
     * Generates the URI for adding the TOTP secret to an authenticator app.
     *
     * @param string          $name The account name or identifier.
     * @param UserInterface|null $user The user (optional) for whom the secret is associated.
     * @return string The TOTP URI.
     */
    public function getUri(string $name, ?UserInterface $user = null): string
    {
        if ($user) {
            $name = sprintf(
                '%s:%s',
                $name,
                $user->getUserIdentifier()
            );
        }

        return sprintf(
            'otpauth://totp/%s?secret=%s&algorithm=%s&digits=%d&period=%d',
            $name,
            $this->getSecret(),
            $this->getAlgorithm(),
            $this->getDigits(),
            $this->getPeriod()
        );
    }

    /**
     * Calculates the TOTP time code based on the provided timestamp (or current time if not specified).
     *
     * @param int|null $timestamp The timestamp (in seconds) for which to calculate the time code.
     * @return int The TOTP time code.
     */
    private function getTimeCode(?int $timestamp = null): int
    {
        if (!$timestamp) {
            $timestamp = time();
        }

        return (int) ($timestamp * 1000) / ($this->getPeriod() * 1000);
    }

    /**
     * Generates a One-Time Password (OTP) using the secret key and the provided input.
     *
     * @param int|null $input The input used to generate the OTP (if null, uses the time-based input).
     * @return int The generated OTP code.
     */
    private function generateOTP(?int $input = null): int
    {
        if (!$input) {
            $input = $this->getTimeCode();
        }

        $hash = hash_hmac($this->getAlgorithm(), $this->intToByteString($input), $this->byteSecret());
        $hmac = [];
        foreach (str_split($hash, 2) as $hex) {
            $hmac[] = hexdec($hex);
        }

        $offset = $hmac[19] & 0xf;
        $code = ($hmac[$offset] & 0x7F) << 24 |
            ($hmac[$offset + 1] & 0xFF) << 16 |
            ($hmac[$offset + 2] & 0xFF) << 8 |
            ($hmac[$offset + 3] & 0xFF);
        return $code % pow(10, $this->getDigits());
    }

    /**
     * Gets the algorithm used for generating TOTP.
     *
     * @return string The TOTP algorithm (e.g., 'sha1', 'sha256', 'sha512').
     */
    private function getAlgorithm(): string
    {
        return $this->algorithm ?? self::DEFAULT_ALGORITHM;
    }

    /**
     * Gets the secret key used for TOTP generation.
     *
     * @return string The TOTP secret key.
     */
    private function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * Gets the time period (in seconds) for TOTP validity.
     *
     * @return int The TOTP period (default is 30 seconds).
     */
    private function getPeriod(): int
    {
        return $this->period ?? self::DEFAULT_PERIOD;
    }

    /**
     * Gets the number of digits in the TOTP code.
     *
     * @return int The number of digits (default is 6).
     */
    private function getDigits(): int
    {
        return $this->digits ?? self::DEFAULT_DIGITS;
    }

    /**
     * Decodes the base32-encoded secret key to obtain the raw byte secret.
     *
     * @return string The raw byte secret.
     */
    private function byteSecret(): string
    {
        return Base32::decode($this->getSecret());
    }

    /**
     * Converts an integer to a byte string.
     *
     * @param int $int The integer to convert.
     * @return string The resulting byte string.
     */
    private function intToByteString(int $int): string
    {
        $result = [];
        while ($int != 0) {
            $result[] = chr($int & 0xFF);
            $int >>= 8;
        }

        return str_pad(join(array_reverse($result)), 8, "\000", STR_PAD_LEFT);
    }

    /**
     * Generates a secret key for Time-based One-Time Password (TOTP) authentication.
     *
     * @param UserInterface $user The user for whom the secret key is being generated.
     * @return array An associative array containing the generated secret key and an array of individual words.
     *               - 'key': The TOTP secret key (encoded and truncated to 16 characters).
     *               - 'words': An array of individual words used to create the secret key.
     */
    public static function generateSecret(UserInterface $user): array
    {
        $words = [];
        while (count($words) < 16) {
            $word = '';
            $size = mt_rand(4, 10);
            for ($i = 0; $i < $size; $i++) {
                $word .= chr(mt_rand(65, 90));
            }

            $words[] = $word;
        }

        $phrase = implode(' ', $words);
        $secret = Base32::encode(hash_hmac('sha512', $phrase, $user->getUserIdentifier(), true));
        $secret = substr(str_replace(['=', '+', '/'], '', $secret), 0, 16);

        return [
            'key'    => $secret,
            'words'  => $words,
        ];
    }
}
