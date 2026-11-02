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

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department',
        'sub_department',
        'employee_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function isDirektur(): bool
    {
        return $this->role === 'direktur';
    }

    public function isSekretaris(): bool
    {
        return in_array($this->role, ['sekretaris', 'sekretaris_direktur']);
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isAsistenManager(): bool
    {
        return in_array($this->role, ['asisten_manager', 'asisten manager']);
    }

    public function isKaryawan(): bool
    {
        return $this->role === 'karyawan';
    }

    // Specific Approval Capabilities
    public function canApproveLeaves(): bool
    {
        // Aturan Ketat: Hanya Manager HR atau Direktur Utama yang boleh menyetujui (approve) pengajuan cuti / izin / dispensasi tingkat final HR
        return $this->isDirektur() || ($this->isHR() && $this->isManager());
    }

    public function canApproveDepartmentLeave($model): bool
    {
        // Manager Departemen berhak me-review dan menyetujui izin/cuti anggota di departemennya pada tahap 1
        $myEmpId = $this->employee_id ?? ($this->employee?->id);
        if ($myEmpId && (int)$model->employee_id === (int)$myEmpId) {
            return false; // Anti self-approval
        }

        if ($this->isDirektur()) {
            return true;
        }

        if ($this->isManager()) {
            $userDept = strtolower($this->department ?? '');
            $reqDept = strtolower($model->employee->department ?? '');
            return $userDept !== '' && $userDept === $reqDept;
        }

        return false;
    }

    public function canQuickClock(): bool
    {
        // Aturan: Fitur Presensi Cepat hanya ada di Manager HR dan Direktur
        return $this->isDirektur() || ($this->isHR() && $this->isManager());
    }

    public function canViewCompanyMetrics(): bool
    {
        // Manager HR, Direktur Utama, dan Sekretaris Direktur (monitoring KPI) yang dapat melihat ringkasan metrik operasional perusahaan di dashboard
        return $this->isDirektur() || ($this->isHR() && $this->isManager()) || $this->isSekretaris();
    }

    public function canApproveReimbursements(): bool
    {
        // Hanya Departemen Finance (Manager / Karyawan Finance) atau Direktur Utama
        return $this->isDirektur() || (strtolower($this->department ?? '') === 'finance');
    }

    public function canApproveInventoryRequests(): bool
    {
        // Pengajuan penambahan / perbaikan inventaris GA di-approve oleh Tim Finance, Manager HR, atau Direktur Utama
        return $this->isDirektur() || (strtolower($this->department ?? '') === 'finance') || ($this->isHR() && $this->isManager());
    }

    public function canManageLeaves(): bool
    {
        return $this->canApproveLeaves();
    }

    public function canManageEmployees(): bool
    {
        // Seluruh Departemen HR (Manager & Karyawan HR Murni) serta Direktur berhak menambahkan/mengelola data & akun karyawan.
        // Staf / Karyawan GA dilarang mengakses modul kepegawaian HR.
        if ($this->isDirektur()) {
            return true;
        }
        if ($this->isHR() && $this->isManager()) {
            return true;
        }
        return $this->isHR() && $this->canAccessHRFeatures();
    }

    public function canExportEmployees(): bool
    {
        // Fitur Menarik / Ekspor Data Karyawan: Khusus Direktur Utama dan Manager HR
        return $this->isDirektur() || ($this->isHR() && $this->isManager());
    }

    public function canManagePaklaring(): bool
    {
        // Fitur Pembuatan Paklaring: Khusus Departemen HR (Manager & Staf HR Murni) dan Direktur Utama
        return $this->isDirektur() || ($this->isHR() && $this->canAccessHRFeatures());
    }

    // Department Qualification Capabilities
    public function canAccessDepartment(string $dept): bool
    {
        // Direktur bisa mengakses semua fitur departemen
        if ($this->isDirektur()) {
            return true;
        }

        $target = strtolower(trim($dept));

        if ($target === 'ga') {
            return $this->canAccessGAFeatures();
        }

        if ($target === 'hr') {
            return $this->canAccessHRFeatures();
        }

        return strtolower($this->department ?? '') === $target;
    }

    public function isFinance(): bool
    {
        return $this->canAccessDepartment('finance');
    }

    public function isBisnisDevelopment(): bool
    {
        return $this->canAccessDepartment('bisnis development');
    }

    public function isHR(): bool
    {
        return strtolower($this->department ?? '') === 'hr';
    }

    public function isGA(): bool
    {
        // Apakah user merupakan bagian dari divisi General Affairs (sub_department GA atau department GA)
        return strtoupper($this->sub_department ?? '') === 'GA' || strtolower($this->department ?? '') === 'ga';
    }

    public function isPureHR(): bool
    {
        // Karyawan/Staf HR murni yang bukan berasal dari divisi GA
        return $this->isHR() && strtoupper($this->sub_department ?? '') !== 'GA';
    }

    /**
     * Hak akses fitur-fitur General Affairs (Aset & Inventaris, Tiket Fasilitas, dsb.)
     * Aturan: Karyawan departemen GA bisa akses; Karyawan HR TIDAK BISA akses.
     */
    public function canAccessGAFeatures(): bool
    {
        if ($this->isDirektur()) {
            return true;
        }

        // Manager HR memimpin gabungan departemen HR & GA
        if ($this->isHR() && $this->isManager()) {
            return true;
        }

        // Asisten Manager GA (eks-Manager GA)
        if ($this->isAsistenManager() && $this->isGA()) {
            return true;
        }

        // Karyawan eks-GA
        if ($this->isGA()) {
            return true;
        }

        // Karyawan HR murni DILARANG mengakses fitur-fitur GA
        return false;
    }

    /**
     * Hak akses fitur-fitur HR murni (Direktori Pegawai, Rekrutmen, Offboarding, Paklaring)
     * Aturan: Karyawan departemen GA HANYA bisa akses fitur GA saja (DILARANG akses fitur HR).
     */
    public function canAccessHRFeatures(): bool
    {
        if ($this->isDirektur()) {
            return true;
        }

        // Manager HR berhak penuh
        if ($this->isHR() && $this->isManager()) {
            return true;
        }

        // Karyawan eks-GA HANYA bisa akses fitur GA saja (Dilarang akses HR)
        if ($this->isGA()) {
            return false;
        }

        // Asisten Manager GA difokuskan pada divisi GA
        if ($this->isAsistenManager()) {
            return false;
        }

        // Karyawan HR murni
        return $this->isHR();
    }

    public function canControlGA(): bool
    {
        // Direktur, Manager HR yang mengawasi GA, Asisten Manager GA, atau Staf operasional GA
        return $this->isDirektur() 
            || ($this->isHR() && $this->isManager()) 
            || ($this->isAsistenManager() && $this->isGA()) 
            || $this->isGA();
    }

    public function isResepsionis(): bool
    {
        return strtolower($this->department ?? '') === 'resepsionis';
    }
}

