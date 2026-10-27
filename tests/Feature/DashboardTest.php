<?php

namespace Tests\Feature;

use App\Models\Kontingen;
use App\Models\Peserta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_data_summary(): void
    {
        $kontingen = Kontingen::create(['nama' => 'Kontingen Garuda']);

        Peserta::create([
            'kontingen_id' => $kontingen->id,
            'nama' => 'Muhammad Rizky',
            'kategori' => 'Kata',
            'juara' => 1,
        ]);
        Peserta::create([
            'kontingen_id' => $kontingen->id,
            'nama' => 'Andi Saputra',
            'kategori' => 'Kumite',
            'juara' => 2,
        ]);
        Peserta::create([
            'kontingen_id' => $kontingen->id,
            'nama' => 'Belum Juara',
            'kategori' => 'Kumite',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Kontingen')
            ->assertSee('Peserta')
            ->assertSee('Juara 1 (Emas)');
    }
}
