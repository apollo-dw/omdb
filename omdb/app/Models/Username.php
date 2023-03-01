<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Username extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usernames';
    protected $primaryKey = 'user_id';

    protected $fillable = [
        'user_id', 'username'
    ];
}
