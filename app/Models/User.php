<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens; // Import ini
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable; // Tambahkan HasApiTokens
    
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
}