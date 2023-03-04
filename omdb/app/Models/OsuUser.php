<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class OsuUser extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'osu_users';
    protected $primaryKey = 'user_id';

    protected $fillable = [
        'user_id', 'username'
    ];

    public function omdb_user()
    {
        return $this->hasOne('App\\Models\\OmdbUser', 'user_id', 'user_id');
    }
}
