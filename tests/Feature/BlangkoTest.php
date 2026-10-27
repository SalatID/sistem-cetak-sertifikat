<?php

namespace Tests\Feature;

use App\Models\Blangko;
use App\Models\BlangkoElemen;
use App\Models\Kontingen;
use App\Models\Peserta;
use App\Models\TeksVarian;
use App\Models\User;
use App\Services\BlangkoRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlangkoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        Storage::fake('public');
    }

    public function test_admin_dapat_membuat_blangko_dengan_gambar(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('blangko.store'), [
            'nama' => 'Blangko Sertifikat Prestasi',
            'jenis' => 'sertifikat',
            'mode' => 'canvas',
            'ukuran_kertas' => 'a4',
            'orientasi' => 'landscape',
            'lebar_mm' => 297,
            'tinggi_mm' => 210,
            'gambar' => UploadedFile::fake()->image('blangko.png', 1200, 900),
        ])->assertRedirect();

        $blangko = Blangko::firstOrFail();

        $this->assertSame('Blangko Sertifikat Prestasi', $blangko->nama);
        $this->assertNotNull($blangko->gambar);
        Storage::disk('public')->assertExists($blangko->gambar);
    }

    public function test_operator_tidak_bisa_mengelola_blangko(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)->get(route('blangko.index'))->assertForbidden();
    }

    public function test_admin_dapat_menyimpan_elemen_dengan_posisi(): void
    {
        $this->actingAs($this->admin);

        $blangko = Blangko::create(['nama' => 'Blangko Uji', 'jenis' => 'sertifikat', 'lebar_mm' => 297, 'tinggi_mm' => 210]);

        $this->post(route('blangko.elemen.simpan', $blangko), [
            'label' => 'Nama Peserta',
            'tipe' => 'data',
            'sumber_field' => 'nama',
            'x_mm' => 40,
            'y_mm' => 80,
            'lebar_mm' => 120,
            'tinggi_mm' => 12,
            'font_family' => 'serif',
            'font_size' => 18,
            'align' => 'center',
            'valign' => 'middle',
            'tampil' => 1,
        ])->assertRedirect();

        $elemen = BlangkoElemen::firstOrFail();

        $this->assertSame('Nama Peserta', $elemen->label);
        $this->assertSame('data', $elemen->tipe);
        $this->assertSame(40, (int) $elemen->x_mm);
        $this->assertSame(18, (int) $elemen->font_size);
    }

    public function test_admin_dapat_menyimpan_varian_teks(): void
    {
        $this->actingAs($this->admin);

        $blangko = Blangko::create(['nama' => 'Blangko Varian', 'jenis' => 'sertifikat']);

        $this->post(route('blangko.varian.simpan', $blangko), [
            'key' => 'pemasalan',
            'judul' => 'Sertifikat Pemasalan',
            'isi' => 'Diberikan kepada {NAMA} atas pembelian merchande.',
        ])->assertRedirect();

        $this->assertDatabaseHas('teks_varians', [
            'blangko_id' => $blangko->id,
            'key' => 'pemasalan',
        ]);
    }

    public function test_varian_otomatis_dipilih_berdasarkan_kelompok_usia(): void
    {
        $this->assertSame(BlangkoRenderer::VARIAN_PEMASALAN, BlangkoRenderer::varianUntuk('Usia Dini 1'));
        $this->assertSame(BlangkoRenderer::VARIAN_PEMASALAN, BlangkoRenderer::varianUntuk('Pra Usia Dini'));
        $this->assertSame(BlangkoRenderer::VARIAN_PRESTASI, BlangkoRenderer::varianUntuk('Usia Dini 2'));
        $this->assertSame(BlangkoRenderer::VARIAN_PRESTASI, BlangkoRenderer::varianUntuk('Dewasa'));
        $this->assertSame(BlangkoRenderer::VARIAN_PRESTASI, BlangkoRenderer::varianUntuk(null));
    }

    public function test_renderer_menempatkan_data_dan_varian_pada_posisi(): void
    {
        $kontingen = Kontingen::create(['nama' => 'Kontingen Garuda']);
        $peserta = Peserta::create([
            'kontingen_id' => $kontingen->id,
            'nama' => 'Budi Santoso',
            'kategori' => 'Kata Putra',
            'kelompok_usia' => 'Usia Dini 1',
            'juara' => 1,
        ]);

        $blangko = Blangko::create(['nama' => 'Blangko Render', 'jenis' => 'sertifikat', 'lebar_mm' => 297, 'tinggi_mm' => 210]);
        BlangkoElemen::create([
            'blangko_id' => $blangko->id,
            'label' => 'Nama',
            'tipe' => 'data',
            'sumber_field' => 'nama',
            'x_mm' => 40,
            'y_mm' => 80,
            'lebar_mm' => 120,
            'tinggi_mm' => 12,
            'font_family' => 'serif',
            'font_size' => 18,
            'align' => 'center',
            'valign' => 'middle',
            'warna' => '#000000',
            'z_index' => 10,
            'tampil' => true,
        ]);
        BlangkoElemen::create([
            'blangko_id' => $blangko->id,
            'label' => 'Teks Sertifikat',
            'tipe' => 'varian',
            'varian_key' => 'pemasalan',
            'x_mm' => 40,
            'y_mm' => 100,
            'lebar_mm' => 120,
            'tinggi_mm' => 12,
            'font_family' => 'sans',
            'font_size' => 11,
            'align' => 'center',
            'valign' => 'top',
            'warna' => '#000000',
            'z_index' => 10,
            'tampil' => true,
        ]);
        TeksVarian::create([
            'blangko_id' => $blangko->id,
            'key' => 'pemasalan',
            'judul' => 'Sertifikat Pemasalan',
            'isi' => 'Diberikan kepada {NAMA} atas pembelian merchande.',
        ]);

        $varian = BlangkoRenderer::varianUntuk($peserta->kelompok_usia);
        $html = BlangkoRenderer::render($blangko, BlangkoRenderer::dataPeserta($peserta), null, $varian);

        $this->assertStringContainsString('Budi Santoso', $html);
        $this->assertStringContainsString('Diberikan kepada Budi Santoso atas pembelian merchande.', $html);
        $this->assertStringContainsString('left:40mm', $html);
        $this->assertStringContainsString('top:80mm', $html);
    }

    public function test_cetak_sertifikat_menggunakan_blangko_aktif(): void
    {
        $this->actingAs($this->admin);

        $kontingen = Kontingen::create(['nama' => 'Kontingen Garuda']);
        $peserta = Peserta::create([
            'kontingen_id' => $kontingen->id,
            'nama' => 'Budi Santoso',
            'kategori' => 'Kata Putra',
            'juara' => 1,
        ]);

        $blangko = Blangko::create(['nama' => 'Blangko Aktif', 'jenis' => 'sertifikat', 'lebar_mm' => 297, 'tinggi_mm' => 210]);
        BlangkoElemen::create([
            'blangko_id' => $blangko->id,
            'label' => 'Nama',
            'tipe' => 'data',
            'sumber_field' => 'nama',
            'x_mm' => 40,
            'y_mm' => 80,
            'lebar_mm' => 120,
            'tinggi_mm' => 12,
            'font_family' => 'serif',
            'font_size' => 18,
            'align' => 'center',
            'valign' => 'middle',
            'warna' => '#000000',
            'z_index' => 10,
            'tampil' => true,
        ]);

        $response = $this->get(route('cetak.peserta.sertifikat', ['ids' => [$peserta->id]]));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_preview_blangko_dapat_diakses_admin(): void
    {
        $this->actingAs($this->admin);

        $blangko = Blangko::create(['nama' => 'Blangko Preview', 'jenis' => 'sertifikat']);

        $this->get(route('blangko.preview', $blangko))->assertOk();
    }

    /**
     * Data elemen untuk pengujian edit/hapus.
     *
     * @return array<string, mixed>
     */
    private function dataElemen(array $ubah = []): array
    {
        return array_merge([
            'label' => 'Nama Peserta',
            'tipe' => 'data',
            'sumber_field' => 'nama',
            'x_mm' => 40,
            'y_mm' => 80,
            'lebar_mm' => 120,
            'tinggi_mm' => 12,
            'font_family' => 'serif',
            'font_size' => 18,
            'align' => 'center',
            'valign' => 'middle',
            'warna' => '#000000',
            'z_index' => 10,
            'tampil' => 1,
        ], $ubah);
    }

    public function test_admin_dapat_mengubah_elemen_yang_sudah_ada(): void
    {
        $this->actingAs($this->admin);

        $blangko = Blangko::create(['nama' => 'Blangko Edit', 'jenis' => 'sertifikat']);
        $elemen = $blangko->elemen()->create($this->dataElemen());

        $this->post(route('blangko.elemen.simpan', $blangko), $this->dataElemen([
            'elemen_id' => $elemen->id,
            'label' => 'Nama Juara',
            'x_mm' => 55,
            'y_mm' => 95,
            'font_size' => 24,
        ]))->assertRedirect();

        $elemen->refresh();

        $this->assertSame('Nama Juara', $elemen->label);
        $this->assertSame(55, (int) $elemen->x_mm);
        $this->assertSame(95, (int) $elemen->y_mm);
        $this->assertSame(24, (int) $elemen->font_size);

        // Update tidak boleh membuat elemen baru.
        $this->assertSame(1, $blangko->elemen()->count());
    }

    public function test_admin_dapat_mengubah_elemen_lewat_ajax(): void
    {
        $this->actingAs($this->admin);

        $blangko = Blangko::create(['nama' => 'Blangko Ajax', 'jenis' => 'sertifikat']);
        $elemen = $blangko->elemen()->create($this->dataElemen());

        $this->postJson(route('blangko.elemen.simpan', $blangko), $this->dataElemen([
            'elemen_id' => $elemen->id,
            'x_mm' => 70,
            'y_mm' => 110,
            'tampil' => 1,
        ]))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('baru', false)
            ->assertJsonPath('elemen.id', $elemen->id);

        $this->assertSame(70, (int) $elemen->fresh()->x_mm);
        $this->assertSame(110, (int) $elemen->fresh()->y_mm);
    }

    public function test_admin_dapat_menghapus_elemen(): void
    {
        $this->actingAs($this->admin);

        $blangko = Blangko::create(['nama' => 'Blangko Hapus', 'jenis' => 'sertifikat']);
        $elemen = $blangko->elemen()->create($this->dataElemen());

        $this->delete(route('blangko.elemen.hapus', [$blangko, $elemen]))->assertRedirect();

        $this->assertDatabaseMissing('blangko_elemen', ['id' => $elemen->id]);
        $this->assertSame(0, $blangko->elemen()->count());
    }

    public function test_hapus_elemen_lewat_ajax_mengembalikan_json(): void
    {
        $this->actingAs($this->admin);

        $blangko = Blangko::create(['nama' => 'Blangko Hapus Ajax', 'jenis' => 'sertifikat']);
        $elemen = $blangko->elemen()->create($this->dataElemen());

        $this->deleteJson(route('blangko.elemen.hapus', [$blangko, $elemen]))
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseMissing('blangko_elemen', ['id' => $elemen->id]);
    }

    public function test_elemen_milik_blangko_lain_tidak_bisa_dihapus(): void
    {
        $this->actingAs($this->admin);

        $blangkoA = Blangko::create(['nama' => 'Blangko A', 'jenis' => 'sertifikat']);
        $blangkoB = Blangko::create(['nama' => 'Blangko B', 'jenis' => 'sertifikat']);
        $elemenB = $blangkoB->elemen()->create($this->dataElemen());

        $this->delete(route('blangko.elemen.hapus', [$blangkoA, $elemenB]))->assertNotFound();

        $this->assertDatabaseHas('blangko_elemen', ['id' => $elemenB->id]);
    }

    /* ------------------------------------------------------------------ */
    /* Mode Blade */
    /* ------------------------------------------------------------------ */

    public function test_pilihan_template_hanya_menampilkan_file_cetak(): void
    {
        $pilihan = Blangko::pilihanTemplate();

        $this->assertArrayHasKey('blangko.contoh-sertifikat', $pilihan);
        $this->assertArrayHasKey('blangko.contoh-sk', $pilihan);

        // Halaman admin tidak boleh muncul sebagai pilihan template cetak.
        $this->assertArrayNotHasKey('blangko.index', $pilihan);
        $this->assertArrayNotHasKey('blangko.form', $pilihan);
        $this->assertArrayNotHasKey('blangko.desainer', $pilihan);
        $this->assertArrayNotHasKey('blangko.preview', $pilihan);
        $this->assertArrayNotHasKey('blangko.blade', $pilihan);
    }

    public function test_admin_dapat_membuat_blangko_mode_blade(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('blangko.store'), [
            'nama' => 'Sertifikat Blade',
            'jenis' => 'sertifikat',
            'mode' => 'blade',
            'view_blade' => 'blangko.contoh-sertifikat',
            'ukuran_kertas' => 'a4',
            'orientasi' => 'landscape',
            'lebar_mm' => 297,
            'tinggi_mm' => 210,
        ])->assertRedirect();

        $blangko = Blangko::firstOrFail();

        $this->assertTrue($blangko->pakaiBlade());
        $this->assertSame('blangko.contoh-sertifikat', $blangko->view_blade);
        $this->assertNotNull($blangko->pathTemplate());
        $this->assertFileExists($blangko->pathTemplate());
    }

    public function test_mode_blade_wajib_memilih_file_template(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('blangko.store'), [
            'nama' => 'Blade Tanpa File',
            'jenis' => 'sertifikat',
            'mode' => 'blade',
            'view_blade' => '',
            'ukuran_kertas' => 'a4',
            'orientasi' => 'landscape',
            'lebar_mm' => 297,
            'tinggi_mm' => 210,
        ])->assertSessionHasErrors('view_blade');

        $this->assertDatabaseCount('blangkos', 0);
    }

    public function test_template_di_luar_folder_blangko_ditolak(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('blangko.store'), [
            'nama' => 'Template Ilegal',
            'jenis' => 'sertifikat',
            'mode' => 'blade',
            'view_blade' => 'pdf.sertifikat-peserta',
            'ukuran_kertas' => 'a4',
            'orientasi' => 'landscape',
            'lebar_mm' => 297,
            'tinggi_mm' => 210,
        ])->assertSessionHasErrors('view_blade');
    }

    public function test_blangko_kanvas_tidak_menyimpan_view_blade(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('blangko.store'), [
            'nama' => 'Kanvas Biasa',
            'jenis' => 'sertifikat',
            'mode' => 'canvas',
            'view_blade' => 'blangko.contoh-sertifikat',
            'ukuran_kertas' => 'a4',
            'orientasi' => 'landscape',
            'lebar_mm' => 297,
            'tinggi_mm' => 210,
        ])->assertRedirect();

        $this->assertNull(Blangko::firstOrFail()->view_blade);
    }

    public function test_ukuran_kertas_preset_menghitung_dimensi_dari_orientasi(): void
    {
        $this->assertSame(['lebar_mm' => 330, 'tinggi_mm' => 215], Blangko::dimensiUkuran('f4', 'landscape'));
        $this->assertSame(['lebar_mm' => 215, 'tinggi_mm' => 330], Blangko::dimensiUkuran('f4', 'portrait'));
        $this->assertSame(['lebar_mm' => 279, 'tinggi_mm' => 216], Blangko::dimensiUkuran('letter', 'landscape'));
        $this->assertSame(['lebar_mm' => 297, 'tinggi_mm' => 210], Blangko::dimensiUkuran('a4', 'landscape'));
        $this->assertNull(Blangko::dimensiUkuran('custom', 'landscape'));
    }

    public function test_simpan_blangko_f4_mengabaikan_input_mm_manual(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('blangko.store'), [
            'nama' => 'Blangko F4',
            'jenis' => 'sertifikat',
            'mode' => 'canvas',
            'ukuran_kertas' => 'f4',
            'orientasi' => 'portrait',
            'lebar_mm' => 999,
            'tinggi_mm' => 999,
        ])->assertRedirect();

        $blangko = Blangko::firstOrFail();

        $this->assertSame(215, $blangko->lebar_mm);
        $this->assertSame(330, $blangko->tinggi_mm);
    }

    public function test_ukuran_custom_wajib_mengisi_dimensi(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('blangko.store'), [
            'nama' => 'Blangko Custom',
            'jenis' => 'sertifikat',
            'mode' => 'canvas',
            'ukuran_kertas' => 'custom',
            'orientasi' => 'portrait',
        ])->assertSessionHasErrors('lebar_mm');

        $this->assertDatabaseCount('blangkos', 0);
    }

    public function test_cetak_kanvas_memakai_ukuran_kertas_f4(): void
    {
        $this->actingAs($this->admin);

        $kontingen = Kontingen::create(['nama' => 'Kontingen Garuda']);
        $peserta = Peserta::create([
            'kontingen_id' => $kontingen->id,
            'nama' => 'Budi Santoso',
            'jenis_kelamin' => 'L',
            'kategori' => 'Kata Putra',
            'juara' => 1,
        ]);

        Blangko::create([
            'nama' => 'Kanvas F4',
            'jenis' => 'sertifikat',
            'mode' => 'canvas',
            'ukuran_kertas' => 'f4',
            'orientasi' => 'landscape',
            'lebar_mm' => 330,
            'tinggi_mm' => 215,
            'aktif' => true,
        ]);

        $response = $this->get(route('cetak.peserta.sertifikat', ['ids' => [$peserta->id]]));

        $response->assertOk();

        preg_match('/\/MediaBox\s*\[\s*[0-9.]+\s+[0-9.]+\s+([0-9.]+)\s+([0-9.]+)\s*\]/', $response->getContent(), $cocok);

        $this->assertNotEmpty($cocok, 'MediaBox PDF tidak ditemukan.');
        $this->assertEqualsWithDelta(330 * 72 / 25.4, (float) $cocok[1], 0.5);
        $this->assertEqualsWithDelta(215 * 72 / 25.4, (float) $cocok[2], 0.5);
    }

    public function test_edit_blangko_blade_menampilkan_editor_template(): void
    {
        $this->actingAs($this->admin);

        $blangko = Blangko::create([
            'nama' => 'Blade Editor',
            'jenis' => 'sertifikat',
            'mode' => 'blade',
            'view_blade' => 'blangko.contoh-sertifikat',
        ]);

        $this->get(route('blangko.edit', $blangko))
            ->assertOk()
            ->assertSee('Mode Blade')
            ->assertSee('contoh-sertifikat');
    }

    public function test_preview_blangko_blade_merender_template(): void
    {
        $this->actingAs($this->admin);

        $blangko = Blangko::create([
            'nama' => 'Blade Preview',
            'jenis' => 'sertifikat',
            'mode' => 'blade',
            'view_blade' => 'blangko.contoh-sertifikat',
        ]);

        $this->get(route('blangko.preview', $blangko))
            ->assertOk()
            ->assertSee('SERTIFIKAT PRESTASI')
            ->assertSee('NAMA PESERTA');
    }

    public function test_render_blade_mengisi_data_dan_teks_varian(): void
    {
        $kontingen = Kontingen::create(['nama' => 'Kontingen Garuda']);
        $peserta = Peserta::create([
            'kontingen_id' => $kontingen->id,
            'nama' => 'Budi Santoso',
            'jenis_kelamin' => 'L',
            'kategori' => 'Kata Putra',
            'kelas' => '-60 kg',
            'kelompok_usia' => 'Dewasa',
            'juara' => 1,
        ]);

        $blangko = Blangko::create([
            'nama' => 'Blade Render',
            'jenis' => 'sertifikat',
            'mode' => 'blade',
            'view_blade' => 'blangko.contoh-sertifikat',
            'ukuran_kertas' => 'a4',
            'orientasi' => 'landscape',
            'lebar_mm' => 297,
            'tinggi_mm' => 210,
        ]);

        TeksVarian::create([
            'blangko_id' => $blangko->id,
            'key' => 'prestasi',
            'judul' => 'Sertifikat Prestasi',
            'isi' => 'Juara {JUARA_ROMAWI} pada {KATEGORI} mewakili {KONTINGEN}.',
        ]);

        $data = BlangkoRenderer::dataPeserta($peserta);

        $this->assertSame('L', $data['jenis_kelamin']);
        $this->assertSame('Putra', $data['jenis_kelamin_teks']);

        $html = BlangkoRenderer::renderBlade(
            $blangko,
            $data,
            'prestasi',
            [],
            BlangkoRenderer::kop(),
        );

        $this->assertStringContainsString('SERTIFIKAT PRESTASI', $html);
        $this->assertStringContainsString('Budi Santoso', $html);
        $this->assertStringContainsString('Juara I pada Kata Putra mewakili Kontingen Garuda.', $html);
        $this->assertStringContainsString('297mm', $html);
    }

    public function test_render_blade_bisa_tanpa_background(): void
    {
        $kontingen = Kontingen::create(['nama' => 'Kontingen Garuda']);
        $peserta = Peserta::create([
            'kontingen_id' => $kontingen->id,
            'nama' => 'Budi Santoso',
            'jenis_kelamin' => 'L',
            'kategori' => 'Kata Putra',
            'kelompok_usia' => 'USIA DINI 1',
            'juara' => 1,
        ]);

        $blangko = Blangko::create([
            'nama' => 'Blade Background',
            'jenis' => 'sertifikat',
            'mode' => 'blade',
            'view_blade' => 'blangko.contoh-sbcc',
            'ukuran_kertas' => 'a4',
            'orientasi' => 'landscape',
            'lebar_mm' => 297,
            'tinggi_mm' => 210,
        ]);

        $data = BlangkoRenderer::dataPeserta($peserta);
        $kop = BlangkoRenderer::kop();

        $denganBackground = BlangkoRenderer::renderBlade(
            $blangko,
            $data,
            BlangkoRenderer::VARIAN_PEMASALAN,
            [],
            $kop,
            true,
        );

        $tanpaBackground = BlangkoRenderer::renderBlade(
            $blangko,
            $data,
            BlangkoRenderer::VARIAN_PEMASALAN,
            [],
            $kop,
            false,
        );

        $this->assertStringContainsString('blangko pemasalan', $denganBackground);
        $this->assertStringNotContainsString('blangko pemasalan', $tanpaBackground);
        $this->assertStringContainsString('background-image:none', $tanpaBackground);

        // Gambar latar harus berupa path file, bukan base64 yang disalin ke setiap halaman.
        $this->assertStringNotContainsString('data:image', $denganBackground);
        $this->assertStringContainsString('blangko-assets/sbckc2.jpg', $denganBackground);
    }

    public function test_render_blade_contoh_sertifikat_tanpa_background_kop(): void
    {
        $kontingen = Kontingen::create(['nama' => 'Kontingen Garuda']);
        $peserta = Peserta::create([
            'kontingen_id' => $kontingen->id,
            'nama' => 'Budi Santoso',
            'jenis_kelamin' => 'L',
            'kategori' => 'Kata Putra',
            'kelompok_usia' => 'Dewasa',
            'juara' => 1,
        ]);

        $blangko = Blangko::create([
            'nama' => 'Blade Kop Background',
            'jenis' => 'sertifikat',
            'mode' => 'blade',
            'view_blade' => 'blangko.contoh-sertifikat',
            'ukuran_kertas' => 'a4',
            'orientasi' => 'landscape',
            'lebar_mm' => 297,
            'tinggi_mm' => 210,
        ]);

        $data = BlangkoRenderer::dataPeserta($peserta);
        $kop = BlangkoRenderer::kop();
        $kop['background'] = 'data:image/png;base64,CONTOH';

        $denganBackground = BlangkoRenderer::renderBlade(
            $blangko,
            $data,
            BlangkoRenderer::VARIAN_PRESTASI,
            [],
            $kop,
            true,
        );

        $tanpaBackground = BlangkoRenderer::renderBlade(
            $blangko,
            $data,
            BlangkoRenderer::VARIAN_PRESTASI,
            [],
            $kop,
            false,
        );

        $this->assertStringContainsString('data:image/png;base64,CONTOH', $denganBackground);
        $this->assertStringNotContainsString('data:image/png;base64,CONTOH', $tanpaBackground);
    }

    public function test_cetak_bulk_memakai_blangko_blade_satu_halaman_per_peserta(): void
    {
        $this->actingAs($this->admin);

        $kontingen = Kontingen::create(['nama' => 'Kontingen Garuda']);

        $peserta = collect(['Budi Santoso', 'Citra Lestari'])->map(fn (string $nama) => Peserta::create([
            'kontingen_id' => $kontingen->id,
            'nama' => $nama,
            'kategori' => 'Kata Putra',
            'kelompok_usia' => 'Dewasa',
            'juara' => 1,
        ]));

        Blangko::create([
            'nama' => 'Blade Cetak',
            'jenis' => 'sertifikat',
            'mode' => 'blade',
            'view_blade' => 'blangko.contoh-sbcc',
            'ukuran_kertas' => 'a4',
            'orientasi' => 'landscape',
            'lebar_mm' => 297,
            'tinggi_mm' => 210,
            'aktif' => true,
        ]);

        $response = $this->get(route('cetak.peserta.sertifikat', ['ids' => $peserta->pluck('id')->all()]));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertSame(2, $this->jumlahHalamanPdf($response->getContent()));
    }

    public function test_cetak_blangko_blade_memakai_ukuran_kertas_f4(): void
    {
        $this->actingAs($this->admin);

        $kontingen = Kontingen::create(['nama' => 'Kontingen Garuda']);

        $peserta = Peserta::create([
            'kontingen_id' => $kontingen->id,
            'nama' => 'Budi Santoso',
            'jenis_kelamin' => 'L',
            'kategori' => 'Kata Putra',
            'kelompok_usia' => 'Dewasa',
            'juara' => 1,
        ]);

        Blangko::create([
            'nama' => 'Blade F4',
            'jenis' => 'sertifikat',
            'mode' => 'blade',
            'view_blade' => 'blangko.contoh-sertifikat',
            'ukuran_kertas' => 'f4',
            'orientasi' => 'landscape',
            'lebar_mm' => 330,
            'tinggi_mm' => 215,
            'aktif' => true,
        ]);

        $response = $this->get(route('cetak.peserta.sertifikat', ['ids' => [$peserta->id]]));

        $response->assertOk();

        preg_match('/\/MediaBox\s*\[\s*[0-9.]+\s+[0-9.]+\s+([0-9.]+)\s+([0-9.]+)\s*\]/', $response->getContent(), $cocok);

        $this->assertNotEmpty($cocok, 'MediaBox PDF tidak ditemukan.');
        $this->assertEqualsWithDelta(330 * 72 / 25.4, (float) $cocok[1], 0.5);
        $this->assertEqualsWithDelta(215 * 72 / 25.4, (float) $cocok[2], 0.5);
        $this->assertSame(1, $this->jumlahHalamanPdf($response->getContent()));
    }

    public function test_cetak_sk_blangko_blade_satu_peserta_satu_halaman(): void
    {
        $this->actingAs($this->admin);

        $kontingen = Kontingen::create(['nama' => 'Kontingen Garuda']);

        $peserta = collect(['Budi Santoso', 'Citra Lestari'])->map(fn (string $nama) => Peserta::create([
            'kontingen_id' => $kontingen->id,
            'nama' => $nama,
            'kategori' => 'Kata Putra',
            'kelompok_usia' => 'Dewasa',
            'juara' => 1,
            'no_sk' => '001/SK/SKX26/III/2026',
        ]));

        Blangko::create([
            'nama' => 'Blade SK',
            'jenis' => 'sk',
            'mode' => 'blade',
            'view_blade' => 'blangko.contoh-sk-sbcc',
            'orientasi' => 'portrait',
            'lebar_mm' => 210,
            'tinggi_mm' => 297,
            'aktif' => true,
        ]);

        $response = $this->get(route('cetak.peserta.sk', ['ids' => $peserta->pluck('id')->all()]));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertSame(2, $this->jumlahHalamanPdf($response->getContent()));
    }

    public function test_cetak_sk_blangko_blade_tanpa_nomor_sk_tetap_dicetak(): void
    {
        $this->actingAs($this->admin);

        $kontingen = Kontingen::create(['nama' => 'Kontingen Garuda']);

        $peserta = collect(['Budi Santoso', 'Citra Lestari'])->map(fn (string $nama) => Peserta::create([
            'kontingen_id' => $kontingen->id,
            'nama' => $nama,
            'kategori' => 'Kata Putra',
            'kelompok_usia' => 'Dewasa',
            'juara' => 1,
            'no_sk' => null,
        ]));

        Blangko::create([
            'nama' => 'Blade SK Tanpa Nomor',
            'jenis' => 'sk',
            'mode' => 'blade',
            'view_blade' => 'blangko.contoh-sk-sbcc',
            'orientasi' => 'portrait',
            'lebar_mm' => 210,
            'tinggi_mm' => 297,
            'aktif' => true,
        ]);

        $response = $this->get(route('cetak.peserta.sk', ['ids' => $peserta->pluck('id')->all()]));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertSame(2, $this->jumlahHalamanPdf($response->getContent()));
    }

    public function test_template_sk_contoh_sbcc_memakai_format_satu_peserta(): void
    {
        $kontingen = Kontingen::create(['nama' => 'Kontingen Garuda']);

        $peserta = Peserta::create([
            'kontingen_id' => $kontingen->id,
            'nama' => 'Budi Santoso',
            'jenis_kelamin' => 'L',
            'kategori' => 'Kata Putra',
            'kelompok_usia' => 'Dewasa',
            'juara' => 1,
            'no_sk' => '001/SK/SKX26/III/2026',
        ]);

        $blangko = Blangko::create([
            'nama' => 'Blade SK SBCC',
            'jenis' => 'sk',
            'mode' => 'blade',
            'view_blade' => 'blangko.contoh-sk-sbcc',
            'orientasi' => 'portrait',
            'lebar_mm' => 210,
            'tinggi_mm' => 297,
            'aktif' => true,
        ]);

        $html = BlangkoRenderer::renderBlade(
            $blangko,
            BlangkoRenderer::dataPeserta($peserta),
            BlangkoRenderer::VARIAN_PRESTASI,
            [],
            BlangkoRenderer::kop(),
        );

        $this->assertStringContainsString('Budi Santoso', $html);
        $this->assertStringContainsString('Juara 1 Kategori Kata Putra Dewasa Putra', $html);
        $this->assertStringContainsString('Pelaksanaan', $html);
        $this->assertStringContainsString('Mall Bale Kota, Kota tangerang', $html);

        // Gambar latar SK juga memakai path file, bukan base64.
        $this->assertStringNotContainsString('data:image', $html);
        $this->assertStringContainsString('blangko-assets/sk-sbckc2.jpg', $html);
    }

    private function jumlahHalamanPdf(string $isi): int
    {
        return preg_match_all('/\/Type\s*\/Page[^s]/', $isi);
    }
}
