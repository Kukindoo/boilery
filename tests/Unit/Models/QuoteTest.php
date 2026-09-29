<?php

namespace Tests\Unit\Models;

use App\Models\Quote;
use Tests\Unit\UnitTestCase;

class QuoteTest extends UnitTestCase
{
    public function test_new_quote_is_not_closed(): void
    {
        $quote = Quote::factory()->make();

        static::assertFalse($quote->isClosed());
    }

    public function test_contacted_quote_is_not_closed(): void
    {
        $quote = Quote::factory()->contacted()->make();

        static::assertFalse($quote->isClosed());
    }

    public function test_accepted_quote_is_closed(): void
    {
        $quote = Quote::factory()->accepted()->make();

        static::assertTrue($quote->isClosed());
    }

    public function test_declined_quote_is_closed(): void
    {
        $quote = Quote::factory()->rejected()->make();

        static::assertTrue($quote->isClosed());
    }
}
