<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'id';

    protected $attributes = [
        'banned' => false,
        'custom_ratings' => '{}',
    ];

    protected $fillable = [
        'id', 'username', 'access_token', 'refresh_token'
    ];

    public function username() {
    }
}
