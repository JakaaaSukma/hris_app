<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'department',
        'user_id',
        'message',
        'attachment_url',
        'attachment_type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
