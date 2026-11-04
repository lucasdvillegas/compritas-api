<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'pin'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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
     * @return BelongsToMany<Workspace, $this>
     */
    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<ShoppingList, $this>
     */
    public function shoppingLists(): BelongsToMany
    {
        return $this->belongsToMany(ShoppingList::class, 'shopping_list_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function currentWorkspace(): Workspace
    {
        return $this->workspaces()->firstOrFail();
    }

    public static function generatePin(): string
    {
        do {
            $pin = (string) random_int(10000000, 99999999);
        } while (static::query()->where('pin', $pin)->exists());

        return $pin;
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (blank($user->pin)) {
                $user->pin = static::generatePin();
            }
        });

        static::created(function (User $user): void {
            $workspace = Workspace::create([
                'name' => 'Espacio personal',
                'owner_id' => $user->id,
            ]);

            $workspace->members()->attach($user->id, ['role' => 'owner']);
        });
    }
}
