<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    public const ADMIN_ROLES = ['admin' => 'Администратор', 'content_manager' => 'Контент-менеджер', 'manager' => 'Управляющий'];
    use HasApiTokens, HasFactory, Notifiable;
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = ['name', 'username', 'email', 'password', 'role_type', 'blocked'];

    protected $hidden = ['password', 'remember_token', 'role_type', 'email_verified_at'];

    protected $appends = ['role'];

    protected $casts = ['password' => 'hashed', 'blocked' => 'boolean'];

    public function profile()
    {
        return $this->hasOne(PlayerProfile::class);
    }

    public function getRoleAttribute(): array
    {
        $role = $this->accessRole();
        return ['id' => match ($role) {
            'admin' => 3, 'manager' => 2, 'content_manager' => 4, default => 1
        }, 'name' => self::ADMIN_ROLES[$role] ?? 'Игрок', 'type' => $role];
    }

    public function accessRole(): string
    {
        return $this->role_type === 'organizer' ? 'manager' : ($this->role_type ?? 'authenticated');
    }

    public function canAccessAdmin(): bool
    {
        return array_key_exists($this->accessRole(), self::ADMIN_ROLES);
    }

    public function canManageContent(): bool
    {
        return in_array($this->accessRole(), ['admin', 'content_manager'], true);
    }

    public function canManagePlayers(): bool
    {
        return $this->isOrganizer();
    }

    public function canAssignRoles(): bool
    {
        return $this->accessRole() === 'admin';
    }

    public function isOrganizer(): bool
    {
        return in_array($this->accessRole(), ['admin', 'manager'], true);
    }
}
