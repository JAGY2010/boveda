<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'negocio_id', 'role'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    // ---- Negocio y roles ----

    /** @return BelongsTo<Negocio, $this> */
    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }

    /**
     * Locales a los que el usuario tiene acceso (un dueño puede tener varios).
     *
     * @return BelongsToMany<Negocio, $this>
     */
    public function negocios(): BelongsToMany
    {
        return $this->belongsToMany(Negocio::class);
    }

    /**
     * IDs de los locales que puede ver: el admin ve todos.
     *
     * Se normalizan a int porque el driver puede devolverlos como string
     * (Postgres lo hace) y quien los recibe los compara con ===.
     *
     * @return array<int, int>
     */
    public function accessibleNegocioIds(): array
    {
        $ids = $this->isAdmin()
            ? Negocio::query()->pluck('id')
            : $this->negocios()->pluck('negocios.id');

        return $ids->map(fn ($id): int => (int) $id)->values()->all();
    }

    /** @return Collection<int, Negocio> */
    public function accessibleNegocios(): Collection
    {
        if ($this->isAdmin()) {
            return Negocio::query()->orderBy('nombre')->get();
        }

        return $this->negocios()->orderBy('nombre')->get();
    }

    public function hasMultipleLocales(): bool
    {
        return count($this->accessibleNegocioIds()) > 1;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    /** Admin y Dueño ven el dinero y las ganancias; el Empleado no. */
    public function puedeVerDinero(): bool
    {
        return in_array($this->role, ['admin', 'owner']);
    }

    /** Admin y Dueño pueden editar y eliminar. */
    public function puedeEditar(): bool
    {
        return in_array($this->role, ['admin', 'owner']);
    }

    public function rolLabel(): string
    {
        return match ($this->role) {
            'admin' => 'Admin',
            'owner' => 'Dueño',
            default => 'Empleado',
        };
    }
}
