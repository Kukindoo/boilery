<?php

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\Price;
use InvalidArgumentException;
use OverflowException;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\Unit\UnitTestCase;

class PriceTest extends UnitTestCase
{
    public function test_multiplies_and_adds_prices_using_integer_cents(): void
    {
        $line = Price::fromCents(1745)->multipliedBy(3);
        $total = $line->plus(Price::fromCents(1500)->multipliedBy(2));
        $this->assertSame(5235, $line->inCents());
        $this->assertSame(8235, $total->inCents());
        $this->assertSame('CZK 82,35', $total->format());
        $this->assertSame(0, $line->multipliedBy(0)->inCents());
        $this->assertSame('CZK 85 899 345,90', Price::fromCents(4294967295)->multipliedBy(2)->format());
    }

    public function test_rejects_negative_multipliers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Price::fromCents(100)->multipliedBy(-1);
    }

    public function test_rejects_multiplication_overflow(): void
    {
        $this->expectException(OverflowException::class);
        Price::fromCents(2)->multipliedBy(PHP_INT_MAX);
    }

    public function test_rejects_sum_overflow(): void
    {
        $price = Price::fromCents(1)->multipliedBy(PHP_INT_MAX);
        $this->expectException(OverflowException::class);
        $price->plus(Price::fromCents(1));
    }

    public function test_formats_prices_with_the_configured_currency(): void
    {
        config(['price.currency' => 'EUR']);

        $this->assertSame('EUR 17,45', Price::fromCents(1745)->format());
    }

    public function test_range_error_uses_the_configured_currency(): void
    {
        config(['price.currency' => 'EUR']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cena musí být mezi 0,00 a 42 949 672,95 EUR.');

        Price::fromCents(-1);
    }

    public function test_validates_input_with_the_configured_regex(): void
    {
        config(['price.input_regex' => '/^[0-9]{1,8}$/D']);

        $this->assertSame(1700, Price::fromInput('17')->inCents());
        $this->expectException(InvalidArgumentException::class);

        Price::fromInput('17,45');
    }

    #[TestWith(['15', 1500, '15,00', 'CZK 15,00'])]
    #[TestWith(['17,45', 1745, '17,45', 'CZK 17,45'])]
    #[TestWith(['17.45', 1745, '17,45', 'CZK 17,45'])]
    #[TestWith(['17,4', 1740, '17,40', 'CZK 17,40'])]
    #[TestWith([' 0,01 ', 1, '0,01', 'CZK 0,01'])]
    #[TestWith(['0', 0, '0,00', 'CZK 0,00'])]
    #[TestWith(['42949672,95', 4294967295, '42949672,95', 'CZK 42 949 672,95'])]
    public function test_parses_and_formats_czk_without_rounding(string $input, int $cents, string $editable, string $formatted): void
    {
        $price = Price::fromInput($input);

        $this->assertSame($cents, $price->inCents());
        $this->assertSame($editable, $price->forInput());
        $this->assertSame($formatted, Price::fromCents($cents)->format());
    }

    #[TestWith([''])]
    #[TestWith(['-1'])]
    #[TestWith(['12,345'])]
    #[TestWith(['12.345'])]
    #[TestWith(['12,34.5'])]
    #[TestWith(['1e3'])]
    #[TestWith(['CZK 17,45'])]
    #[TestWith(['42949672,96'])]
    #[TestWith(['9999999999999999999999999999'])]
    public function test_rejects_invalid_prices(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        Price::fromInput($input);
    }

    #[TestWith([-1])]
    #[TestWith([4294967296])]
    public function test_rejects_cents_outside_the_supported_range(int $cents): void
    {
        $this->expectException(InvalidArgumentException::class);

        Price::fromCents($cents);
    }
}
