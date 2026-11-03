<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Employee;
use App\Models\OutgoingLetter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class OutgoingLetterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureDeviceSessionIsValid::class);
    }

    public function test_secretary_or_direktur_can_view_letters_index()
    {
        $sekretaris = User::where('role', 'sekretaris')->first();
        if (!$sekretaris) {
            $sekretaris = User::where('role', 'direktur')->first();
        }

        $response = $this->actingAs($sekretaris)->get(route('secretary.letters.index'));
        $response->assertStatus(200);
        $response->assertSee('Buku Agenda Surat Keluar');
        $response->assertSee('Terbitkan Nomor Surat');
    }

    public function test_regular_karyawan_cannot_access_letters_index()
    {
        $karyawan = User::where('role', 'karyawan')->where('role', '!=', 'sekretaris')->first();
        if (!$karyawan) {
            $karyawan = User::factory()->create([
                'role' => 'karyawan',
                'sub_department' => 'IT'
            ]);
        }

        $response = $this->actingAs($karyawan)->get(route('secretary.letters.index'));
        $response->assertStatus(403);
    }

    public function test_ajax_get_next_letter_number()
    {
        $sekretaris = User::where('role', 'sekretaris')->first() ?? User::where('role', 'direktur')->first();

        $response = $this->actingAs($sekretaris)->get(route('secretary.letters.nextNumber', [
            'letter_date' => '2026-11-03',
            'letter_category' => 'SK',
            'organization_code' => 'DIR-GYOKAI',
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'letter_number',
            'sequence_number',
            'month_roman',
            'year',
        ]);
        $response->assertJson([
            'success' => true,
            'month_roman' => 'XI',
            'year' => 2026,
        ]);
    }

    public function test_secretary_can_store_new_outgoing_letter()
    {
        $sekretaris = User::where('role', 'sekretaris')->first() ?? User::where('role', 'direktur')->first();
        Storage::fake('local');

        $file = UploadedFile::fake()->create('surat_tugas_resmi.pdf', 150, 'application/pdf');

        $data = [
            'letter_category' => 'ST',
            'organization_code' => 'DIR-GYOKAI',
            'letter_date' => '2026-11-03',
            'urgency' => 'penting',
            'status' => 'draft',
            'recipient' => 'PT Mitra Solusi Prima',
            'recipient_address' => 'Gedung Cyber 2 Lt. 14, Jakarta',
            'subject' => 'Surat Tugas Monitoring Implementasi Sistem',
            'regarding_summary' => 'Menugaskan tim IT dan perwakilan manajemen untuk uji coba sistem di lapangan.',
            'signer_name' => 'Hendra Wijaya',
            'signer_title' => 'Direktur Utama',
            'notes' => 'Harap konfirmasi jadwal paling lambat H-1.',
            'attachment_file' => $file,
        ];

        $response = $this->actingAs($sekretaris)->post(route('secretary.letters.store'), $data);
        $response->assertRedirect(route('secretary.letters.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('outgoing_letters', [
            'letter_category' => 'ST',
            'recipient' => 'PT Mitra Solusi Prima',
            'subject' => 'Surat Tugas Monitoring Implementasi Sistem',
            'signer_name' => 'Hendra Wijaya',
        ]);
    }

    public function test_secretary_can_update_letter_status_and_notes()
    {
        $sekretaris = User::where('role', 'sekretaris')->first() ?? User::where('role', 'direktur')->first();

        $letter = OutgoingLetter::latest('id')->first();
        $this->assertNotNull($letter);

        $response = $this->actingAs($sekretaris)->patch(route('secretary.letters.update', $letter->id), [
            'status' => 'sent',
            'sent_at' => '2026-11-03T14:30',
            'notes' => 'Telah dikirim via email dan ekspedisi fisik tercatat.',
        ]);

        $response->assertRedirect(route('secretary.letters.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('outgoing_letters', [
            'id' => $letter->id,
            'status' => 'sent',
            'notes' => 'Telah dikirim via email dan ekspedisi fisik tercatat.',
        ]);
    }
}
