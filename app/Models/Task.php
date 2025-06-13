<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'tasklist',
        'description',
        'deadline',
        'status',
        'user_id'
    ];

    protected $casts = [
        'deadline' => 'date'
    ];

    protected $appends = ['remaining_days'];


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function getRemainingDaysAttribute()
    {
        $now = Carbon::now();
        $deadline = Carbon::parse($this->deadline);
        $diff = $deadline->diffInDays($now, false);

        if ($diff < 0 ) {
            return abs($diff) . ' hari lagi';
        } elseif ($diff == 0) {
            return 'Hari ini';
        } else {
            return 'Sudah lewat ' . $diff . ' hari';
        }
    }


    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }


    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('tasklist', 'LIKE', "%{$search}%")->orWhere('description', 'LIKE', "%{$search}%");
        });
    }
}
