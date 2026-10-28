<?php

namespace App\Console\Commands;

use App\Models\Pengguna;
use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProvisionSuperAdmin extends Command
{
    protected $signature = 'app:super-admin
        {--name= : Nama Super Admin sekolah}
        {--username= : Username}
        {--email= : Email}
        {--password= : Password baru}';

    protected $description = 'Membuat atau memperbarui satu-satunya akun Super Admin sekolah';

    public function handle(): int
    {
        $name = (string) ($this->option('name') ?: env('SUPER_ADMIN_NAME', 'Super Admin Sekolah'));
        $username = (string) ($this->option('username') ?: env('SUPER_ADMIN_USERNAME', 'superadmin'));
        $email = (string) ($this->option('email') ?: env('SUPER_ADMIN_EMAIL', 'superadmin@sekolah.test'));
        $password = (string) ($this->option('password') ?: env('SUPER_ADMIN_PASSWORD', ''));
        if ($password === '' || strlen($password) < 12) {
            $this->error('Password Super Admin wajib diisi dan minimal 12 karakter.');
            return self::FAILURE;
        }

        $role = Role::query()->whereIn('nama_role', ['Admin', 'Admin / Pengelola'])->firstOrFail();
        DB::transaction(function () use ($role, $name, $username, $email, $password): void {
            Pengguna::query()->where('is_super_admin', true)->update(['is_super_admin' => false, 'status_akun' => 'Nonaktif']);
            $account = Pengguna::query()->where('username', $username)->first() ?? new Pengguna;
            $account->forceFill([
                'id_role' => $role->getKey(), 'nama' => $name, 'username' => $username,
                'email' => $email, 'password' => Hash::make($password),
                'status_akun' => 'Aktif', 'is_super_admin' => true,
            ])->save();
            Pengguna::query()->whereKeyNot($account->getKey())->whereHas('role', fn ($roles) => $roles->whereIn('nama_role', ['Admin', 'Admin / Pengelola']))
                ->update(['status_akun' => 'Nonaktif', 'is_super_admin' => false]);
        });

        $this->info('Akun Super Admin sekolah berhasil disiapkan.');
        return self::SUCCESS;
    }
}
