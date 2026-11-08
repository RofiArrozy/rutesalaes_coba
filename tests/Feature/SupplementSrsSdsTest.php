<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplementSrsSdsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $sales1;
    protected User $sales2;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Admin
        $this->admin = User::factory()->create([
            'name' => 'Admin Pusat',
            'email' => 'admin@test.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        // 2. Setup Sales 1
        $this->sales1 = User::factory()->create([
            'name' => 'Sales Budi',
            'email' => 'sales1@test.com',
            'role' => 'sales',
            'is_active' => true,
        ]);

        // 3. Setup Sales 2
        $this->sales2 = User::factory()->create([
            'name' => 'Sales Siti',
            'email' => 'sales2@test.com',
            'role' => 'sales',
            'is_active' => true,
        ]);

        // 4. Setup Toko
        $this->store = Store::create([
            'name' => 'Toko Barokah Jaya',
            'owner_name' => 'Pak Haji',
            'phone' => '081234567890',
            'address' => 'Jl. Merdeka No. 1',
            'area' => 'Jakarta Selatan',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'status' => 'active',
        ]);
    }

    /**
     * 1. SDS II.1: Authentication Middleware ('auth')
     * Seluruh rute aplikasi (selain login) hanya dapat diakses oleh pengguna yang sudah terautentikasi.
     */
    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect(route('login'));

        $response = $this->get('/rute');
        $response->assertRedirect(route('login'));

        $response = $this->get('/stores');
        $response->assertRedirect(route('login'));

        $response = $this->get('/sales');
        $response->assertRedirect(route('login'));
    }

    /**
     * 2. SRS I.1 & SDS II.1: Role-Based Authorization
     * Sales TIDAK DAPAT mengakses pengelolaan pengguna, pembuatan toko, atau dashboard analitik admin.
     */
    public function test_sales_cannot_access_admin_restricted_areas(): void
    {
        // Sales tidak dapat mengakses pengelolaan pengguna / sales CRUD
        $response = $this->actingAs($this->sales1)->get('/sales');
        $response->assertStatus(403);

        // Sales tidak dapat mengakses form tambah toko baru
        $response = $this->actingAs($this->sales1)->get('/stores/create');
        $response->assertStatus(403);

        // Sales tidak dapat membuat toko baru
        $response = $this->actingAs($this->sales1)->post('/stores', [
            'name' => 'Toko Baru Sales',
            'owner_name' => 'Owner',
            'address' => 'Alamat',
            'status' => 'active',
        ]);
        $response->assertStatus(403);

        // Sales diarahkan menjauhi dashboard analitik admin ke agenda rute
        $response = $this->actingAs($this->sales1)->get('/dashboard');
        $response->assertRedirect(route('rute.index'));
    }

    /**
     * 3. SRS I.1 & SDS II.1: Data-Level Authorization & VisitPolicy
     * Sales hanya dapat melihat data kunjungan miliknya sendiri via URI dinamis /rute/detail/{id}.
     */
    public function test_sales_can_only_access_assigned_visits(): void
    {
        // Kunjungan untuk Sales 1
        $visitSales1 = Visit::create([
            'user_id' => $this->sales1->id,
            'store_id' => $this->store->id,
            'visit_date' => Carbon::today()->toDateString(),
            'status' => 'Direncanakan',
            'order_value' => 0,
        ]);

        // Kunjungan untuk Sales 2
        $visitSales2 = Visit::create([
            'user_id' => $this->sales2->id,
            'store_id' => $this->store->id,
            'visit_date' => Carbon::today()->toDateString(),
            'status' => 'Direncanakan',
            'order_value' => 0,
        ]);

        // Sales 1 BISA mengakses kunjungannya sendiri
        $response = $this->actingAs($this->sales1)->get("/rute/detail/{$visitSales1->id}");
        $response->assertStatus(200);

        // Sales 1 TIDAK BISA mengakses kunjungan Sales 2 (403 Forbidden)
        $response = $this->actingAs($this->sales1)->get("/rute/detail/{$visitSales2->id}");
        $response->assertStatus(403);

        // Admin BISA mengakses kunjungan siapa saja
        $response = $this->actingAs($this->admin)->get("/rute/detail/{$visitSales1->id}");
        $response->assertStatus(200);
        $response = $this->actingAs($this->admin)->get("/rute/detail/{$visitSales2->id}");
        $response->assertStatus(200);
    }

    /**
     * 4. SRS I.2: Aturan Transisi Status & Imutabilitas (Kunci Data Read-Only)
     * Setelah status menjadi Selesai, formulir dikunci dan Sales tidak dapat mengubah kembali.
     */
    public function test_completed_visit_is_locked_for_sales(): void
    {
        $visit = Visit::create([
            'user_id' => $this->sales1->id,
            'store_id' => $this->store->id,
            'visit_date' => Carbon::today()->toDateString(),
            'status' => 'Direncanakan',
            'order_value' => 0,
        ]);

        // Check-in pertama berhasil
        $response = $this->actingAs($this->sales1)->post("/rute/checkin/{$visit->id}", [
            'result_status' => 'Berhasil',
            'order_value' => 500000,
            'notes' => 'Toko memesan produk reguler',
        ]);
        $response->assertRedirect("/rute/detail/{$visit->id}");

        $visit->refresh();
        $this->assertEquals('Selesai', $visit->status);
        $this->assertEquals('Berhasil', $visit->result_status);
        $this->assertNotNull($visit->check_in_time);

        // Percobaan check-in kedua oleh Sales DITOLAK (Immutability Lock)
        $response = $this->actingAs($this->sales1)->post("/rute/checkin/{$visit->id}", [
            'result_status' => 'Berhasil',
            'order_value' => 999999,
        ]);
        $response->assertStatus(403);

        // Percobaan edit jadwal oleh Sales DITOLAK
        $response = $this->actingAs($this->sales1)->get("/rute/{$visit->id}/edit");
        $response->assertStatus(403);
    }

    /**
     * 5. SRS I.3: Detail Aturan Validasi Server-Side
     * - status: Wajib diisi (Berhasil/Tutup/Ditolak)
     * - nilai_pesanan: Wajib >= 0 jika Berhasil, wajib null/0 jika Tutup/Ditolak
     * - catatan: Wajib jika Tutup/Ditolak, opsional jika Berhasil
     * - waktu_kunjungan: Otomatis diisi oleh server timestamp
     */
    public function test_server_side_validation_rules_for_checkin(): void
    {
        $visit = Visit::create([
            'user_id' => $this->sales1->id,
            'store_id' => $this->store->id,
            'visit_date' => Carbon::today()->toDateString(),
            'status' => 'Direncanakan',
            'order_value' => 0,
        ]);

        // Kasus A: Status kosong -> Harus gagal validasi
        $response = $this->actingAs($this->sales1)->post("/rute/checkin/{$visit->id}", []);
        $response->assertSessionHasErrors(['result_status']);

        // Kasus B: Status Berhasil tapi nilai_pesanan kosong -> Harus gagal
        $response = $this->actingAs($this->sales1)->post("/rute/checkin/{$visit->id}", [
            'status' => 'Berhasil',
            'order_value' => '',
        ]);
        $response->assertSessionHasErrors(['order_value']);

        // Kasus C: Status Tutup tapi catatan/fail_reason kosong -> Harus gagal
        $response = $this->actingAs($this->sales1)->post("/rute/checkin/{$visit->id}", [
            'status' => 'Tutup',
            'catatan' => '',
        ]);
        $response->assertSessionHasErrors(['fail_reason']);

        // Kasus D: Status Tutup dengan nilai_pesanan positif -> Harus gagal (wajib 0 atau null)
        $response = $this->actingAs($this->sales1)->post("/rute/checkin/{$visit->id}", [
            'status' => 'Tutup',
            'catatan' => 'Toko tutup karena libur',
            'order_value' => 250000,
        ]);
        $response->assertSessionHasErrors(['order_value']);

        // Kasus E: Status Tutup valid (catatan terisi, order_value = 0) -> Berhasil
        $response = $this->actingAs($this->sales1)->post("/rute/checkin/{$visit->id}", [
            'status' => 'Tutup',
            'catatan' => 'Toko tutup karena hari libur nasional',
            'order_value' => 0,
        ]);
        $response->assertRedirect("/rute/detail/{$visit->id}");
        $visit->refresh();
        $this->assertEquals('Selesai', $visit->status);
        $this->assertEquals('Tutup', $visit->result_status);
        $this->assertEquals(0, (float) $visit->order_value);
        $this->assertNotNull($visit->check_in_time);
    }
}
