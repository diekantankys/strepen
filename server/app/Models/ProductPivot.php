<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property float|null $price
 * @property int $amount
 */
class ProductPivot extends Pivot {}
