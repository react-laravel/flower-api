<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Flower extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'name_en',
        'category',
        'price',
        'original_price',
        'image',
        'description',
        'meaning',
        'care',
        'stock',
        'featured',
        'holiday',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'featured' => 'boolean',
    ];
}
