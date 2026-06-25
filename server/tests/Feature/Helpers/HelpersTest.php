<?php

namespace Tests\Feature\Helpers;

use App\Helpers\BetterParsedown;
use App\Helpers\ParseProductIds;
use Tests\TestCase;

class HelpersTest extends TestCase
{
    public function test_parse_product_ids_filters_invalid_values()
    {
        $this->assertSame([1, 2, 3], ParseProductIds::parse('1, 2,foo,0,,3'));
    }

    public function test_better_parsedown_escapes_markup_and_adds_link_attributes()
    {
        $html = (new BetterParsedown)->text('<script>alert("x")</script> [site](https://example.com)');

        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="noreferrer"', $html);
    }
}
