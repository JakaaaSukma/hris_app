<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Employee;
use App\Models\SalarySlip;
use App\Models\OutgoingLetter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SecurityPaket1And3Test extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureDeviceSessionIsValid::class);
        Storage::fake('public');
    }

    public function test_magic_bytes_rejects_php_script()
    {
        $phpContent = "<?php echo 'malicious';";
        $tempFile = tempnam(sys_get_temp_dir(), 'test_php');
        file_put_contents($tempFile, $phpContent);

        $uploaded = new UploadedFile($tempFile, 'test.pdf', 'application/pdf', null, true);

        $controller = new class {
            use \App\Traits\ValidatesFileUpload;
        };

        $result = $controller->validateUploadSecurity($uploaded);
        $this->assertFalse($result['safe']);
        $this->assertStringContainsString('skrip PHP yang dilarang', $result['message']);

        @unlink($tempFile);
    }

    public function test_magic_bytes_rejects_windows_executable()
    {
        $mzContent = "MZ\x90\x00\x03\x00\x00\x00\x04\x00";
        $tempFile = tempnam(sys_get_temp_dir(), 'test_mz');
        file_put_contents($tempFile, $mzContent);

        $uploaded = new UploadedFile($tempFile, 'safe.pdf', 'application/pdf', null, true);

        $controller = new class {
            use \App\Traits\ValidatesFileUpload;
        };

        $result = $controller->validateUploadSecurity($uploaded);
        $this->assertFalse($result['safe']);
        $this->assertStringContainsString('Windows Executable', $result['message']);

        @unlink($tempFile);
    }

    public function test_magic_bytes_allows_valid_pdf()
    {
        $pdfContent = "%PDF-1.4\n%âãÏÓ\n1 0 obj\n<<\n/Type /Catalog\n>>\nendobj\ntrailer\n<<\n/Root 1 0 R\n>>\n%%EOF";
        $tempFile = tempnam(sys_get_temp_dir(), 'valid_pdf');
        file_put_contents($tempFile, $pdfContent);

        $uploaded = new UploadedFile($tempFile, 'invoice.pdf', 'application/pdf', null, true);

        $controller = new class {
            use \App\Traits\ValidatesFileUpload;
        };

        $result = $controller->validateUploadSecurity($uploaded);
        $this->assertTrue($result['safe']);

        @unlink($tempFile);
    }

    public function test_adms_rejects_unregistered_serial_number()
    {
        $response = $this->get('/iclock/cdata?SN=HACKER_DEVICE_999');
        $response->assertStatus(403);
    }

    public function test_adms_allows_registered_solution_x609_serial_number()
    {
        $response = $this->get('/iclock/cdata?SN=SOLUTION_X609');
        $response->assertStatus(200);
        $this->assertStringContainsString('GET OPTION FROM: SOLUTION_X609', $response->getContent());
    }

    public function test_regular_karyawan_cannot_download_outgoing_letters()
    {
        $karyawan = User::where('role', 'karyawan')->where('department', '!=', 'Executive')->first();
        if (!$karyawan) {
            $karyawan = User::factory()->create([
                'role' => 'karyawan',
                'department' => 'IT & Engineering',
            ]);
        }

        $letter = OutgoingLetter::first();
        if (!$letter) {
            $letter = OutgoingLetter::create([
                'letter_number' => 'TEST/001/TEST/2026',
                'sequence_number' => 9999,
                'letter_category' => 'SURAT_TUGAS',
                'organization_code' => 'DIR',
                'month_roman' => 'XI',
                'year' => 2026,
                'letter_date' => now()->toDateString(),
                'recipient' => 'Test Recipient',
                'subject' => 'Test Subject',
                'urgency' => 'biasa',
                'status' => 'draft',
                'signer_name' => 'Direktur',
                'signer_title' => 'Direktur Utama',
                'file_path' => 'outgoing_letters/test.pdf',
                'created_by' => $karyawan->id,
            ]);
        }

        $response = $this->actingAs($karyawan)->get(route('secretary.letters.download', $letter->id));
        $response->assertStatus(403);
    }
}
