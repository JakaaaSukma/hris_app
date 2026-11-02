<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Member extends Model
{
    use HasFactory;

    /**
     * The connection name for the model.
     * Direct connection to gyokai02 database so changes are automatically 100% synchronized!
     *
     * @var string
     */
    protected $connection = 'gyokai';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'members';

    protected $fillable = [
        'reference',
        'tanggal_daftar',
        'name',
        'email',
        'phone',
        'nik_ktp',
        'tempat_lahir',
        'tanggal_lahir',
        'gender',
        'perusahaan_favorite',
        'status',
        'source',
        'google_sheet_row_id',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tanggal_daftar' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Accessor for age
    public function getAgeAttribute()
    {
        if (!$this->tanggal_lahir) {
            return null;
        }
        return $this->tanggal_lahir->age;
    }

    // Accessor for formatted age (with "tahun")
    public function getUmurAttribute(): string
    {
        if (!$this->tanggal_lahir) {
            return '-';
        }
        return $this->age . ' tahun';
    }

    // Accessor for how long the member has been registered
    public function getWaktuTerdaftarAttribute(): string
    {
        if (!$this->tanggal_daftar) {
            return '-';
        }

        $now = Carbon::now();
        $diff = $now->diff($this->tanggal_daftar);

        $parts = [];
        if ($diff->y > 0) {
            $parts[] = $diff->y . ' tahun';
        }
        if ($diff->m > 0) {
            $parts[] = $diff->m . ' bulan';
        }
        if ($diff->d > 0 && empty($parts)) {
            $parts[] = $diff->d . ' hari';
        }

        return empty($parts) ? 'Hari ini' : implode(' ', $parts);
    }
}
