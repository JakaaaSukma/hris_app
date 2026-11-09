<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ClientContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ClientContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureDeviceSessionIsValid::class);
        Storage::fake('public');
    }

    public function test_karyawan_cannot_access_client_contracts()
    {
        $karyawan = User::factory()->create([
            'role' => 'karyawan',
            'department' => 'IT & Engineering',
        ]);

        $response = $this->actingAs($karyawan)->get(route('secretary.contracts.index'));
        $response->assertStatus(403);
    }

    public function test_sekretaris_and_direktur_can_access_client_contracts()
    {
        $sekretaris = User::factory()->create([
            'role' => 'sekretaris',
            'department' => 'Executive',
            'sub_department' => 'Sekretariat',
        ]);

        $responseSek = $this->actingAs($sekretaris)->get(route('secretary.contracts.index'));
        $responseSek->assertStatus(200);

        $direktur = User::factory()->create([
            'role' => 'direktur',
            'department' => 'Executive',
        ]);

        $responseDir = $this->actingAs($direktur)->get(route('secretary.contracts.index'));
        $responseDir->assertStatus(200);
    }

    public function test_sekretaris_can_create_and_upload_client_contract()
    {
        $sekretaris = User::factory()->create([
            'role' => 'sekretaris',
            'department' => 'Executive',
        ]);

        $draftFile = UploadedFile::fake()->create('draft_mou.docx', 100);
        $signedFile = UploadedFile::fake()->create('signed_mou.pdf', 200, 'application/pdf');

        $data = [
            'contract_number' => '001/MOU-GYK/XI/2026',
            'title' => 'Kerjasama Magang Industri Jepang Batch 1',
            'client_name' => 'PT Asahi Seimitsu Indonesia',
            'client_category' => 'perusahaan_swasta',
            'contract_type' => 'mou',
            'client_pic_name' => 'Yamada Taro',
            'client_pic_phone' => '08123456789',
            'client_pic_email' => 'yamada@asahi.co.jp',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'auto_renewal' => 1,
            'contract_value' => 250000000,
            'currency' => 'IDR',
            'notes' => 'Klausul evaluasi tiap 6 bulan.',
            'signed_at' => now()->toDateString(),
            'draft_file' => $draftFile,
            'signed_file' => $signedFile,
        ];

        $response = $this->actingAs($sekretaris)->post(route('secretary.contracts.store'), $data);
        $response->assertRedirect(route('secretary.contracts.index'));

        $this->assertDatabaseHas('client_contracts', [
            'contract_number' => '001/MOU-GYK/XI/2026',
            'client_name' => 'PT Asahi Seimitsu Indonesia',
            'status' => 'active',
        ]);

        $contract = ClientContract::where('contract_number', '001/MOU-GYK/XI/2026')->first();
        $this->assertNotNull($contract->draft_file_path);
        $this->assertNotNull($contract->signed_file_path);
        Storage::disk('public')->assertExists($contract->draft_file_path);
        Storage::disk('public')->assertExists($contract->signed_file_path);
    }

    public function test_computed_status_and_days_remaining()
    {
        // 1. Kontrak yang masih aktif dan aman (6 bulan lagi)
        $activeContract = ClientContract::create([
            'contract_number' => 'MOU-SAFE-001',
            'title' => 'Kontrak Aman',
            'client_name' => 'SMK Negeri 1',
            'client_category' => 'lembaga_pendidikan',
            'contract_type' => 'mou',
            'start_date' => now()->subMonths(6)->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'status' => 'active',
        ]);

        $this->assertEquals('active', $activeContract->computed_status);
        $this->assertEquals('safe', $activeContract->urgency_level);
        $this->assertTrue($activeContract->days_remaining > 60);

        // 2. Kontrak kritis (akan habis 10 hari lagi)
        $criticalContract = ClientContract::create([
            'contract_number' => 'MOU-CRIT-002',
            'title' => 'Kontrak Kritis',
            'client_name' => 'Kumiai Tokyo',
            'client_category' => 'mitra_jepang',
            'contract_type' => 'pks',
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'status' => 'active',
        ]);

        $this->assertEquals('expiring_soon', $criticalContract->computed_status);
        $this->assertEquals('critical', $criticalContract->urgency_level);
        $this->assertEquals(10, $criticalContract->days_remaining);

        // 3. Kontrak yang sudah lewat tanggal berakhir (expired)
        $expiredContract = ClientContract::create([
            'contract_number' => 'MOU-EXP-003',
            'title' => 'Kontrak Kedaluwarsa',
            'client_name' => 'PT Lama',
            'client_category' => 'perusahaan_swasta',
            'contract_type' => 'mou',
            'start_date' => now()->subYears(2)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'status' => 'active',
        ]);

        $this->assertEquals('expired', $expiredContract->computed_status);
        $this->assertEquals('expired', $expiredContract->urgency_level);
        $this->assertTrue($expiredContract->days_remaining < 0);
    }

    public function test_sekretaris_can_renew_contract()
    {
        $sekretaris = User::factory()->create([
            'role' => 'sekretaris',
            'department' => 'Executive',
        ]);

        $contract = ClientContract::create([
            'contract_number' => 'MOU-RENEW-001',
            'title' => 'Kerjasama Kampus',
            'client_name' => 'Politeknik Negeri',
            'client_category' => 'lembaga_pendidikan',
            'contract_type' => 'mou',
            'start_date' => '2025-01-01',
            'end_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $newEndDate = '2027-01-01';
        $response = $this->actingAs($sekretaris)->post(route('secretary.contracts.renew', $contract->id), [
            'new_end_date' => $newEndDate,
            'renewal_notes' => 'Perpanjangan 1 tahun berdasarkan evaluasi baik.',
        ]);

        $response->assertRedirect(route('secretary.contracts.index'));

        $contract->refresh();
        $this->assertEquals($newEndDate, $contract->end_date->toDateString());
        $this->assertStringContainsString('Perpanjangan 1 tahun', $contract->notes);
    }

    public function test_download_contract_file_authorized_and_unauthorized()
    {
        $sekretaris = User::factory()->create(['role' => 'sekretaris']);
        $karyawan = User::factory()->create(['role' => 'karyawan']);

        $fakeSigned = UploadedFile::fake()->create('signed.pdf', 100, 'application/pdf');
        $filePath = $fakeSigned->storeAs('client_contracts/signed', 'signed_test.pdf', 'public');

        $contract = ClientContract::create([
            'contract_number' => 'MOU-FILE-001',
            'title' => 'Test File',
            'client_name' => 'PT Mitra',
            'client_category' => 'perusahaan_swasta',
            'contract_type' => 'mou',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'signed_file_path' => $filePath,
        ]);

        // Karyawan biasa dilarang unduh berkas MoU
        $resKaryawan = $this->actingAs($karyawan)->get(route('secretary.contracts.download', [
            'id' => $contract->id,
            'type' => 'signed',
        ]));
        $resKaryawan->assertStatus(403);

        // Sekretaris diizinkan unduh berkas MoU
        $resSekretaris = $this->actingAs($sekretaris)->get(route('secretary.contracts.download', [
            'id' => $contract->id,
            'type' => 'signed',
        ]));
        $resSekretaris->assertStatus(200);
    }
}

