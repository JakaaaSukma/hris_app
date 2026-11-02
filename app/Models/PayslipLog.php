<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayslipLog extends Model
{
    use HasFactory;

    protected $connection = 'gyokai';
    protected $table = 'payslip_logs';

    protected $fillable = [
        'user_id',
        'payslip_send_session_id',
        'smtp_mailer_id',
        'smtp_mailer_name',
        'smtp_mailer_address',
        'filename',
        'recipient_email',
        'recipient_name',
        'period',
        'status',
        'error_message',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sendSession()
    {
        return $this->belongsTo(PayslipSendSession::class, 'payslip_send_session_id');
    }

    public function smtpMailer()
    {
        return $this->belongsTo(SmtpMailer::class);
    }
}
