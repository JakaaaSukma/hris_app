<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Models\UserDevice;
use Illuminate\Support\Facades\Auth;

class EnsureDeviceSessionIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $sessionId = $request->session()->getId();

            // Cek apakah session_id perangkat ini masih terdaftar di user_devices
            $device = UserDevice::where('user_id', $user->id)
                ->where('session_id', $sessionId)
                ->first();

            if (!$device) {
                // Perangkat ini telah diterminasi karena login baru dari perangkat lain telah melampaui batas 3 perangkat
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Sesi login Anda telah berakhir karena akun Anda aktif di lebih dari 3 perangkat lain secara bersamaan.',
                ]);
            }

            // Perbarui waktu aktif terakhir (setiap 5 menit untuk efisiensi query)
            if ($device->last_active_at->diffInMinutes(now()) >= 5) {
                $device->update(['last_active_at' => now()]);
            }
        }

        return $next($request);
    }
}
