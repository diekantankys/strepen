<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

abstract class ApiController extends Controller
{
    public function getItems($modelClass, $query, $request)
    {
        if ($request->has('query')) {
            return ($modelClass.'::search')($query, $request->input('query'));
        }

        return $query;
    }

    public function getLimit($request)
    {
        if ($request->has('limit')) {
            $limit = (int) $request->input('limit');
            if ($limit < 1) {
                $limit = 1;
            }
            if ($limit > 50) {
                $limit = 50;
            }

            return $limit;
        } else {
            return 20;
        }
    }
}
