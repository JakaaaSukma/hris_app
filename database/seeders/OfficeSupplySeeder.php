<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OfficeSupply;
use App\Models\OfficeSupplyUsage;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;

class OfficeSupplySeeder extends Seeder
{
    public function run(): void
    {
        $supplies = [
            [
                'item_code' => 'ATK-001',
                'name' => 'Kertas HVS A4 80gr PaperOne',
                'category' => 'Kertas & Buku',
                'unit' => 'Rim',
                'stock_qty' => 35,
                'min_stock' => 10,
                'location' => 'Lemari ATK Lt. 1 - Rak A1',
                'notes' => 'Kertas standar cetak dokumen resmi, sertifikat paklaring, dan operasional kantor.',
            ],
            [
                'item_code' => 'ATK-002',
                'name' => 'Kertas HVS F4 75gr SiDU',
                'category' => 'Kertas & Buku',
                'unit' => 'Rim',
                'stock_qty' => 18,
                'min_stock' => 5,
                'location' => 'Lemari ATK Lt. 1 - Rak A2',
                'notes' => 'Kertas ukuran folio untuk berkas legal dan formulir pendaftaran.',
            ],
            [
                'item_code' => 'ATK-003',
                'name' => 'Pulpen Gel Hitam Joyko 0.5mm (Pack isi 12)',
                'category' => 'Alat Tulis & Koreksi',
                'unit' => 'Pack',
                'stock_qty' => 14,
                'min_stock' => 4,
                'location' => 'Lemari ATK Lt. 1 - Laci B1',
                'notes' => 'Pulpen tinta hitam resmi untuk staf dan meja resepsionis.',
            ],
            [
                'item_code' => 'ATK-004',
                'name' => 'Spidol Whiteboard Snowman Hitam',
                'category' => 'Alat Tulis & Koreksi',
                'unit' => 'Pcs',
                'stock_qty' => 20,
                'min_stock' => 6,
                'location' => 'Lemari ATK Lt. 1 - Laci B2',
                'notes' => 'Spidol papan tulis untuk ruang rapat dan kelas pelatihan LPK.',
            ],
            [
                'item_code' => 'ATK-005',
                'name' => 'Spidol Whiteboard Snowman Biru',
                'category' => 'Alat Tulis & Koreksi',
                'unit' => 'Pcs',
                'stock_qty' => 15,
                'min_stock' => 5,
                'location' => 'Lemari ATK Lt. 1 - Laci B2',
                'notes' => 'Spidol warna biru untuk kelas training.',
            ],
            [
                'item_code' => 'ATK-006',
                'name' => 'Binder Clip No. 107 Joyko (Kotak isi 12)',
                'category' => 'Filing & Dokumen',
                'unit' => 'Box',
                'stock_qty' => 12,
                'min_stock' => 3,
                'location' => 'Lemari ATK Lt. 1 - Rak C1',
                'notes' => 'Penjepit kertas ukuran kecil.',
            ],
            [
                'item_code' => 'ATK-007',
                'name' => 'Map Snelhechter Plastik Biru Folio',
                'category' => 'Filing & Dokumen',
                'unit' => 'Lusin',
                'stock_qty' => 8,
                'min_stock' => 2,
                'location' => 'Lemari ATK Lt. 1 - Rak C2',
                'notes' => 'Map arsip berkas kepegawaian & data trainee.',
            ],
            [
                'item_code' => 'ATK-008',
                'name' => 'Toner Printer HP LaserJet 85A (CE285A)',
                'category' => 'Tinta & Printer',
                'unit' => 'Pcs',
                'stock_qty' => 2,
                'min_stock' => 2,
                'location' => 'Ruang Server & GA Lt. 2',
                'notes' => 'Cartridge toner printer HP ruang finance & HR. Stok menipis!',
            ],
            [
                'item_code' => 'ATK-009',
                'name' => 'Post-it Sticky Notes Joyko 76x76mm Kuning',
                'category' => 'Kertas & Buku',
                'unit' => 'Pad',
                'stock_qty' => 22,
                'min_stock' => 5,
                'location' => 'Lemari ATK Lt. 1 - Rak A3',
                'notes' => 'Memo tempel penanda dokumen dan task board.',
            ],
            [
                'item_code' => 'ATK-010',
                'name' => 'Lakban Bening 2 Inch 100 Yard Daimaru',
                'category' => 'Perekat & Pengikat',
                'unit' => 'Rol',
                'stock_qty' => 10,
                'min_stock' => 3,
                'location' => 'Lemari ATK Lt. 1 - Rak D1',
                'notes' => 'Lakban packing paket pengiriman surat dan dokumen ke luar kantor.',
            ],
            [
                'item_code' => 'ATK-011',
                'name' => 'Stapler HD-10 & Isi Staples No. 10 Max',
                'category' => 'Alat Tulis & Koreksi',
                'unit' => 'Set',
                'stock_qty' => 9,
                'min_stock' => 3,
                'location' => 'Lemari ATK Lt. 1 - Laci B3',
                'notes' => 'Stapler meja kerja kantor.',
            ],
            [
                'item_code' => 'ATK-012',
                'name' => 'Tinta Refill Spidol Whiteboard Hitam Snowman',
                'category' => 'Tinta & Printer',
                'unit' => 'Botol',
                'stock_qty' => 1,
                'min_stock' => 3,
                'location' => 'Lemari ATK Lt. 1 - Rak C3',
                'notes' => 'Tinta isi ulang spidol kelas. Perlu restock segera!',
            ],
        ];

        foreach ($supplies as $item) {
            OfficeSupply::updateOrCreate(
                ['item_code' => $item['item_code']],
                $item
            );
        }

        // Seed sample historical usages
        $employees = Employee::all();
        $gaUser = User::where(function ($q) {
            $q->where('sub_department', 'GA')->orWhere('role', 'direktur');
        })->first() ?? User::first();

        if ($employees->isNotEmpty() && $gaUser) {
            $kertasA4 = OfficeSupply::where('item_code', 'ATK-001')->first();
            $pulpen = OfficeSupply::where('item_code', 'ATK-003')->first();
            $spidol = OfficeSupply::where('item_code', 'ATK-004')->first();
            $map = OfficeSupply::where('item_code', 'ATK-007')->first();

            $samples = [
                [
                    'supply' => $kertasA4,
                    'emp' => $employees->where('department', 'Finance')->first() ?? $employees->first(),
                    'qty' => 2,
                    'date' => Carbon::today()->subDays(2),
                    'purpose' => 'Cetak laporan keuangan bulanan dan slip gaji',
                ],
                [
                    'supply' => $spidol,
                    'emp' => $employees->where('department', 'Trainer')->first() ?? $employees->first(),
                    'qty' => 3,
                    'date' => Carbon::today()->subDays(3),
                    'purpose' => 'Kebutuhan mengajar kelas pelatihan bahasa Jepang & magang',
                ],
                [
                    'supply' => $pulpen,
                    'emp' => $employees->where('department', 'Resepsionis')->first() ?? $employees->first(),
                    'qty' => 1,
                    'date' => Carbon::today()->subDay(),
                    'purpose' => 'Stok pulpen buku tamu lobby & meja resepsionis',
                ],
                [
                    'supply' => $map,
                    'emp' => $employees->where('department', 'HR')->first() ?? $employees->first(),
                    'qty' => 1,
                    'date' => Carbon::today(),
                    'purpose' => 'Penyusunan berkas calon kandidat & rekrutmen batch baru',
                ],
            ];

            foreach ($samples as $s) {
                if ($s['supply'] && $s['emp']) {
                    OfficeSupplyUsage::create([
                        'office_supply_id' => $s['supply']->id,
                        'employee_id' => $s['emp']->id,
                        'department' => $s['emp']->department,
                        'quantity' => $s['qty'],
                        'usage_date' => $s['date'],
                        'purpose' => $s['purpose'],
                        'recorded_by' => $gaUser->id,
                        'notes' => 'Diserahkan langsung di loket GA',
                    ]);
                }
            }
        }
    }
}

