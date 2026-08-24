<?php

namespace Tests\Unit;

use App\Support\Formatters;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class FormattersTest extends TestCase
{
    public function test_formats_cpf(): void
    {
        $this->assertSame('123.456.789-09', Formatters::cpf('12345678909'));
    }

    public function test_returns_original_cpf_when_length_is_wrong(): void
    {
        $this->assertSame('123', Formatters::cpf('123'));
    }

    public function test_formats_rg(): void
    {
        $this->assertSame('12.232.343-4', Formatters::rg('122323434'));
    }

    public function test_formats_mobile_phone(): void
    {
        $this->assertSame('(11) 98765-4321', Formatters::phone('11987654321'));
    }

    public function test_formats_landline_phone(): void
    {
        $this->assertSame('(42) 3224-1234', Formatters::phone('4232241234'));
    }

    public function test_formats_date(): void
    {
        $this->assertSame('10/06/2015', Formatters::date(Carbon::parse('2015-06-10')));
    }

    public function test_empty_values_become_empty_strings(): void
    {
        $this->assertSame('', Formatters::cpf(null));
        $this->assertSame('', Formatters::phone(''));
        $this->assertSame('', Formatters::rg(null));
        $this->assertSame('', Formatters::date(null));
    }
}
