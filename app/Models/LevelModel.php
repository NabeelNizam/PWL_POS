<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LevelModel extends Model
{
    protected $table = 'm_level';
    public function level(): BelongsTo
    {
        return $this->belongsTo(UserModel::class);
    }
}
