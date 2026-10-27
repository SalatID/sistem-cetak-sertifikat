<?php

namespace Tests\Feature;

use App\Models\Kontingen;
use App\Models\Official;
use App\Models\User;
use App\Services\LayananCetak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficialTest extends TestCase
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

    public function test_official_dapat_disimpan_dan_sertifikatnya_dicetak(): void
    {
        $this->post(route('official.store'), [
            'kontingen_id' => $this->kontingen->id,
            'nama' => 'Coach Budi Hartono',
            'jabatan' => 'Coach',
        ])->assertRedirect(route('official.index'));

        $official = Official::firstOrFail();

        $this->assertDatabaseHas('officials', ['nama' => 'Coach Budi Hartono', 'jabatan' => 'Coach']);

        $response = $this->get(route('cetak.official.sertifikat', $official));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $this->assertDatabaseHas('print_logs', [
            'jenis_dokumen' => 'sertifikat_official',
            'official_id' => $official->id,
            'jumlah_data' => 1,
        ]);
    }

    public function test_admin_dapat_mencetak_sertifikat_official_tanpa_background(): void
    {
        $official = Official::create([
            'kontingen_id' => $this->kontingen->id,
            'nama' => 'Coach Budi Hartono',
            'jabatan' => 'Coach',
        ]);

        $this->mock(LayananCetak::class, function ($mock): void {
            $mock->shouldReceive('sertifikatOfficial')
                ->once()
                ->withArgs(fn ($officialArg, $userId, $pakaiBackground) => $pakaiBackground === false)
                ->andReturn(response('PDF', 200, ['content-type' => 'application/pdf']));
        });

        $this->get(route('cetak.official.sertifikat', [
            'official' => $official,
            'pakai_background' => 0,
        ]))->assertOk();
    }

    public function test_official_wajib_memilih_kontingen(): void
    {
        $this->post(route('official.store'), [
            'kontingen_id' => '',
            'nama' => 'Tanpa Kontingen',
            'jabatan' => 'Coach',
        ])->assertSessionHasErrors('kontingen_id');

        $this->assertDatabaseCount('officials', 0);
    }

    public function test_official_dapat_dihapus(): void
    {
        $official = Official::create([
            'kontingen_id' => $this->kontingen->id,
            'nama' => 'Coach Budi Hartono',
            'jabatan' => 'Coach',
        ]);

        $this->delete(route('official.destroy', $official))->assertRedirect(route('official.index'));

        $this->assertDatabaseMissing('officials', ['id' => $official->id]);
    }

    public function test_halaman_official_menampilkan_daftar(): void
    {
        Official::create([
            'kontingen_id' => $this->kontingen->id,
            'nama' => 'Coach Darmawan',
            'jabatan' => 'Official',
        ]);

        $this->get(route('official.index'))
            ->assertOk()
            ->assertSee('Coach Darmawan')
            ->assertSee('Official');
    }
}
