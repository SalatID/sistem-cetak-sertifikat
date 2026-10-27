<?php

namespace Tests\Feature;

use App\Models\PrintLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogCetakTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_melihat_riwayat_cetak(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        PrintLog::create([
            'user_id' => $admin->id,
            'jenis_dokumen' => 'sertifikat_peserta',
            'jumlah_data' => 3,
            'konteks' => 'Budi Santoso, Citra Lestari, Dimas Anggara',
        ]);

        $this->actingAs($admin)
            ->get(route('log-cetak.index'))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('Sertifikat Peserta');
    }

    public function test_riwayat_cetak_memakai_timezone_wib(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('WIB', now()->format('T'));
    }

    public function test_riwayat_cetak_dapat_difilter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        PrintLog::create([
            'user_id' => $admin->id,
            'jenis_dokumen' => 'sk_juara',
            'jumlah_data' => 1,
            'nomor_sk' => '001/SK/SKX26/III/2026',
            'konteks' => 'Budi Santoso',
        ]);
        PrintLog::create([
            'user_id' => $admin->id,
            'jenis_dokumen' => 'sertifikat_official',
            'jumlah_data' => 1,
            'konteks' => 'Coach Budi Hartono',
        ]);

        $this->actingAs($admin)
            ->get(route('log-cetak.index', ['jenis' => 'sk_juara']))
            ->assertOk()
            ->assertSee('001/SK/SKX26/III/2026')
            ->assertDontSee('Coach Budi Hartono');
    }

    public function test_operator_tidak_bisa_melihat_riwayat_cetak(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)->get(route('log-cetak.index'))->assertForbidden();
    }
}
