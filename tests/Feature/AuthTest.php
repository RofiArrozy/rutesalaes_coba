<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Skenario 19: Halaman login menampilkan antarmuka dan opsi akun demo pengujian.
     */
    public function test_login_page_renders_with_demo_accounts(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('Masuk ke Sistem');
        $response->assertSee('admin@rutesales.com');
    }

    /**
     * Skenario 20: Fitur Login dan Quick-Login mengarahkan pengguna sesuai perannya:
     * - Sales langsung diarahkan ke agenda rute (route('rute.index')) agar tidak tertolak di dashboard.
     * - Admin diarahkan ke dashboard analitik (route('dashboard')).
     */
    public function test_quick_login_and_logout_flow(): void
    {
        $sales = User::factory()->sales()->create(['name' => 'Sales Budi', 'password' => bcrypt('password')]);
        $admin = User::factory()->admin()->create(['name' => 'Admin Pusat']);

        // Quick login sebagai Sales -> langsung ke rute.index
        $loginResponseSales = $this->get(route('quick.login', $sales->id));
        $loginResponseSales->assertRedirect(route('rute.index'));
        $this->assertAuthenticatedAs($sales);

        // Logout
        $logoutResponse = $this->post(route('logout'));
        $logoutResponse->assertRedirect(route('login'));
        $this->assertGuest();

        // Quick login sebagai Admin -> ke dashboard
        $loginResponseAdmin = $this->get(route('quick.login', $admin->id));
        $loginResponseAdmin->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    /**
     * Pengujian login standar melalui form:
     * Sales yang melakukan login diarahkan langsung ke halaman rute.index.
     */
    public function test_sales_regular_login_redirects_to_rute_index(): void
    {
        $sales = User::factory()->sales()->create([
            'email' => 'sales.test@rutesales.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('login.submit'), [
            'email' => 'sales.test@rutesales.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('rute.index'));
        $this->assertAuthenticatedAs($sales);
    }
}

