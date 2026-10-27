<?php

namespace Tests\Feature;

use App\Models\Blangko;
use App\Models\PrintLog;
use App\Models\Sekolah;
use App\Models\User;
use App\Services\BlangkoRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SekolahTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->operator = User::factory()->create(['role' => 'operator']);
    }

    public function test_admin_dapat_melihat_daftar_sekolah_dan_status_cetak(): void
    {
        $belum = Sekolah::create(['nama' => 'SMA Negeri 1']);
        $sudah = Sekolah::create([
            'nama' => 'SMA Negeri 2',
            'terakhir_dicetak_at' => now(),
            'jumlah_cetak' => 2,
        ]);

        $this->actingAs($this->admin)
            ->get(route('sekolah.index'))
            ->assertOk()
            ->assertSee('SMA Negeri 1')
            ->assertSee('SMA Negeri 2')
            ->assertSee('Belum pernah dicetak')
            ->assertSee('Sudah dicetak')
            ->assertSee(route('cetak.sekolah.sertifikat', $belum))
            ->assertSee(route('cetak.sekolah.sertifikat', $sudah));
    }

    public function test_admin_dapat_menambah_sekolah_dengan_nama_saja(): void
    {
        $this->actingAs($this->admin)
            ->post(route('sekolah.store'), ['nama' => 'SMA Negeri 1'])
            ->assertRedirect(route('sekolah.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('sekolahs', [
            'nama' => 'SMA Negeri 1',
            'jumlah_cetak' => 0,
        ]);
    }

    public function test_nama_sekolah_duplikat_ditolak(): void
    {
        Sekolah::create(['nama' => 'SMA Negeri 1']);

        $this->actingAs($this->admin)
            ->post(route('sekolah.store'), ['nama' => 'SMA Negeri 1'])
            ->assertSessionHasErrors('nama');

        $this->assertDatabaseCount('sekolahs', 1);
    }

    public function test_operator_tidak_bisa_mengakses_daftar_atau_mencetak_sertifikat_sekolah(): void
    {
        $sekolah = Sekolah::create(['nama' => 'SMA Negeri 1']);

        $this->actingAs($this->operator)
            ->get(route('sekolah.index'))
            ->assertForbidden();

        $this->actingAs($this->operator)
            ->post(route('sekolah.store'), ['nama' => 'SMA Negeri 2'])
            ->assertForbidden();

        $this->actingAs($this->operator)
            ->get(route('cetak.sekolah.sertifikat', $sekolah))
            ->assertForbidden();
    }

    public function test_admin_dapat_mencetak_sertifikat_sekolah_tanpa_blangko_khusus(): void
    {
        $sekolah = Sekolah::create(['nama' => 'SMA Negeri 1']);

        $response = $this->actingAs($this->admin)
            ->get(route('cetak.sekolah.sertifikat', $sekolah));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'SMA Negeri 1.pdf',
            (string) $response->headers->get('content-disposition'),
        );
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $this->assertDatabaseHas('print_logs', [
            'user_id' => $this->admin->id,
            'jenis_dokumen' => 'sertifikat_sekolah',
            'konteks' => 'SMA Negeri 1',
            'jumlah_data' => 1,
        ]);

        $sekolah->refresh();

        $this->assertNotNull($sekolah->terakhir_dicetak_at);
        $this->assertSame(1, $sekolah->jumlah_cetak);
    }

    public function test_jumlah_cetak_sekolah_bertambah_setiap_kali_dicetak(): void
    {
        $sekolah = Sekolah::create(['nama' => 'SMA Negeri 1']);

        $this->actingAs($this->admin)
            ->get(route('cetak.sekolah.sertifikat', $sekolah))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get(route('cetak.sekolah.sertifikat', $sekolah))
            ->assertOk();

        $this->assertSame(2, $sekolah->fresh()->jumlah_cetak);
    }

    public function test_cetak_sekolah_memakai_blangko_jenis_sertifikat_sekolah(): void
    {
        $peserta = Blangko::create([
            'nama' => 'Blangko Peserta',
            'jenis' => Blangko::JENIS_SERTIFIKAT,
            'mode' => Blangko::MODE_CANVAS,
            'aktif' => true,
        ]);

        $sekolah = Blangko::create([
            'nama' => 'Blangko Sekolah',
            'jenis' => Blangko::JENIS_SEKOLAH,
            'mode' => Blangko::MODE_CANVAS,
            'aktif' => true,
        ]);

        $this->assertTrue(Blangko::aktif(Blangko::JENIS_SERTIFIKAT)->is($peserta));
        $this->assertTrue(Blangko::aktif(Blangko::JENIS_SEKOLAH)->is($sekolah));
    }

    public function test_sertifikat_sekolah_tidak_menampilkan_nomor_sertifikat(): void
    {
        $sekolah = new Sekolah(['nama' => 'SMA Negeri 1']);

        $blangko = Blangko::create([
            'nama' => 'Blangko Sekolah',
            'jenis' => Blangko::JENIS_SEKOLAH,
            'mode' => Blangko::MODE_BLADE,
            'view_blade' => 'blangko.contoh-sekolah-sbcc',
            'ukuran_kertas' => 'a4',
            'orientasi' => 'landscape',
            'lebar_mm' => 297,
            'tinggi_mm' => 210,
            'aktif' => true,
        ]);

        $data = BlangkoRenderer::dataSekolah($sekolah);
        $html = BlangkoRenderer::renderBlade(
            $blangko,
            $data,
            BlangkoRenderer::VARIAN_PRESTASI,
            [],
            BlangkoRenderer::kop(),
        );

        $this->assertSame('', $data['no_sertifikat']);
        $this->assertStringContainsString('SMA NEGERI 1', $html);
        $this->assertStringNotContainsString('Nomor', $html);
    }

    public function test_admin_dapat_membuat_blangko_jenis_sertifikat_sekolah(): void
    {
        $this->actingAs($this->admin)
            ->post(route('blangko.store'), [
                'nama' => 'Blangko Sekolah',
                'jenis' => 'sertifikat_sekolah',
                'mode' => 'blade',
                'view_blade' => 'blangko.contoh-sekolah-sbcc',
                'ukuran_kertas' => 'a4',
                'orientasi' => 'landscape',
                'lebar_mm' => 297,
                'tinggi_mm' => 210,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('blangkos', [
            'nama' => 'Blangko Sekolah',
            'jenis' => 'sertifikat_sekolah',
        ]);
    }

    public function test_riwayat_cetak_dapat_difilter_untuk_sertifikat_sekolah(): void
    {
        PrintLog::create([
            'user_id' => $this->admin->id,
            'jenis_dokumen' => 'sertifikat_sekolah',
            'jumlah_data' => 1,
            'konteks' => 'SMA Negeri 1',
        ]);

        $this->actingAs($this->admin)
            ->get(route('log-cetak.index', ['jenis' => 'sertifikat_sekolah']))
            ->assertOk()
            ->assertSee('SMA Negeri 1')
            ->assertSee('Sertifikat Sekolah');
    }

    public function test_daftar_dan_cetak_sekolah_memerlukan_login(): void
    {
        $sekolah = Sekolah::create(['nama' => 'SMA Negeri 1']);

        auth()->logout();

        $this->get(route('sekolah.index'))->assertRedirect('/login');
        $this->get(route('cetak.sekolah.sertifikat', $sekolah))->assertRedirect('/login');
    }
}
