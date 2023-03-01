<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeatmapSet extends Model
{
    use HasFactory;

    protected $table = 'beatmapsets';

    protected $fillable = [
        'id', 'beatmapset_id', 'creator_id',
    ];

    protected $dateFormat = \DateTime::ISO8601;
    protected $casts = [
        'date_ranked' => 'datetime:c',
    ];
}
