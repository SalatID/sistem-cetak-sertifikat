<?php

namespace Tests\Feature;

use App\Models\Kontingen;
use App\Models\Peserta;
use App\Models\PrintLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CetakSkTest extends TestCase
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

    private function peserta(string $nama, ?string $noSk, int $juara = 1): Peserta
    {
        return Peserta::create([
            'kontingen_id' => $this->kontingen->id,
            'nama' => $nama,
            'kategori' => 'Kata Perorangan Putra',
            'juara' => $juara,
            'no_sk' => $noSk,
        ]);
    }

    public function test_sk_juara_dapat_dicetak(): void
    {
        $a = $this->peserta('Budi Santoso', '001/SK/SKX26/III/2026', 1);
        $b = $this->peserta('Citra Lestari', '001/SK/SKX26/III/2026', 2);

        $response = $this->get(route('cetak.peserta.sk', ['ids' => [$a->id, $b->id]]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $this->assertDatabaseHas('print_logs', [
            'jenis_dokumen' => 'sk_juara',
            'nomor_sk' => '001/SK/SKX26/III/2026',
            'jumlah_data' => 1,
            'konteks' => 'Budi Santoso',
        ]);
        $this->assertDatabaseHas('print_logs', [
            'jenis_dokumen' => 'sk_juara',
            'nomor_sk' => '001/SK/SKX26/III/2026',
            'jumlah_data' => 1,
            'konteks' => 'Citra Lestari',
        ]);
        $this->assertSame(2, PrintLog::where('jenis_dokumen', 'sk_juara')->count());

        // 1 peserta = 1 halaman PDF
        $this->assertSame(2, preg_match_all('/\/Type\s*\/Page[^s]/', $response->getContent()));
    }

    public function test_sk_bulk_tetap_dicetak_bila_nomor_sk_kosong(): void
    {
        $a = $this->peserta('Tanpa Nomor A', null, 1);
        $b = $this->peserta('Tanpa Nomor B', null, 2);

        $response = $this->get(route('cetak.peserta.sk', ['ids' => [$a->id, $b->id]]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $this->assertSame(2, PrintLog::where('jenis_dokumen', 'sk_juara')->count());
        $this->assertSame(2, PrintLog::where('jenis_dokumen', 'sk_juara')->where('jumlah_data', 1)->count());

        // 1 peserta = 1 halaman, walaupun nomor SK kosong.
        $this->assertSame(2, preg_match_all('/\/Type\s*\/Page[^s]/', $response->getContent()));
    }

    public function test_setiap_peserta_menghasilkan_satu_halaman_sk(): void
    {
        $a = $this->peserta('Budi Santoso', '001/SK/SKX26/III/2026', 1);
        $b = $this->peserta('Citra Lestari', '002/SK/SKX26/III/2026', 2);

        $response = $this->get(route('cetak.peserta.sk', ['ids' => [$a->id, $b->id]]));

        $response->assertOk();

        $this->assertDatabaseHas('print_logs', ['jenis_dokumen' => 'sk_juara', 'nomor_sk' => '001/SK/SKX26/III/2026']);
        $this->assertDatabaseHas('print_logs', ['jenis_dokumen' => 'sk_juara', 'nomor_sk' => '002/SK/SKX26/III/2026']);
        $this->assertSame(2, PrintLog::where('jenis_dokumen', 'sk_juara')->count());

        // 2 peserta = 2 halaman PDF
        $this->assertSame(2, preg_match_all('/\/Type\s*\/Page[^s]/', $response->getContent()));
    }

    public function test_sk_memerlukan_login(): void
    {
        auth()->logout();

        $this->get(route('cetak.peserta.sk', ['ids' => [1]]))->assertRedirect('/login');
    }
}
