<?php

namespace Tests\Feature;

use App\Models\Kontingen;
use App\Models\Peserta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KontingenTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_kontingen_index_lists_data(): void
    {
        Kontingen::create(['nama' => 'Kontingen Garuda', 'kota' => 'Kota Z']);

        $this->get(route('kontingen.index'))
            ->assertOk()
            ->assertSee('Kontingen Garuda')
            ->assertSee('Kota Z');
    }

    public function test_kontingen_can_be_created(): void
    {
        $this->post(route('kontingen.store'), [
            'nama' => 'Kontingen Sumbermaneg',
            'kota' => 'Kab. X',
        ])->assertRedirect(route('kontingen.index'));

        $this->assertDatabaseHas('kontingens', [
            'nama' => 'Kontingen Sumbermaneg',
            'kota' => 'Kab. X',
        ]);
    }

    public function test_kontingen_name_is_required(): void
    {
        $this->post(route('kontingen.store'), ['nama' => ''])
            ->assertSessionHasErrors('nama');

        $this->assertDatabaseCount('kontingens', 0);
    }

    public function test_kontingen_name_must_be_unique(): void
    {
        Kontingen::create(['nama' => 'Kontingen Garuda']);

        $this->post(route('kontingen.store'), ['nama' => 'Kontingen Garuda'])
            ->assertSessionHasErrors('nama');

        $this->assertDatabaseCount('kontingens', 1);
    }

    public function test_kontingen_can_be_updated(): void
    {
        $kontingen = Kontingen::create(['nama' => 'Kontingen Lama']);

        $this->put(route('kontingen.update', $kontingen), [
            'nama' => 'Kontingen Baru',
            'kota' => 'Kab. Y',
        ])->assertRedirect(route('kontingen.index'));

        $this->assertDatabaseHas('kontingens', [
            'id' => $kontingen->id,
            'nama' => 'Kontingen Baru',
            'kota' => 'Kab. Y',
        ]);
    }

    public function test_deleting_kontingen_also_deletes_its_peserta(): void
    {
        $kontingen = Kontingen::create(['nama' => 'Kontingen Garuda']);
        Peserta::create([
            'kontingen_id' => $kontingen->id,
            'nama' => 'Muhammad Rizky',
            'kategori' => 'Kata Perorangan Putra',
            'juara' => 1,
        ]);

        $this->delete(route('kontingen.destroy', $kontingen))
            ->assertRedirect(route('kontingen.index'));

        $this->assertDatabaseMissing('kontingens', ['id' => $kontingen->id]);
        $this->assertDatabaseMissing('pesertas', ['kontingen_id' => $kontingen->id]);
    }
}
