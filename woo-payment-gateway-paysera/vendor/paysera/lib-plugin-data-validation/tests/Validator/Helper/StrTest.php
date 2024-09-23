<?php

declare(strict_types=1);

namespace Paysera\DataValidator\Tests\Validator\Helper;

use Paysera\DataValidator\Validator\Helper\Str;
use PHPUnit\Framework\TestCase;

class StrTest extends TestCase
{
    public function testPrettifyAttributeName()
    {
        $example = "some.*example_string.with replaced.*symbols";

        $this->assertEquals(
            'Someexample string with replacedsymbols',
            Str::prettifyAttributeName($example)
        );
    }
}
