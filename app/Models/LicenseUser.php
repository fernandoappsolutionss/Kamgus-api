<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class LicenseUser extends Pivot
{
    //

    protected $fillable = ["id", "license_id", "user_id", "expiredAt", "created_at", "updated_at"];
}
