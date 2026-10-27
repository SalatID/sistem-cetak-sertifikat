<?php

namespace Tests\Feature;

use App\Models\Kontingen;
use App\Models\Peserta;
use App\Models\User;
use App\Services\LayananCetak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CetakSertifikatTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Kontingen $kontingen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->kontingen = Kontingen::create(['nama' => 'Kontingen Garuda']);
    }

    private function peserta(string $nama = 'Budi Santoso', int|string|null $juara = 1, ?int $noSertifikat = null): Peserta
    {
        return Peserta::create([
            'kontingen_id' => $this->kontingen->id,
            'nama' => $nama,
            'kategori' => 'Kata Perorangan Putra',
            'juara' => $juara,
            'no_sertifikat' => $noSertifikat,
            'no_sk' => '001/SK/SKX26/III/2026',
        ]);
    }

    public function test_sertifikat_peserta_dapat_dicetak(): void
    {
        $peserta = $this->peserta();

        $response = $this->get(route('cetak.peserta.sertifikat', ['ids' => [$peserta->id]]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $this->assertDatabaseHas('pesertas', ['id' => $peserta->id, 'no_sertifikat' => 1]);
        $this->assertDatabaseHas('print_logs', [
            'user_id' => $this->user->id,
            'jenis_dokumen' => 'sertifikat_peserta',
            'jumlah_data' => 1,
        ]);
    }

    public function test_admin_dapat_memilih_cetak_tanpa_background(): void
    {
        $peserta = $this->peserta();

        $this->mock(LayananCetak::class, function ($mock): void {
            $mock->shouldReceive('sertifikat')
                ->once()
                ->withArgs(fn ($pesertas, $userId, $pakaiBackground) => $pakaiBackground === false)
                ->andReturn(response('PDF', 200, ['content-type' => 'application/pdf']));
        });

        $this->get(route('cetak.peserta.sertifikat', [
            'ids' => [$peserta->id],
            'pakai_background' => 0,
        ]))->assertOk();
    }

    public function test_nomor_sertifikat_dipakai_ulang_saat_cetak_ulang(): void
    {
        $peserta = $this->peserta('Budi Santoso', 1, 7);

        $this->get(route('cetak.peserta.sertifikat', ['ids' => [$peserta->id]]))->assertOk();

        $this->assertDatabaseHas('pesertas', ['id' => $peserta->id, 'no_sertifikat' => 7]);
        $this->assertSame(7, Peserta::find($peserta->id)->no_sertifikat);
    }

    public function test_nomor_sertifikat_baru_berurutan_saat_cetak_bulk(): void
    {
        $a = $this->peserta('Budi Santoso', 1);
        $b = $this->peserta('Citra Lestari', 2);
        $c = $this->peserta('Dimas Anggara', 3);

        $response = $this->get(route('cetak.peserta.sertifikat', ['ids' => [$a->id, $b->id, $c->id]]));

        $response->assertOk();

        $nomor = Peserta::whereIn('id', [$a->id, $b->id, $c->id])->pluck('no_sertifikat')->sort()->values()->all();

        $this->assertSame([1, 2, 3], $nomor);
        $this->assertDatabaseHas('print_logs', ['jenis_dokumen' => 'sertifikat_peserta', 'jumlah_data' => 3]);

        // 1 peserta = 1 halaman PDF
        $this->assertSame(3, $this->jumlahHalamanPdf($response->getContent()));
    }

    public function test_nomor_sertifikat_baru_mengambil_nomor_terakhir_plus_satu(): void
    {
        $lama = $this->peserta('Juara Lama', 1, 7);
        $baru = $this->peserta('Juara Baru', 'Pesilat Terbaik');
        $peserta = $this->peserta('Peserta Saja', 'PESERTA');

        $response = $this->get(route('cetak.peserta.sertifikat', ['ids' => [$baru->id, $peserta->id]]));

        $response->assertOk()->assertSessionMissing('warning');
        $this->assertSame(2, $this->jumlahHalamanPdf($response->getContent()));

        $this->assertSame(7, $lama->fresh()->no_sertifikat);
        $this->assertSame(8, $baru->fresh()->no_sertifikat);
        $this->assertNull($peserta->fresh()->no_sertifikat);
    }

    /**
     * Hitung jumlah halaman PDF dari isi file (objek /Type /Page).
     */
    private function jumlahHalamanPdf(string $isi): int
    {
        return preg_match_all('/\/Type\s*\/Page[^s]/', $isi);
    }

    public function test_peserta_tanpa_juara_ditolak(): void
    {
        $peserta = $this->peserta('Belum Juara', null);

        $this->get(route('cetak.peserta.sertifikat', ['ids' => [$peserta->id]]))
            ->assertStatus(422);

        $this->assertDatabaseMissing('print_logs', ['jenis_dokumen' => 'sertifikat_peserta']);
    }

    public function test_peserta_tanpa_juara_dilewati_saat_ada_peserta_juara(): void
    {
        $juara = $this->peserta('Budi Santoso', 1);
        $belum = $this->peserta('Belum Juara', null);

        $response = $this->get(route('cetak.peserta.sertifikat', ['ids' => [$juara->id, $belum->id]]));

        $response->assertOk();
        $response->assertSessionHas('warning');
        $this->assertDatabaseHas('print_logs', ['jenis_dokumen' => 'sertifikat_peserta', 'jumlah_data' => 1]);
        $this->assertNull(Peserta::find($belum->id)->no_sertifikat);
    }

    public function test_peserta_label_peserta_dicetak_tanpa_nomor(): void
    {
        $peserta = $this->peserta('Peserta Saja', 'PESERTA');

        $response = $this->get(route('cetak.peserta.sertifikat', ['ids' => [$peserta->id]]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $this->assertDatabaseHas('print_logs', [
            'jenis_dokumen' => 'sertifikat_peserta',
            'jumlah_data' => 1,
        ]);
        $this->assertNull(Peserta::find($peserta->id)->no_sertifikat);
    }

    public function test_peserta_label_peserta_dicetak_bersama_juara_tanpa_nomor(): void
    {
        $juara = $this->peserta('Budi Santoso', 'JUARA 1');
        $peserta = $this->peserta('Peserta Saja', 'PESERTA');

        $response = $this->get(route('cetak.peserta.sertifikat', ['ids' => [$juara->id, $peserta->id]]));

        $response->assertOk()->assertSessionMissing('warning');
        $this->assertSame(2, $this->jumlahHalamanPdf($response->getContent()));
        $this->assertDatabaseHas('print_logs', ['jenis_dokumen' => 'sertifikat_peserta', 'jumlah_data' => 2]);
        $this->assertNotNull(Peserta::find($juara->id)->no_sertifikat);
        $this->assertNull(Peserta::find($peserta->id)->no_sertifikat);
    }

    public function test_beberapa_peserta_label_peserta_dicetak_tanpa_nomor(): void
    {
        $pesertas = collect([
            $this->peserta('Peserta Satu', 'PESERTA'),
            $this->peserta('Peserta Dua', 'PESERTA'),
            $this->peserta('Peserta Tiga', 'PESERTA'),
        ]);

        $response = $this->get(route('cetak.peserta.sertifikat', [
            'ids' => $pesertas->pluck('id')->all(),
        ]));

        $response->assertOk()->assertSessionMissing('warning');
        $this->assertSame(3, $this->jumlahHalamanPdf($response->getContent()));
        $this->assertDatabaseHas('print_logs', ['jenis_dokumen' => 'sertifikat_peserta', 'jumlah_data' => 3]);
        $this->assertSame(
            [null, null, null],
            $pesertas->map(fn (Peserta $p) => $p->fresh()->no_sertifikat)->all(),
        );
    }

    public function test_pesilat_terbaik_mendapat_nomor_sertifikat(): void
    {
        $peserta = $this->peserta('Pesilat Terbaik', 'PESILAT TERBAIK');

        $this->get(route('cetak.peserta.sertifikat', ['ids' => [$peserta->id]]))
            ->assertOk();

        $this->assertDatabaseHas('pesertas', ['id' => $peserta->id, 'no_sertifikat' => 1]);
    }

    public function test_mengubah_juara_menjadi_peserta_menghapus_nomor_sertifikat(): void
    {
        $peserta = $this->peserta('Budi Santoso', 'JUARA 1', 10);

        $peserta->update(['juara' => 'PESERTA']);

        $this->assertNull($peserta->fresh()->no_sertifikat);
    }

    public function test_permintaan_cetak_dibatasi_maksimal_50_peserta(): void
    {
        $ids = range(1, 51);

        $this->get(route('cetak.peserta.sertifikat', ['ids' => $ids]))
            ->assertStatus(422);

        $this->assertDatabaseMissing('print_logs', ['jenis_dokumen' => 'sertifikat_peserta']);
    }

    public function test_cetak_memerlukan_login(): void
    {
        auth()->logout();

        $this->get(route('cetak.peserta.sertifikat', ['ids' => [1]]))->assertRedirect('/login');
    }
}
