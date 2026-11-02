<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmtpMailer extends Model
{
    use HasFactory;

    protected $connection = 'gyokai';
    protected $table = 'smtp_mailers';

    protected $fillable = [
        'mailer_name',
        'from_address',
        'from_name',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_username',
        'smtp_password',
        'status',
        'daily_limit',
        'daily_sent_count',
        'priority',
        'last_used_at',
        'last_limit_reached_at',
        'cooldown_until',
    ];

    protected $casts = [
        'smtp_port' => 'integer',
        'daily_limit' => 'integer',
        'daily_sent_count' => 'integer',
        'priority' => 'integer',
        'last_used_at' => 'datetime',
        'last_limit_reached_at' => 'datetime',
        'cooldown_until' => 'datetime',
    ];
}
