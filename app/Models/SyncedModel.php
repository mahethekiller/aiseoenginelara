<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncedModel extends Model
{
    protected $primaryKey = 'provider';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['provider', 'models'];

    protected $casts = [
        'models' => 'array',
    ];
}
