<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SyncUserPasswords extends Command
{
    protected $signature = 'users:sync-passwords {--dry-run : Periksa tanpa mengubah database}';

    protected $description = 'Sinkronkan password akun Oktober 2026 dan tambahkan user.test untuk Gowa';

    private const PASSWORDS = [
        'admin.sulsel' => 'NtFjxHhc',
        'kepulauan.selayar' => 'Gq9cqnhm',
        'bulukumba' => 'NftZdCgb',
        'bantaeng' => 'NX5MxRn3',
        'jeneponto' => 'PqC4fnbg',
        'takalar' => 'X8ZTDCQ7',
        'gowa' => 'NhTow8Mr',
        'sinjai' => 'ZcHqGCXB',
        'maros' => 'nUvfnRaj',
        'pangkajene.dan.kepulauan' => 'HDDMBML2',
        'barru' => '2RMg2dmZ',
        'bone' => 'LZyMWKLY',
        'soppeng' => '2tYzh8PS',
        'wajo' => 'dwSgPq7Z',
        'sidenreng.rappang' => 'EFEAQGrM',
        'pinrang' => 'xQp2YSte',
        'enrekang' => 'jcCq29kT',
        'luwu' => 'xVuxvoiU',
        'tana.toraja' => 'GHCLwN7x',
        'luwu.utara' => 'EabiYESS',
        'luwu.timur' => 'dKJMtBNM',
        'toraja.utara' => 'egDMFRaX',
        'makassar' => 'Ud4idrSC',
        'parepare' => 'wXFKFzvA',
        'palopo' => 'DtH2CzCS',
    ];

    public function handle(): int
    {
        try {
            $users = User::whereIn('username', array_keys(self::PASSWORDS))->get()->keyBy('username');
            $missing = array_diff(array_keys(self::PASSWORDS), $users->keys()->all());
            if ($missing) {
                $this->error('Akun belum tersedia: '.implode(', ', $missing).'. Tidak ada perubahan.');
                return self::FAILURE;
            }

            $gowa = $users->get('gowa');
            $test = User::where('username', 'user.test')->first();
            if (!$test && User::where('email', 'user.test@example.com')->exists()) {
                $this->error('Email user.test@example.com sudah digunakan akun lain. Tidak ada perubahan.');
                return self::FAILURE;
            }

            $indexes = Schema::getIndexes('users');
            $uniqueIndexes = array_filter($indexes, fn ($index) => $index['unique'] && !$index['primary'] && $index['columns'] === ['wilayah_id']);
            $hasRegionIndex = (bool) array_filter($indexes, fn ($index) => !$index['unique'] && ($index['columns'][0] ?? null) === 'wilayah_id');

            $this->info('Ditemukan 25 akun. user.test akan '.($test ? 'diperbarui' : 'dibuat').' untuk wilayah Gowa.');
            if ($this->option('dry-run')) {
                $this->info('Indeks unik wilayah yang perlu dihapus: '.count($uniqueIndexes).'. Tidak ada perubahan database.');
                return self::SUCCESS;
            }

            // MySQL DDL commits implicitly; adjust indexes before the data transaction.
            if ($uniqueIndexes) {
                Schema::table('users', function (Blueprint $table) use ($uniqueIndexes, $hasRegionIndex) {
                    if (!$hasRegionIndex) {
                        $table->index('wilayah_id', 'users_wilayah_index');
                    }
                    foreach ($uniqueIndexes as $index) {
                        $table->dropUnique($index['name']);
                    }
                });
            }

            DB::transaction(function () use ($users, $gowa, $test) {
                foreach (self::PASSWORDS as $username => $password) {
                    $user = $users->get($username);
                    $this->setPassword($user, $password);
                    $user->save();
                }

                $test ??= new User;
                $test->username = 'user.test';
                if (!$test->exists) {
                    $test->name = 'User Test Gowa';
                    $test->email = 'user.test@example.com';
                }
                $test->role_id = $gowa->role_id;
                $test->wilayah_id = $gowa->wilayah_id;
                $this->setPassword($test, 'password');
                $test->save();

                foreach (self::PASSWORDS + ['user.test' => 'password'] as $username => $password) {
                    $stored = User::where('username', $username)->value('password');
                    if (!Hash::check($password, $stored)) {
                        throw new \RuntimeException('Verifikasi password gagal: '.$username);
                    }
                }
            });

            $this->info('Selesai: password 26 akun terverifikasi. user.test menggunakan password: password.');
            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Sinkronisasi gagal: '.$exception->getMessage());
            return self::FAILURE;
        }
    }

    private function setPassword(User $user, string $password): void
    {
        try {
            if ($user->password && Hash::check($password, $user->password)) {
                return;
            }
        } catch (\RuntimeException) {
            // Replace legacy passwords whose hash format is unsupported.
        }
        $user->password = Hash::make($password);
    }
}
