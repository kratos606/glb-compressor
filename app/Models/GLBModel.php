<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GLBModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'models'; // Explicitly define table name (optional but good practice if not following conventions exactly)

    // If you want to allow mass assignment for these attributes (using Model::create(), fill(), etc.)
    protected $fillable = [
        'name',
        'path',
        'size',
        'type',
    ];

    // You can add relationships, scopes, accessors, mutators, etc. here later.
}