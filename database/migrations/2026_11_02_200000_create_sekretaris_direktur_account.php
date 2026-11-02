<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Employee;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Pastikan kolom role di users mendukung varchar
        // Buat Employee Sekretaris Direktur jika belum ada
        $employee = Employee::where('email', 'sekretaris@gyokai.com')->first();
        if (!$employee) {
            $employee = Employee::create([
                'nip' => 'NEX-00021',
                'name' => 'Sekretaris Direktur',
                'email' => 'sekretaris@gyokai.com',
                'phone' => '+62 812-9900-1122',
                'role_title' => 'Sekretaris Direktur',
                'department' => 'Executive',
                'sub_department' => 'Sekretariat',
                'employment_type' => 'Tetap (FTE)',
                'status' => 'active',
                'join_date' => '2023-01-01',
                'avatar_url' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150',
                'location' => 'Executive Office (Jakarta)',
                'annual_leave_quota' => 12,
                'annual_leave_used' => 0,
            ]);
        }

        // Buat User Sekretaris Direktur jika belum ada
        $user = User::where('email', 'sekretaris@gyokai.com')->first();
        if (!$user) {
            User::create([
                'name' => 'Sekretaris Direktur',
                'email' => 'sekretaris@gyokai.com',
                'password' => Hash::make(env('DEFAULT_USER_PASSWORD', 'password123')),
                'role' => 'sekretaris',
                'department' => 'Executive',
                'sub_department' => 'Sekretariat',
                'employee_id' => $employee->id,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        User::where('email', 'sekretaris@gyokai.com')->delete();
        Employee::where('email', 'sekretaris@gyokai.com')->delete();
    }
};

