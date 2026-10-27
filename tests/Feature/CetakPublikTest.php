<?php

namespace Tests\Feature;

use App\Models\Kontingen;
use App\Models\Peserta;
use App\Models\User;
use App\Services\LayananCetak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CetakPublikTest extends TestCase
{
    use RefreshDatabase;

    private Kontingen $kontingen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kontingen = Kontingen::create(['nama' => 'Kontingen Garuda']);
    }

    private function peserta(string $nama = 'Budi Santoso', int|string|null $juara = 1, ?string $noSk = '001/SK/SKX26/III/2026'): Peserta
    {
        return Peserta::create([
            'kontingen_id' => $this->kontingen->id,
            'nama' => $nama,
            'kategori' => 'Kata Putra',
            'kelompok_usia' => 'Usia Dini 2',
            'juara' => $juara,
            'no_sk' => $noSk,
        ]);
    }

    public function test_tamu_dapat_membuka_daftar_peserta_tanpa_login(): void
    {
        $this->peserta('Budi Santoso');

        $this->get(route('publik.index'))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('Kontingen Garuda');
    }

    public function test_daftar_peserta_tamu_dipaginasi_15_per_halaman(): void
    {
        Peserta::factory()->count(20)->create(['kontingen_id' => $this->kontingen->id]);

        $response = $this->get(route('publik.index'));

        $response->assertOk();

        $this->assertCount(15, $response->viewData('peserta')->items());
        $this->assertSame(20, $response->viewData('peserta')->total());
    }

    public function test_tamu_dapat_mencari_nama_peserta(): void
    {
        $this->peserta('Budi Santoso');
        $this->peserta('Citra Lestari');

        $this->get(route('publik.index', ['q' => 'Citra']))
            ->assertOk()
            ->assertSee('Citra Lestari')
            ->assertDontSee('Budi Santoso');
    }

    public function test_tamu_mencetak_sertifikat_juara_dan_mengalokasikan_nomor(): void
    {
        $peserta = $this->peserta();

        $response = $this->get(route('publik.sertifikat', $peserta));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());

        // Tercatat sebagai cetak tamu (user_id kosong)
        $this->assertDatabaseHas('print_logs', [
            'user_id' => null,
            'jenis_dokumen' => 'sertifikat_peserta',
            'peserta_id' => $peserta->id,
        ]);

        // Juara 1 berhak mendapat nomor MAX+1 saat pertama dicetak.
        $this->assertSame(1, Peserta::find($peserta->id)->no_sertifikat);
    }

    public function test_tamu_selalu_memakai_background_walau_meminta_tanpa_background(): void
    {
        $peserta = $this->peserta();

        $this->mock(LayananCetak::class, function ($mock): void {
            $mock->shouldReceive('sertifikat')
                ->once()
                ->withArgs(fn ($pesertas, $userId, $pakaiBackground) => $pakaiBackground === true)
                ->andReturn(response('PDF', 200, ['content-type' => 'application/pdf']));
        });

        $this->get(route('publik.sertifikat', [
            'peserta' => $peserta,
            'pakai_background' => 0,
        ]))->assertOk();
    }

    public function test_tamu_mencetak_sertifikat_pesilat_terbaik_dan_mengalokasikan_nomor(): void
    {
        $peserta = $this->peserta('Pesilat Terbaik', 'PESILAT TERBAIK');

        $this->get(route('publik.sertifikat', $peserta))->assertOk();

        $this->assertSame(1, Peserta::find($peserta->id)->no_sertifikat);
    }

    public function test_cetak_tamu_tidak_mengubah_nomor_sertifikat_yang_sudah_ada(): void
    {
        $peserta = $this->peserta();
        $peserta->update(['no_sertifikat' => 123]);

        $this->get(route('publik.sertifikat', $peserta))->assertOk();

        $this->assertSame(123, Peserta::find($peserta->id)->no_sertifikat);
    }

    public function test_tamu_tidak_dapat_mencetak_sertifikat_peserta_belum_juara(): void
    {
        $peserta = $this->peserta('Belum Juara', null);

        $this->get(route('publik.sertifikat', $peserta))->assertNotFound();
    }

    public function test_tamu_dapat_mencetak_sertifikat_untuk_label_peserta_tanpa_nomor(): void
    {
        $peserta = $this->peserta('Peserta Saja', 'PESERTA');

        $response = $this->get(route('publik.sertifikat', $peserta));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $this->assertDatabaseHas('print_logs', [
            'user_id' => null,
            'jenis_dokumen' => 'sertifikat_peserta',
            'peserta_id' => $peserta->id,
        ]);
        $this->assertNull(Peserta::find($peserta->id)->no_sertifikat);
    }

    public function test_daftar_tamu_menampilkan_tombol_sertifikat_untuk_label_peserta(): void
    {
        $peserta = $this->peserta('Peserta Saja', 'PESERTA');

        $this->get(route('publik.index'))
            ->assertOk()
            ->assertSee(route('publik.sertifikat', $peserta));
    }

    public function test_tamu_dapat_mencetak_sk(): void
    {
        $peserta = $this->peserta();

        $response = $this->get(route('publik.sk', $peserta));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $this->assertDatabaseHas('print_logs', [
            'user_id' => null,
            'jenis_dokumen' => 'sk_juara',
            'nomor_sk' => '001/SK/SKX26/III/2026',
        ]);
    }

    public function test_tamu_dapat_mencetak_sk_tanpa_nomor_untuk_satu_peserta(): void
    {
        $peserta = $this->peserta('Tanpa Nomor', 1, null);
        $this->peserta('Peserta Lain', 1, null);

        $response = $this->get(route('publik.sk', $peserta));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $this->assertDatabaseHas('print_logs', [
            'user_id' => null,
            'jenis_dokumen' => 'sk_juara',
            'jumlah_data' => 1,
            'nomor_sk' => '',
            'konteks' => '[TAMU] Tanpa Nomor',
        ]);
    }

    public function test_daftar_tamu_menampilkan_kelompok_usia_dan_tombol_sk(): void
    {
        $peserta = $this->peserta('Tanpa Nomor', 1, null);

        $this->get(route('publik.index'))
            ->assertOk()
            ->assertSee('Kelompok Usia')
            ->assertSee('Usia Dini 2')
            ->assertSee(route('publik.sk', $peserta));
    }

    public function test_tamu_dapat_mencetak_sertifikat_official_dengan_nama_dan_kontingen(): void
    {
        $response = $this->post(route('publik.official.cetak'), [
            'nama' => 'Coach Budi Hartono',
            'kontingen_id' => $this->kontingen->id,
            'jabatan' => 'Coach',
        ]);

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $this->assertDatabaseHas('print_logs', [
            'user_id' => null,
            'jenis_dokumen' => 'sertifikat_official',
        ]);
    }

    public function test_formulir_official_tamu_menampilkan_kontingen(): void
    {
        $this->get(route('publik.official'))
            ->assertOk()
            ->assertSee('Kontingen Garuda');
    }

    public function test_cetak_tamu_dibatasi_rate_limit(): void
    {
        $peserta = $this->peserta();

        for ($i = 0; $i < 30; $i++) {
            $this->get(route('publik.sertifikat', $peserta))->assertOk();
        }

        $this->get(route('publik.sertifikat', $peserta))->assertStatus(429);
    }

    public function test_admin_boleh_membuka_halaman_publik(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('publik.index'))
            ->assertOk();
    }
}
