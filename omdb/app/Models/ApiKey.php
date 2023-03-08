<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
  use HasFactory;

  protected $table = "api_keys";
  protected $primaryKey = "api_key";
  protected $keyType = 'string';

  public function user()
  {
    return $this->hasOne("App\\Models\\OmdbUser", "user_id", "user_id");
  }
}
