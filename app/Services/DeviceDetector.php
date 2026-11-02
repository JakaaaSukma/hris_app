<?php

namespace App\Services;

class DeviceDetector
{
    public static function parseDeviceName(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'Perangkat Tidak Dikenal';
        }

        $platform = 'Unknown OS';
        if (preg_match('/windows nt 10/i', $userAgent)) {
            $platform = 'Windows 10/11';
        } elseif (preg_match('/windows nt 6.3/i', $userAgent)) {
            $platform = 'Windows 8.1';
        } elseif (preg_match('/windows nt 6.1/i', $userAgent)) {
            $platform = 'Windows 7';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $platform = 'macOS';
        } elseif (preg_match('/iphone/i', $userAgent)) {
            $platform = 'iPhone (iOS)';
        } elseif (preg_match('/ipad/i', $userAgent)) {
            $platform = 'iPad (iPadOS)';
        } elseif (preg_match('/android/i', $userAgent)) {
            $platform = 'Android';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $platform = 'Linux';
        }

        $browser = 'Unknown Browser';
        if (preg_match('/edg/i', $userAgent)) {
            $browser = 'Microsoft Edge';
        } elseif (preg_match('/chrome|crios/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/firefox|fxios/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/safari/i', $userAgent) && !preg_match('/chrome/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/opera|opr/i', $userAgent)) {
            $browser = 'Opera';
        }

        return "{$platform} ({$browser})";
    }
}
