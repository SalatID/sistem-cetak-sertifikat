<?php

namespace Tests\Feature;

use App\Models\Blangko;
use App\Models\Kontingen;
use App\Models\Peserta;
use App\Models\PrintLog;
use App\Models\Setting;
use App\Models\StempelLegalisir;
use App\Models\User;
use App\Services\LayananCetak;
use App\Services\LegalisirRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegalisirTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Kontingen $kontingen;

    /** @var list<string> */
    private array $fileUji = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->kontingen = Kontingen::create(['nama' => 'Kontingen Garuda']);
    }

    protected function tearDown(): void
    {
        foreach ($this->fileUji as $path) {
            Storage::disk('public')->delete($path);
        }

        parent::tearDown();
    }

    private function peserta(string $nama = 'Budi Santoso', ?string $juara = '1'): Peserta
    {
        return Peserta::create([
            'kontingen_id' => $this->kontingen->id,
            'nama' => $nama,
            'kategori' => 'Kata Perorangan Putra',
            'juara' => $juara,
            'no_sk' => '001/SK/SKX26/III/2026',
        ]);
    }

    /**
     * @param  array<string, mixed>  $ubah
     */
    private function buatStempel(array $ubah = []): StempelLegalisir
    {
        $path = 'legalisir-test/stempel-'.uniqid().'.png';
        Storage::disk('public')->put($path, $this->pngMerah());
        $this->fileUji[] = $path;

        return StempelLegalisir::create(array_merge([
            'nama' => 'Stempel Resmi '.uniqid(),
            'gambar' => $path,
            'x_mm' => 237,
            'y_mm' => 20,
            'lebar_mm' => 40,
            'tinggi_mm' => 40,
            'aktif' => true,
        ], $ubah));
    }

    private function pngMerah(): string
    {
        $image = imagecreatetruecolor(200, 200);
        imagesavealpha($image, true);
        $transparan = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparan);
        $merah = imagecolorallocate($image, 220, 30, 30);
        imagerectangle($image, 0, 0, 199, 199, $merah);

        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }

    private function jumlahHalamanPdf(string $isi): int
    {
        return preg_match_all('/\/Type\s*\/Page[^s]/', $isi);
    }

    public function test_operator_tidak_bisa_membuka_legalisir(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)->get(route('legalisir.index'))->assertForbidden();
        $this->actingAs($operator)->get(route('legalisir.stempel.index'))->assertForbidden();
        $this->actingAs($operator)->get(route('legalisir.cetak', ['ids' => [1], 'stempel_id' => 1]))->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('legalisir.index'))->assertRedirect('/login');
    }

    public function test_admin_dapat_membuka_halaman_legalisir(): void
    {
        $peserta = $this->peserta();
        $stempel = $this->buatStempel();

        $this->actingAs($this->admin)
            ->get(route('legalisir.index', ['ids' => [$peserta->id]]))
            ->assertOk()
            ->assertSee($peserta->nama)
            ->assertSee($stempel->nama)
            ->assertSee('Cetak Legalisir (2 copy)')
            ->assertSee('Siap');
    }

    public function test_halaman_legalisir_mengingatkan_bila_belum_ada_stempel(): void
    {
        $peserta = $this->peserta();

        $this->actingAs($this->admin)
            ->get(route('legalisir.index', ['ids' => [$peserta->id]]))
            ->assertOk()
            ->assertSee('Belum ada stempel legalisir aktif.');
    }

    public function test_admin_dapat_membuat_stempel_legalisir(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('legalisir.stempel.store'), [
            'nama' => 'Stempel Panitia',
            'gambar' => UploadedFile::fake()->image('stempel.png', 200, 200),
            'x_mm' => 237,
            'y_mm' => 20,
            'lebar_mm' => 40,
            'tinggi_mm' => 40,
            'aktif' => 1,
        ])->assertRedirect(route('legalisir.stempel.index'));

        $stempel = StempelLegalisir::firstOrFail();
        $this->fileUji[] = $stempel->gambar;

        $this->assertSame('Stempel Panitia', $stempel->nama);
        $this->assertTrue($stempel->aktif);
        Storage::disk('public')->assertExists($stempel->gambar);
    }

    public function test_stempel_ditolak_bila_posisi_melewati_halaman(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('legalisir.stempel.store'), [
            'nama' => 'Stempel Terlalu Lebar',
            'gambar' => UploadedFile::fake()->image('stempel.png', 200, 200),
            'x_mm' => 290,
            'y_mm' => 20,
            'lebar_mm' => 40,
            'tinggi_mm' => 40,
            'aktif' => 1,
        ])->assertSessionHasErrors('lebar_mm');

        $this->assertDatabaseCount('stempel_legalisirs', 0);
    }

    public function test_admin_dapat_memperbarui_stempel_legalisir(): void
    {
        $stempel = $this->buatStempel(['nama' => 'Stempel Lama']);

        $this->actingAs($this->admin)
            ->put(route('legalisir.stempel.update', $stempel), [
                'nama' => 'Stempel Baru',
                'x_mm' => 200,
                'y_mm' => 30,
                'lebar_mm' => 50,
                'tinggi_mm' => 30,
                'aktif' => 1,
            ])
            ->assertRedirect(route('legalisir.stempel.index'));

        $stempel->refresh();

        $this->assertSame('Stempel Baru', $stempel->nama);
        $this->assertSame(200.0, $stempel->x_mm);
        $this->assertTrue($stempel->aktif);
    }

    public function test_admin_dapat_menghapus_stempel_legalisir(): void
    {
        $stempel = $this->buatStempel();
        $path = $stempel->gambar;

        $this->actingAs($this->admin)
            ->delete(route('legalisir.stempel.destroy', $stempel))
            ->assertRedirect(route('legalisir.stempel.index'));

        $this->assertDatabaseMissing('stempel_legalisirs', ['id' => $stempel->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_stempel_nonaktif_tidak_bisa_dipakai(): void
    {
        $peserta = $this->peserta();
        $stempel = $this->buatStempel(['aktif' => false]);

        $this->actingAs($this->admin)
            ->get(route('legalisir.cetak', [
                'ids' => [$peserta->id],
                'stempel_id' => $stempel->id,
            ]))
            ->assertStatus(422);

        $this->assertDatabaseMissing('print_logs', ['jenis_dokumen' => 'legalisir']);
    }

    public function test_legalisir_default_dua_copy(): void
    {
        $peserta = $this->peserta();
        $stempel = $this->buatStempel();

        $response = $this->actingAs($this->admin)->get(route('legalisir.cetak', [
            'ids' => [$peserta->id],
            'stempel_id' => $stempel->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertSame(2, $this->jumlahHalamanPdf($response->getContent()));

        $this->assertDatabaseHas('print_logs', [
            'jenis_dokumen' => 'legalisir',
            'jumlah_data' => 1,
            'jumlah_copy' => 2,
        ]);
        $this->assertDatabaseHas('pesertas', [
            'id' => $peserta->id,
            'no_sertifikat' => 1,
        ]);
    }

    public function test_jumlah_copy_mengikuti_pengaturan(): void
    {
        Setting::setValue('legalisir_jumlah_copy', '3');

        $a = $this->peserta('Budi Santoso');
        $b = $this->peserta('Citra Lestari', '2');
        $stempel = $this->buatStempel();

        $response = $this->actingAs($this->admin)->get(route('legalisir.cetak', [
            'ids' => [$a->id, $b->id],
            'stempel_id' => $stempel->id,
        ]));

        $response->assertOk();
        $this->assertSame(6, $this->jumlahHalamanPdf($response->getContent()));

        $this->assertDatabaseHas('print_logs', [
            'jenis_dokumen' => 'legalisir',
            'jumlah_data' => 2,
            'jumlah_copy' => 3,
        ]);
    }

    public function test_legalisir_memakai_blangko_peserta_aktif(): void
    {
        Blangko::create([
            'nama' => 'Blangko Legalisir',
            'jenis' => Blangko::JENIS_SERTIFIKAT,
            'mode' => Blangko::MODE_CANVAS,
            'lebar_mm' => 297,
            'tinggi_mm' => 210,
            'aktif' => true,
        ]);

        $peserta = $this->peserta();
        $stempel = $this->buatStempel();

        $response = $this->actingAs($this->admin)->get(route('legalisir.cetak', [
            'ids' => [$peserta->id],
            'stempel_id' => $stempel->id,
        ]));

        $response->assertOk();
        $this->assertSame(2, $this->jumlahHalamanPdf($response->getContent()));
        $this->assertDatabaseHas('print_logs', ['jenis_dokumen' => 'legalisir', 'jumlah_data' => 1]);
    }

    public function test_legalisir_menolak_semua_peserta_tanpa_juara(): void
    {
        $peserta = $this->peserta('Belum Juara', null);
        $stempel = $this->buatStempel();

        $this->actingAs($this->admin)
            ->get(route('legalisir.cetak', [
                'ids' => [$peserta->id],
                'stempel_id' => $stempel->id,
            ]))
            ->assertStatus(422);

        $this->assertDatabaseMissing('print_logs', ['jenis_dokumen' => 'legalisir']);
    }

    public function test_legalisir_melewati_peserta_tanpa_juara(): void
    {
        $valid = $this->peserta('Budi Santoso');
        $invalid = $this->peserta('Belum Juara', null);
        $stempel = $this->buatStempel();

        $response = $this->actingAs($this->admin)->get(route('legalisir.cetak', [
            'ids' => [$valid->id, $invalid->id],
            'stempel_id' => $stempel->id,
        ]));

        $response->assertOk();
        $response->assertSessionHas('warning');
        $this->assertSame(2, $this->jumlahHalamanPdf($response->getContent()));

        $this->assertDatabaseHas('print_logs', [
            'jenis_dokumen' => 'legalisir',
            'jumlah_data' => 1,
            'jumlah_copy' => 2,
        ]);
    }

    public function test_legalisir_dibatasi_maksimal_200_halaman(): void
    {
        Setting::setValue('legalisir_jumlah_copy', '20');

        $ids = [];

        for ($i = 1; $i <= 11; $i++) {
            $ids[] = $this->peserta('Peserta '.$i)->id;
        }

        $stempel = $this->buatStempel();

        $this->actingAs($this->admin)
            ->get(route('legalisir.cetak', ['ids' => $ids, 'stempel_id' => $stempel->id]))
            ->assertStatus(422);

        $this->assertDatabaseMissing('print_logs', ['jenis_dokumen' => 'legalisir']);
    }

    public function test_legalisir_dibatasi_maksimal_50_peserta(): void
    {
        $ids = [];

        for ($i = 1; $i <= 51; $i++) {
            $ids[] = $this->peserta('Peserta '.$i)->id;
        }

        $stempel = $this->buatStempel();

        $this->actingAs($this->admin)
            ->get(route('legalisir.cetak', ['ids' => $ids, 'stempel_id' => $stempel->id]))
            ->assertStatus(422);

        $this->assertDatabaseMissing('print_logs', ['jenis_dokumen' => 'legalisir']);
    }

    public function test_legalisir_menolak_stempel_tanpa_gambar(): void
    {
        $peserta = $this->peserta();
        $stempel = $this->buatStempel(['gambar' => 'legalisir-test/tidak-ada.png']);

        $this->actingAs($this->admin)
            ->get(route('legalisir.cetak', [
                'ids' => [$peserta->id],
                'stempel_id' => $stempel->id,
            ]))
            ->assertStatus(422);
    }

    public function test_legalisir_menolak_stempel_di_luar_halaman(): void
    {
        $peserta = $this->peserta();
        $stempel = $this->buatStempel(['x_mm' => 290, 'lebar_mm' => 40]);

        $this->actingAs($this->admin)
            ->get(route('legalisir.cetak', [
                'ids' => [$peserta->id],
                'stempel_id' => $stempel->id,
            ]))
            ->assertStatus(422);
    }

    public function test_pengaturan_jumlah_copy_legalisir(): void
    {
        $this->actingAs($this->admin)
            ->put(route('pengaturan.update'), [
                'nama_kejuaraan' => 'Kejuaraan Uji',
                'legalisir_jumlah_copy' => 4,
            ])
            ->assertRedirect(route('pengaturan.index'));

        $this->assertSame('4', Setting::getValue('legalisir_jumlah_copy'));
        $this->assertSame(4, LayananCetak::jumlahCopyLegalisir());

        $this->actingAs($this->admin)
            ->put(route('pengaturan.update'), [
                'nama_kejuaraan' => 'Kejuaraan Uji',
                'legalisir_jumlah_copy' => 0,
            ])
            ->assertSessionHasErrors('legalisir_jumlah_copy');
    }

    public function test_log_legalisir_menampilkan_jumlah_copy(): void
    {
        PrintLog::create([
            'user_id' => $this->admin->id,
            'jenis_dokumen' => 'legalisir',
            'jumlah_data' => 2,
            'jumlah_copy' => 3,
            'konteks' => 'Budi Santoso, Citra Lestari',
        ]);

        $this->actingAs($this->admin)
            ->get(route('log-cetak.index'))
            ->assertOk()
            ->assertSee('Legalisir')
            ->assertSee('3 copy');
    }

    public function test_grayscale_renderer_mengubah_gambar_dan_mempertahankan_stempel(): void
    {
        $path = 'legalisir-test/sumber-'.uniqid().'.png';
        Storage::disk('public')->put($path, $this->pngMerah());
        $this->fileUji[] = $path;

        $absolut = str_replace('\\', '/', public_path('storage/'.$path));
        $hasil = LegalisirRenderer::grayscalePath($absolut);

        $this->assertNotNull($hasil);
        $this->assertFileExists($hasil);

        $image = imagecreatefrompng($hasil);
        $this->assertNotFalse($image);
        $warna = imagecolorat($image, 0, 0);
        $r = ($warna >> 16) & 0xFF;
        $g = ($warna >> 8) & 0xFF;
        $b = $warna & 0xFF;
        imagedestroy($image);

        $this->assertSame($r, $g);
        $this->assertSame($g, $b);

        $html = '<img src="'.$absolut.'"><img src="'.$absolut.'" data-stempel-legalisir="1">';
        $html = LegalisirRenderer::grayscaleHtml($html);

        $this->assertStringContainsString('storage/app/legalisir', str_replace('\\', '/', $html));
        $this->assertStringContainsString('data-stempel-legalisir="1"', $html);

        @unlink($hasil);
    }
}
