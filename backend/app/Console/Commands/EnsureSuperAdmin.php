<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Admin\Enums\PermissionEnum;
use Modules\User\Entities\User;
use Spatie\Permission\Models\Role;

class EnsureSuperAdmin extends Command
{
    protected $signature = 'user:ensure-super-admin
                            {email : Admin email}
                            {password? : Plain password (only used when creating a new user, or with --reset-password)}
                            {--name=Super Admin : Display name for new users}
                            {--reset-password : Update password if the user already exists}';

    protected $description = 'Idempotent: create super_admin if missing, otherwise only ensure full permissions (no duplicate user)';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $password = (string) ($this->argument('password') ?? '');
        $name = (string) $this->option('name');
        $resetPassword = (bool) $this->option('reset-password');

        if ($email === '') {
            $this->error('email is required');

            return self::FAILURE;
        }

        // يعيد مزامنة كل صلاحيات PermissionEnum على دور super_admin
        $this->call('permission:insert');

        $role = Role::where(['name' => 'super_admin', 'guard_name' => 'admin'])->first();
        if (! $role) {
            $this->error('super_admin role missing after permission:insert');

            return self::FAILURE;
        }

        // تأكيد: الدور يحمل كل الصلاحيات الحالية
        $role->syncPermissions(PermissionEnum::permissions());

        $user = User::where('email', $email)->first();

        if (! $user) {
            if ($password === '') {
                $this->error("User {$email} does not exist — password is required to create it");

                return self::FAILURE;
            }

            $user = User::create([
                'email' => $email,
                'name' => $name,
                'password' => $password,
                'role' => 'seeker',
                'kind' => 'individual',
                'tier' => 'elite',
                'status' => 'active',
            ]);
            $this->info("Created user {$user->email} (id={$user->id})");
        } else {
            $this->line("User already exists: {$user->email} (id={$user->id}) — skip create");

            // لا نغيّر الاسم/الباسورد عند الوجود، إلا بطلب صريح لإعادة الباسورد
            if ($resetPassword) {
                if ($password === '') {
                    $this->error('--reset-password requires a password argument');

                    return self::FAILURE;
                }
                $user->password = $password;
                $user->status = 'active';
                $user->save();
                $this->info('Password reset applied');
            } elseif ($user->status !== 'active') {
                $user->status = 'active';
                $user->save();
                $this->info('User status set to active');
            }
        }

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
            $this->info('Assigned role super_admin');
        } else {
            // إعادة ربط الدور يضمن تطابق الصلاحيات بعد permission:insert
            $user->syncRoles([$role]);
            $this->line('Role super_admin already present — permissions re-synced');
        }

        $permCount = $role->permissions()->count();
        $this->info("OK: {$user->email} has super_admin with {$permCount} permissions");

        return self::SUCCESS;
    }
}
