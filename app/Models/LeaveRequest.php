<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'reason', 'start_date', 'end_date', 'status'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
