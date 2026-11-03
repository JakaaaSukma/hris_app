<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Employee;
use App\Models\OfficeSupply;
use App\Models\OfficeSupplyUsage;

class GeneralAffairsAtkTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureDeviceSessionIsValid::class);
    }

    public function test_ga_manager_or_direktur_can_view_atk_dashboard()
    {
        $direktur = User::where('role', 'direktur')->first();
        if (!$direktur) {
            $direktur = User::factory()->create(['role' => 'direktur']);
        }

        $response = $this->actingAs($direktur)->get(route('ga.index', ['tab' => 'supplies']));
        $response->assertStatus(200);
        $response->assertSee('Stok & Pemakaian ATK', false);
    }

    public function test_ga_user_can_create_new_office_supply()
    {
        $gaUser = User::where('sub_department', 'GA')->first() ?? User::where('role', 'direktur')->first();

        $code = 'ATK-TEST-' . rand(100, 999);
        $response = $this->actingAs($gaUser)->post(route('ga.supplies.store'), [
            'item_code' => $code,
            'name' => 'Buku Ekspedisi Hardcover Folio',
            'category' => 'Kertas & Buku',
            'unit' => 'Buku',
            'stock_qty' => 15,
            'min_stock' => 3,
            'location' => 'Lemari ATK Lt. 1',
            'notes' => 'Buku tanda terima surat masuk',
        ]);

        $response->assertRedirect(route('ga.index', ['tab' => 'supplies']));
        $this->assertDatabaseHas('office_supplies', [
            'item_code' => $code,
            'name' => 'Buku Ekspedisi Hardcover Folio',
            'stock_qty' => 15,
        ]);
    }

    public function test_ga_user_can_record_usage_and_stock_is_decremented()
    {
        $gaUser = User::where('sub_department', 'GA')->first() ?? User::where('role', 'direktur')->first();
        $employee = Employee::first();

        $supply = OfficeSupply::create([
            'item_code' => 'ATK-DEC-' . rand(100, 999),
            'name' => 'Spidol Permanent Hitam Snowman',
            'category' => 'Alat Tulis & Koreksi',
            'unit' => 'Pcs',
            'stock_qty' => 20,
            'min_stock' => 5,
            'location' => 'Lemari ATK',
        ]);

        $response = $this->actingAs($gaUser)->post(route('ga.supplies.recordUsage'), [
            'office_supply_id' => $supply->id,
            'employee_id' => $employee->id,
            'quantity' => 3,
            'usage_date' => date('Y-m-d'),
            'purpose' => 'Penandaan arsip dokumen training',
        ]);

        $response->assertRedirect(route('ga.index', ['tab' => 'supplies']));

        // Assert stock decremented by 3 (20 - 3 = 17)
        $this->assertEquals(17, $supply->fresh()->stock_qty);

        // Assert usage recorded
        $this->assertDatabaseHas('office_supply_usages', [
            'office_supply_id' => $supply->id,
            'employee_id' => $employee->id,
            'quantity' => 3,
            'purpose' => 'Penandaan arsip dokumen training',
        ]);
    }

    public function test_cannot_record_usage_exceeding_stock()
    {
        $gaUser = User::where('sub_department', 'GA')->first() ?? User::where('role', 'direktur')->first();
        $employee = Employee::first();

        $supply = OfficeSupply::create([
            'item_code' => 'ATK-LIMIT-' . rand(100, 999),
            'name' => 'Kertas Glossy Foto A4',
            'category' => 'Kertas & Buku',
            'unit' => 'Pack',
            'stock_qty' => 2,
            'min_stock' => 1,
            'location' => 'Lemari ATK',
        ]);

        // Try to take 5 when only 2 available
        $response = $this->actingAs($gaUser)->from(route('ga.index', ['tab' => 'supplies']))->post(route('ga.supplies.recordUsage'), [
            'office_supply_id' => $supply->id,
            'employee_id' => $employee->id,
            'quantity' => 5,
            'usage_date' => date('Y-m-d'),
            'purpose' => 'Cetak poster pelatihan',
        ]);

        $response->assertSessionHas('error');
        // Stock should remain unchanged
        $this->assertEquals(2, $supply->fresh()->stock_qty);
    }

    public function test_restock_supply_increments_stock()
    {
        $gaUser = User::where('sub_department', 'GA')->first() ?? User::where('role', 'direktur')->first();

        $supply = OfficeSupply::create([
            'item_code' => 'ATK-INC-' . rand(100, 999),
            'name' => 'Isi Staples No 10',
            'category' => 'Alat Tulis & Koreksi',
            'unit' => 'Box',
            'stock_qty' => 5,
            'min_stock' => 2,
            'location' => 'Lemari ATK',
        ]);

        $response = $this->actingAs($gaUser)->post(route('ga.supplies.restock', $supply->id), [
            'add_qty' => 10,
            'notes' => 'Pembelian toko ATK',
        ]);

        $response->assertRedirect(route('ga.index', ['tab' => 'supplies']));
        $this->assertEquals(15, $supply->fresh()->stock_qty);
    }

    public function test_canceling_usage_rolls_back_stock()
    {
        $gaUser = User::where('sub_department', 'GA')->first() ?? User::where('role', 'direktur')->first();
        $employee = Employee::first();

        $supply = OfficeSupply::create([
            'item_code' => 'ATK-ROLL-' . rand(100, 999),
            'name' => 'Lakban Coklat 2 Inch',
            'category' => 'Perekat & Pengikat',
            'unit' => 'Rol',
            'stock_qty' => 8,
            'min_stock' => 2,
            'location' => 'Lemari ATK',
        ]);

        $usage = OfficeSupplyUsage::create([
            'office_supply_id' => $supply->id,
            'employee_id' => $employee->id,
            'department' => $employee->department,
            'quantity' => 4,
            'usage_date' => date('Y-m-d'),
            'purpose' => 'Packing pengiriman',
            'recorded_by' => $gaUser->id,
        ]);

        // Stock was 8 - 4 = 4
        $supply->decrement('stock_qty', 4);
        $this->assertEquals(4, $supply->fresh()->stock_qty);

        // Cancel / Delete usage
        $response = $this->actingAs($gaUser)->delete(route('ga.supplies.destroyUsage', $usage->id));
        $response->assertRedirect(route('ga.index', ['tab' => 'supplies']));

        // Stock should be restored back to 8
        $this->assertEquals(8, $supply->fresh()->stock_qty);
        $this->assertDatabaseMissing('office_supply_usages', ['id' => $usage->id]);
    }
}
