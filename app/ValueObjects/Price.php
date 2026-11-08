<?php

namespace App\ValueObjects;

use Illuminate\Support\Facades\Config;
use InvalidArgumentException;

final readonly class Price
{
    public const int MAX_CENTS = 4294967295;

    private function __construct(private int $cents) {}

    /**
     * @throws InvalidArgumentException
     */
    public static function fromCents(int $cents): self
    {
        if ($cents < 0 || $cents > self::MAX_CENTS) {
            throw new InvalidArgumentException('Cena musí být mezi 0,00 a 42 949 672,95 ' . Config::string('price.currency') . '.');
        }

        return new self($cents);
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function fromInput(string $input): self
    {
        $input = mb_trim($input);

        if (! preg_match(Config::string('price.input_regex'), $input)) {
            throw new InvalidArgumentException('Zadejte nezápornou cenu s nejvýše dvěma desetinnými místy, například 17,45.');
        }

        [$crowns, $cents] = array_pad(explode('.', str_replace(',', '.', $input), 2), 2, '0');

        return self::fromCents((int) $crowns * 100 + (int) mb_str_pad($cents, 2, '0'));
    }

    public function inCents(): int
    {
        return $this->cents;
    }

    public function forInput(): string
    {
        return intdiv($this->cents, 100) . ',' . mb_str_pad((string) ($this->cents % 100), 2, '0', STR_PAD_LEFT);
    }

    public function format(): string
    {
        return Config::string('price.currency') . ' ' . number_format(intdiv($this->cents, 100), 0, ',', ' ')
            . ',' . mb_str_pad((string) ($this->cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
