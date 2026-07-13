<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\User\Entities\User;
use Spatie\Permission\Models\Role;

class EnsureSuperAdmin extends Command
{
    protected $signature = 'user:ensure-super-admin
                            {email : Admin email}
                            {password : Plain password}
                            {--name=Super Admin : Display name}';

    protected $description = 'Create or update a user and grant Spatie super_admin (all permissions, guard: admin)';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $password = (string) $this->argument('password');
        $name = (string) $this->option('name');

        if ($email === '' || $password === '') {
            $this->error('email and password are required');

            return self::FAILURE;
        }

        $this->call('permission:insert');

        $role = Role::where(['name' => 'super_admin', 'guard_name' => 'admin'])->first();
        if (! $role) {
            $this->error('super_admin role missing after permission:insert');

            return self::FAILURE;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'role' => 'seeker',
                'kind' => 'individual',
                'tier' => 'elite',
                'status' => 'active',
            ]
        );

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        $this->info("OK: {$user->email} is super_admin (id={$user->id})");

        return self::SUCCESS;
    }
}
