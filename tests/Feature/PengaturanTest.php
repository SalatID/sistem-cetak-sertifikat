<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PengaturanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_dapat_memperbarui_pengaturan_dokumen(): void
    {
        $this->actingAs($this->admin);

        $this->put(route('pengaturan.update'), [
            'nama_kejuaraan' => 'Kejuaraan Karate 2027',
            'singkatan_kejuaraan' => 'SK27',
            'penyelenggara' => 'DPD Karate Kabupaten X',
            'tempat_tanggal' => 'GOR Kabupaten X, 1-3 Maret 2027',
            'teks_sertifikat' => 'Diberikan kepada {NAMA}.',
            'teks_sertifikat_official' => 'Diberikan kepada {NAMA}onsel.',
            'nama_ttd' => 'H. Ahmad Wijaya, S.Pd',
            'jabatan_ttd' => 'Ketua Panitia',
            'kota_ttd' => 'X',
        ])->assertRedirect(route('pengaturan.index'));

        $this->assertDatabaseHas('settings', [
            'setting_key' => 'nama_kejuaraan',
            'setting_value' => 'Kejuaraan Karate 2027',
        ]);
    }

    public function test_operator_tidak_bisa_membuka_pengaturan(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)->get(route('pengaturan.index'))->assertForbidden();
        $this->actingAs($operator)->put(route('pengaturan.update'), ['nama_kejuaraan' => 'X'])->assertForbidden();
    }

    public function test_admin_tidak_bisa_masuk_tanpa_login(): void
    {
        $this->get(route('pengaturan.index'))->assertRedirect('/login');
    }

    public function test_logo_dapat_diunggah(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin);

        $this->put(route('pengaturan.update'), [
            'nama_kejuaraan' => 'Kejuaraan Karate 2027',
            'logo_kiri' => UploadedFile::fake()->image('logo.png', 200, 200),
        ])->assertRedirect(route('pengaturan.index'));

        $path = Setting::getValue('logo_kiri');

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_halaman_pengaturan_menampilkan_form(): void
    {
        $this->actingAs($this->admin)
            ->get(route('pengaturan.index'))
            ->assertOk()
            ->assertSee('Identitas Kejuaraan')
            ->assertSee('Penandatangan');
    }
}
