<?php

namespace App\Models;

use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory;

    protected $fillable = [
        'external_id',
        'title',
        'author',
        'first_publish_year',
        'cover_url',
    ];

    protected function casts(): array
    {
        return [
            'first_publish_year' => 'integer',
        ];
    }
}
