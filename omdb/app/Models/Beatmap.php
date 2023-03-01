<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Beatmap extends Model
{
    use HasFactory;

    protected $primaryKey = 'beatmap_id';
    protected $table = 'beatmaps';

    protected $attributes = [
    ];

    protected $fillable = [
        'id', 'beatmapset_id', 'creator_id',
        'difficulty_name', 'artist', 'title',
        'mode', 'status', 'genre', 'language',
        'star_rating', 'date_ranked',
    ];
}
