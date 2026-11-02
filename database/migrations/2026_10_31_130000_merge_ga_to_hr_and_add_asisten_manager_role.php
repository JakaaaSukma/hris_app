<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Modifikasi kolom 'role' pada tabel 'users' menjadi VARCHAR(30) agar mendukung role 'asisten_manager'
        DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(30) NOT NULL DEFAULT 'karyawan'");

        // 2. Tambahkan kolom 'sub_department' pada tabel 'users' jika belum ada
        if (!Schema::hasColumn('users', 'sub_department')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('sub_department', 50)->nullable()->after('department');
            });
        }

        // 3. Tambahkan kolom 'sub_department' pada tabel 'employees' jika belum ada
        if (!Schema::hasColumn('employees', 'sub_department')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('sub_department', 50)->nullable()->after('department');
            });
        }

        // 4. Data Migration:
        // A. Update pengguna eks-GA:
        //    - Mantan Manager GA (Joko Susanto & Harlan) menjadi role 'asisten_manager', department 'HR', sub_department 'GA'
        DB::table('users')->where('department', 'GA')->where('role', 'manager')->update([
            'role' => 'asisten_manager',
            'department' => 'HR',
            'sub_department' => 'GA',
            'updated_at' => now(),
        ]);

        //    - Karyawan eks-GA menjadi department 'HR', sub_department 'GA'
        DB::table('users')->where('department', 'GA')->update([
            'department' => 'HR',
            'sub_department' => 'GA',
            'updated_at' => now(),
        ]);

        // B. Update pengguna HR sebelumnya: sub_department 'HR'
        DB::table('users')->where('department', 'HR')->whereNull('sub_department')->update([
            'sub_department' => 'HR',
            'updated_at' => now(),
        ]);

        // C. Update data pegawai (employees):
        //    - Mantan Manager GA (Joko Susanto & Harlan)
        DB::table('employees')->where('department', 'GA')->where(function ($q) {
            $q->whereIn('name', ['Joko Susanto', 'Harlan'])
              ->orWhere('role_title', 'like', '%Manager%');
        })->update([
            'role_title' => 'Asisten Manager GA',
            'department' => 'HR',
            'sub_department' => 'GA',
            'updated_at' => now(),
        ]);

        //    - Karyawan eks-GA
        DB::table('employees')->where('department', 'GA')->update([
            'department' => 'HR',
            'sub_department' => 'GA',
            'updated_at' => now(),
        ]);

        //    - Pegawai HR sebelumnya
        DB::table('employees')->where('department', 'HR')->whereNull('sub_department')->update([
            'sub_department' => 'HR',
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert data
        DB::table('users')->where('sub_department', 'GA')->where('role', 'asisten_manager')->update([
            'role' => 'manager',
            'department' => 'GA',
            'sub_department' => null,
        ]);

        DB::table('users')->where('sub_department', 'GA')->update([
            'department' => 'GA',
            'sub_department' => null,
        ]);

        DB::table('employees')->where('sub_department', 'GA')->where('role_title', 'Asisten Manager GA')->update([
            'role_title' => 'General Affairs Manager',
            'department' => 'GA',
            'sub_department' => null,
        ]);

        DB::table('employees')->where('sub_department', 'GA')->update([
            'department' => 'GA',
            'sub_department' => null,
        ]);

        if (Schema::hasColumn('employees', 'sub_department')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('sub_department');
            });
        }

        if (Schema::hasColumn('users', 'sub_department')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('sub_department');
            });
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('direktur', 'manager', 'karyawan') NOT NULL DEFAULT 'karyawan'");
    }
};
