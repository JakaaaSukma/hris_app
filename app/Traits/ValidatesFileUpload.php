<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

trait ValidatesFileUpload
{
    protected array $dangerousExtensions = [
        'exe', 'bat', 'cmd', 'com', 'msi', 'scr', 'pif',
        'php', 'phtml', 'php3', 'php4', 'php5', 'phps',
        'sh', 'bash', 'cgi', 'pl', 'py', 'rb',
        'js', 'jsx', 'ts', 'tsx',
        'asp', 'aspx', 'jsp', 'jspx',
        'htaccess', 'htpasswd',
        'vbs', 'vbe', 'wsf', 'wsh', 'ps1',
        'svg',
        'swf',
    ];

    protected array $dangerousMagicBytes = [
        '4D5A'       => 'Windows Executable (MZ)',
        '7F454C46'   => 'Linux Executable (ELF)',
        'CAFEBABE'   => 'Java Class File',
        '23212F'     => 'Script with Shebang (#!/)',
    ];

    public function validateUploadSecurity(UploadedFile $file, string $category = 'any', int $maxSizeMb = 100): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if (in_array($ext, $this->dangerousExtensions, true)) {
            return [
                'safe' => false,
                'message' => "Tipe file .{$ext} tidak diizinkan demi alasan keamanan."
            ];
        }

        if ($file->getSize() > ($maxSizeMb * 1024 * 1024)) {
            return [
                'safe' => false,
                'message' => "Ukuran file melebihi batas maksimal {$maxSizeMb} MB."
            ];
        }

        // Inspeksi Signature Biner (Magic Bytes) untuk mencegah file executable atau skrip tersembunyi
        $realPath = $file->getRealPath();
        if ($realPath && file_exists($realPath) && is_readable($realPath)) {
            $fp = @fopen($realPath, 'rb');
            if ($fp) {
                $header = fread($fp, 16);
                fclose($fp);
                if ($header !== false && strlen($header) >= 2) {
                    $hex = strtoupper(bin2hex($header));
                    foreach ($this->dangerousMagicBytes as $magicHex => $label) {
                        if (str_starts_with($hex, strtoupper($magicHex))) {
                            return [
                                'safe' => false,
                                'message' => "Berkas ditolak: Terdeteksi format file berbahaya ({$label})."
                            ];
                        }
                    }
                    // Deteksi skrip PHP di awal file (<?php atau <?=)
                    if (str_starts_with($hex, '3C3F706870') || str_starts_with($hex, '3C3F3D')) {
                        return [
                            'safe' => false,
                            'message' => "Berkas ditolak: Terdeteksi skrip PHP yang dilarang."
                        ];
                    }
                }
            }
        }

        return ['safe' => true, 'message' => null];
    }
}
