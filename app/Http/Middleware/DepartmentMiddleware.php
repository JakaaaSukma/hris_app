<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DepartmentMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$departments): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Direktur punya akses ke seluruh fitur departemen
        if ($user->isDirektur()) {
            return $next($request);
        }

        $userDept = strtolower(trim($user->department ?? ''));

        foreach ($departments as $dept) {
            $targetDept = strtolower(trim($dept));

            // JIKA TARGET FITUR ADALAH GENERAL AFFAIRS (GA):
            if ($targetDept === 'ga') {
                // Catatan Khusus: Karyawan HR TIDAK BISA akses fitur-fitur GA
                if ($user->isKaryawan() && $user->isPureHR()) {
                    abort(403, 'Akses Ditolak: Karyawan departemen HR tidak memiliki izin untuk mengakses fitur-fitur General Affairs (GA).');
                }

                // Pengguna yang memiliki hak akses GA (Direktur, Manager HR, Asisten Manager GA, Staf GA)
                if ($user->canAccessGAFeatures()) {
                    return $next($request);
                }
            }

            // JIKA TARGET FITUR ADALAH HUMAN RESOURCES (HR):
            if ($targetDept === 'hr') {
                // Catatan Khusus: Karyawan departemen GA HANYA bisa akses fitur-fitur GA saja (DILARANG akses fitur HR)
                if ($user->isKaryawan() && $user->isGA()) {
                    abort(403, 'Akses Ditolak: Karyawan departemen GA hanya memiliki izin untuk mengakses fitur-fitur General Affairs (GA).');
                }

                // Asisten Manager GA wewenangnya terfokus pada GA
                if ($user->isAsistenManager() && $user->isGA()) {
                    abort(403, 'Akses Ditolak: Sebagai Asisten Manager GA, wewenang Anda terfokus pada pengelolaan fasilitas dan aset GA.');
                }

                if ($user->canAccessHRFeatures()) {
                    return $next($request);
                }
            }

            if ($userDept === $targetDept) {
                return $next($request);
            }
        }

        abort(403, 'Akses Ditolak: Modul ini hanya diperuntukkan bagi Departemen: ' . strtoupper(implode(' / ', $departments)) . ' atau Direktur.');
    }
}
