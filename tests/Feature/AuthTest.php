<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Sertifikat Kejuaraan')
            ->assertDontSee('Manajemen User')
            ->assertDontSee('Deploy Tunnel');
    }

    public function test_users_can_authenticate_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'panitia@sistem.test',
            'password' => 'rahasia123',
        ]);

        $response = $this->post('/login', [
            'email' => 'panitia@sistem.test',
            'password' => 'rahasia123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'panitia@sistem.test',
            'password' => 'rahasia123',
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'panitia@sistem.test',
            'password' => 'salah-sekali',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_locked_after_five_failed_attempts(): void
    {
        User::factory()->create([
            'email' => 'panitia@sistem.test',
            'password' => 'rahasia123',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'panitia@sistem.test',
                'password' => 'salah-sekali',
            ]);
        }

        $response = $this->from('/login')->post('/login', [
            'email' => 'panitia@sistem.test',
            'password' => 'salah-sekali',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Terlalu banyak percobaan login',
            session('errors')->first('email'),
        );
        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        foreach (['/dashboard', '/kontingen', '/peserta'] as $uri) {
            $this->get($uri)->assertRedirect('/login');
        }
    }
}
