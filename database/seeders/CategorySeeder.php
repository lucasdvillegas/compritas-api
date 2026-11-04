<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class CategorySeeder extends Seeder
{
    /**
     * Categorías de ejemplo para probar los listados.
     *
     * @var array<int, string>
     */
    public const CATEGORIES = [
        'Frutas',
        'Verduras',
        'Lácteos',
        'Carnes',
        'Pescados',
        'Panadería',
        'Fiambres',
        'Bebidas',
        'Almacén',
        'Congelados',
        'Limpieza',
        'Perfumería',
        'Bebés',
        'Mascotas',
        'Snacks',
        'Desayuno',
        'Pastas',
        'Conservas',
        'Especias',
        'Hogar',
    ];

    public function run(): void
    {
        $workspaces = $this->targetWorkspaces();

        if ($workspaces->isEmpty()) {
            $this->command?->warn('No hay workspaces. Registrá un usuario y volvé a ejecutar el seeder.');

            return;
        }

        foreach ($workspaces as $workspace) {
            foreach (self::CATEGORIES as $name) {
                Category::firstOrCreate([
                    'workspace_id' => $workspace->id,
                    'name' => $name,
                ]);
            }
        }

        $this->command?->info(sprintf(
            'Categorías aseguradas: %d por workspace (%d workspace/s).',
            count(self::CATEGORIES),
            $workspaces->count(),
        ));
    }

    /**
     * @return Collection<int, Workspace>
     */
    private function targetWorkspaces(): Collection
    {
        $workspaceId = env('SEED_WORKSPACE_ID');

        if ($workspaceId !== null && $workspaceId !== '') {
            return Workspace::query()->whereKey($workspaceId)->get();
        }

        return Workspace::query()->orderBy('id')->get();
    }
}
