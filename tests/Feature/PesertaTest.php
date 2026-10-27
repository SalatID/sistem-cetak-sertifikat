<?php

namespace Tests\Feature;

use App\Models\Kontingen;
use App\Models\Peserta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PesertaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Kontingen $kontingen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->kontingen = Kontingen::create(['nama' => 'Kontingen Garuda', 'kota' => 'Kota Z']);
    }

    public function test_peserta_index_lists_data_with_juara_badge(): void
    {
        Peserta::create([
            'kontingen_id' => $this->kontingen->id,
            'nama' => 'Muhammad Rizky',
            'kategori' => 'Kata Perorangan Putra',
            'juara' => 1,
        ]);

        $this->get(route('peserta.index'))
            ->assertOk()
            ->assertSee('Muhammad Rizky')
            ->assertSee('Kontingen Garuda')
            ->assertSee('Emas');
    }

    public function test_tombol_cetak_sertifikat_tampil_untuk_semua_yang_punya_keterangan_juara(): void
    {
        $juara = Peserta::create([
            'kontingen_id' => $this->kontingen->id,
            'nama' => 'Muhammad Rizky',
            'kategori' => 'Kata',
            'juara' => 'JUARA 1',
        ]);
        $peserta = Peserta::create([
            'kontingen_id' => $this->kontingen->id,
            'nama' => 'Peserta Saja',
            'kategori' => 'Kata',
            'juara' => 'PESERTA',
        ]);
        $belumJuara = Peserta::create([
            'kontingen_id' => $this->kontingen->id,
            'nama' => 'Belum Juara',
            'kategori' => 'Kata',
            'juara' => null,
        ]);

        $response = $this->get(route('peserta.index'))
            ->assertOk()
            ->assertSee(route('cetak.peserta.sertifikat', ['ids' => [$juara->id]]), false)
            ->assertSee(route('cetak.peserta.sertifikat', ['ids' => [$peserta->id]]), false)
            ->assertDontSee(route('cetak.peserta.sertifikat', ['ids' => [$belumJuara->id]]), false);

        // Cetak satuan (link per baris) dan bulk (form) harus membuka tab baru.
        $response->assertSee(
            '<form id="form-cetak" method="GET" action="'.route('cetak.peserta.sertifikat').'" target="_blank"',
            false,
        );
        $response->assertSee('target="_blank"', false);
    }

    public function test_peserta_can_be_created(): void
    {
        $this->post(route('peserta.store'), [
            'kontingen_id' => $this->kontingen->id,
            'nama' => 'Andi Saputra',
            'jenis_kelamin' => 'L',
            'kategori' => 'Kumite -60 kg Putra',
            'kelas' => '-60 kg',
            'juara' => 'Pesilat Terbaik',
            'no_sk' => '001/SK/SKX26/III/2026',
        ])->assertRedirect(route('peserta.index'));

        $this->assertDatabaseHas('pesertas', [
            'kontingen_id' => $this->kontingen->id,
            'nama' => 'Andi Saputra',
            'kategori' => 'Kumite -60 kg Putra',
            'juara' => 'Pesilat Terbaik',
            'no_sk' => '001/SK/SKX26/III/2026',
        ]);
    }

    public function test_peserta_requires_kontingen_nama_and_kategori(): void
    {
        $this->post(route('peserta.store'), [
            'kontingen_id' => '',
            'nama' => '',
            'kategori' => '',
        ])->assertSessionHasErrors(['kontingen_id', 'nama', 'kategori']);

        $this->assertDatabaseCount('pesertas', 0);
    }

    public function test_peserta_juara_maksimal_100_karakter(): void
    {
        $this->post(route('peserta.store'), [
            'kontingen_id' => $this->kontingen->id,
            'nama' => 'Andi Saputra',
            'kategori' => 'Kumite -60 kg Putra',
            'juara' => str_repeat('x', 101),
        ])->assertSessionHasErrors('juara');
    }

    public function test_peserta_index_can_be_filtered_by_kontingen(): void
    {
        $lain = Kontingen::create(['nama' => 'Kontingen Lain']);

        Peserta::create([
            'kontingen_id' => $this->kontingen->id,
            'nama' => 'Muhammad Rizky',
            'kategori' => 'Kata',
        ]);
        Peserta::create([
            'kontingen_id' => $lain->id,
            'nama' => 'Zainuddin',
            'kategori' => 'Kata',
        ]);

        $this->get(route('peserta.index', ['kontingen_id' => $this->kontingen->id]))
            ->assertOk()
            ->assertSee('Muhammad Rizky')
            ->assertDontSee('Zainuddin');
    }

    public function test_form_edit_memilih_kelompok_usia_walau_data_lama_huruf_besar(): void
    {
        $peserta = Peserta::create([
            'kontingen_id' => $this->kontingen->id,
            'nama' => 'Muhammad Rizky',
            'kategori' => 'Kata',
            'kelompok_usia' => 'PRA REMAJA',
        ]);

        $this->get(route('peserta.edit', $peserta))
            ->assertOk()
            ->assertSee('value="Pra Remaja" selected', false);
    }

    public function test_peserta_index_can_be_filtered_by_kelompok_usia(): void
    {
        Peserta::create([
            'kontingen_id' => $this->kontingen->id,
            'nama' => 'Budi Santoso',
            'kategori' => 'Kata',
            'kelompok_usia' => 'Usia Dini 1',
        ]);
        Peserta::create([
            'kontingen_id' => $this->kontingen->id,
            'nama' => 'Citra Lestari',
            'kategori' => 'Kata',
            'kelompok_usia' => 'Dewasa',
        ]);

        $this->get(route('peserta.index', ['kelompok_usia' => 'Usia Dini 1']))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertDontSee('Citra Lestari');
    }

    public function test_peserta_index_can_be_filtered_by_juara(): void
    {
        Peserta::create([
            'kontingen_id' => $this->kontingen->id,
            'nama' => 'Budi Santoso',
            'kategori' => 'Kata',
            'juara' => 1,
        ]);
        Peserta::create([
            'kontingen_id' => $this->kontingen->id,
            'nama' => 'Citra Lestari',
            'kategori' => 'Kata',
        ]);

        $this->get(route('peserta.index', ['juara' => '1']))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertDontSee('Citra Lestari');

        $this->get(route('peserta.index', ['juara' => '-']))
            ->assertOk()
            ->assertSee('Citra Lestari')
            ->assertDontSee('Budi Santoso');
    }

    public function test_peserta_index_can_be_searched_by_name(): void
    {
        Peserta::create([
            'kontingen_id' => $this->kontingen->id,
            'nama' => 'Salsabila Putri',
            'kategori' => 'Kata',
        ]);
        Peserta::create([
            'kontingen_id' => $this->kontingen->id,
            'nama' => 'Nadia Kirana',
            'kategori' => 'Kata',
        ]);

        $this->get(route('peserta.index', ['q' => 'Salsabila']))
            ->assertOk()
            ->assertSee('Salsabila Putri')
            ->assertDontSee('Nadia Kirana');
    }

    public function test_peserta_can_be_updated_and_deleted(): void
    {
        $peserta = Peserta::create([
            'kontingen_id' => $this->kontingen->id,
            'nama' => 'Nadia Kirana',
            'kategori' => 'Kumite -50 kg Putri',
        ]);

        $this->put(route('peserta.update', $peserta), [
            'kontingen_id' => $this->kontingen->id,
            'nama' => 'Nadia Kirana',
            'kategori' => 'Kumite -50 kg Putri',
            'juara' => '3',
        ])->assertRedirect(route('peserta.index'));

        $this->assertDatabaseHas('pesertas', ['id' => $peserta->id, 'juara' => '3']);

        $this->delete(route('peserta.destroy', $peserta))
            ->assertRedirect(route('peserta.index'));

        $this->assertDatabaseMissing('pesertas', ['id' => $peserta->id]);
    }
}
