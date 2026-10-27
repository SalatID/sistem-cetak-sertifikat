<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_dapat_membuat_akun(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('user.store'), [
            'name' => 'Operator Baru',
            'email' => 'operator.baru@sistem.test',
            'role' => 'operator',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('user.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'operator.baru@sistem.test',
            'role' => 'operator',
        ]);
    }

    public function test_email_akun_harus_unik(): void
    {
        $this->actingAs($this->admin);

        User::factory()->create(['email' => 'kembar@sistem.test']);

        $this->post(route('user.store'), [
            'name' => 'Kembar',
            'email' => 'kembar@sistem.test',
            'role' => 'operator',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors('email');
    }

    public function test_operator_tidak_bisa_mengelola_akun(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)->get(route('user.index'))->assertForbidden();
    }

    public function test_akun_tidak_boleh_menghapus_diri_sendiri(): void
    {
        $this->actingAs($this->admin);

        $this->delete(route('user.destroy', $this->admin))->assertRedirect(route('user.index'));

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_admin_dapat_memperbarui_akun_tanpa_mengubah_password(): void
    {
        $this->actingAs($this->admin);

        $operator = User::factory()->create(['role' => 'operator', 'name' => 'Nama Lama']);

        $this->put(route('user.update', $operator), [
            'name' => 'Nama Baru',
            'email' => $operator->email,
            'role' => 'admin',
        ])->assertRedirect(route('user.index'));

        $operator->refresh();

        $this->assertSame('Nama Baru', $operator->name);
        $this->assertSame('admin', $operator->role);
    }
}
