<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class EnsureAdmin extends Command
{
    protected $signature = 'boveda:ensure-admin';

    protected $description = 'Crea el usuario administrador si aún no existe ninguno (idempotente).';

    public function handle(): int
    {
        if (User::where('role', 'admin')->exists()) {
            $this->info('Ya existe un administrador. Nada que hacer.');

            return self::SUCCESS;
        }

        $email = getenv('BOVEDA_ADMIN_EMAIL') ?: 'admin@boveda.app';
        $password = getenv('BOVEDA_ADMIN_PASSWORD') ?: 'boveda-cambia-esta-clave';

        $user = User::create([
            'name' => 'Administrador',
            'email' => $email,
            'password' => $password, // el cast 'hashed' lo encripta
            'role' => 'admin',
            'negocio_id' => null,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        $this->info("Administrador creado: {$email}");

        return self::SUCCESS;
    }
}
