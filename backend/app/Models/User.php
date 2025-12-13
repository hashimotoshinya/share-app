<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'firebase_uid',
        'name',
        'email',
    ];

    protected $hidden = [
        // no hidden fields for now; passwords/remember tokens are handled by Firebase
    ];

    protected function casts(): array
    {
        return [
            // email verification handled by Firebase; no local casts required here
        ];
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }
}