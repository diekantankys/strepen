<?php

namespace App\Helpers;

class ParseProductIds
{
    public static function parse($ids)
    {
        return collect(explode(',', $ids))
            ->map(function ($id) {
                $intId = filter_var($id, FILTER_VALIDATE_INT);

                return $intId !== false ? $intId : null;
            })
            ->filter()
            ->values()
            ->all();
    }
}
