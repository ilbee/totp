<?php

namespace Ilbee\Totp;

use Base32\Base32;
use Symfony\Component\Security\Core\User\UserInterface;

final class Totp
{
    /**
     * @var string
     */
    private $secret;

    /**
     * @var int
     */
    private $period;

    /**
     * @var string
     */
    private $algorithm;

    /**
     * @var int
     */
    private $digits;

    private const DEFAULT_PERIOD = 30;
    private const DEFAULT_DIGITS = 6;
    private const DEFAULT_ALGORITHM = 'sha1';

    public function __construct(string $secret, array $options = [])
    {
        $this->digits = $options['digits'] ?? self::DEFAULT_DIGITS;
        $this->algorithm = $options['algorithm'] ?? self::DEFAULT_ALGORITHM;
        $this->period = $options['period'] ?? self::DEFAULT_PERIOD;
        $this->secret = $secret;
    }

    public function now(): int
    {
        return $this->generateOTP();
    }

    public function at(int $timestamp): int
    {
        return $this->generateOTP($this->getTimeCode($timestamp));
    }

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

    private function getTimeCode(?int $timestamp = null): int
    {
        if (!$timestamp) {
            $timestamp = time();
        }

        return (int) ($timestamp * 1000) / ($this->getPeriod() * 1000);
    }

    private function generateOTP(?int $input = null): int
    {
        if (!$input) {
            $input = $this->getTimeCode();
        }

        $hash = hash_hmac($this->getAlgorithm(), $this->intToBytestring($input), $this->byteSecret());
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

    private function getAlgorithm(): string
    {
        return $this->algorithm ?? self::DEFAULT_ALGORITHM;
    }

    private function getSecret(): string
    {
        return $this->secret;
    }

    private function getPeriod(): int
    {
        return $this->period ?? self::DEFAULT_PERIOD;
    }

    private function getDigits(): int
    {
        return $this->digits ?? self::DEFAULT_DIGITS;
    }

    private function byteSecret(): string
    {
        return Base32::decode($this->getSecret());
    }

    private function intToBytestring(int $int): string
    {
        $result = [];
        while ($int != 0) {
            $result[] = chr($int & 0xFF);
            $int >>= 8;
        }

        return str_pad(join(array_reverse($result)), 8, "\000", STR_PAD_LEFT);
    }
}
