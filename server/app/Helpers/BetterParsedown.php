<?php

namespace App\Helpers;

use Parsedown;

class BetterParsedown extends Parsedown
{
    protected $markupEscaped = true;

    protected function inlineUrl($excerpt)
    {
        $url = parent::inlineUrl($excerpt);
        if ($url != null) {
            $url['element']['attributes']['target'] = '_blank';
            $url['element']['attributes']['rel'] = 'noreferrer';
        }

        return $url;
    }

    protected function inlineLink($excerpt)
    {
        $link = parent::inlineLink($excerpt);
        if ($link != null) {
            $link['element']['attributes']['target'] = '_blank';
            $link['element']['attributes']['rel'] = 'noreferrer';
        }

        return $link;
    }
}
