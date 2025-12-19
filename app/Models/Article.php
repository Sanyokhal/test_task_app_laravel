<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'id',
        'code',
        'brand_id',
        'category',
        'second_category',
        'catalog',
        'name',
        'description',
        'price',
        'warranty',
        'availability',
    ];
}
