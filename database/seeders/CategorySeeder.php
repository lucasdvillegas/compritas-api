<?php

namespace Database\Seeders;

use App\Models\Workspace;
use App\Services\DefaultCatalogService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $catalog = app(DefaultCatalogService::class);
        $workspaces = $this->targetWorkspaces();

        if ($workspaces->isEmpty()) {
            $this->command?->warn('No hay workspaces. Registrá un usuario y volvé a ejecutar el seeder.');

            return;
        }

        foreach ($workspaces as $workspace) {
            $catalog->seedCategoriesForWorkspace($workspace);
        }

        $this->command?->info(sprintf(
            'Categorías aseguradas: %d por workspace (%d workspace/s).',
            count(DefaultCatalogService::CATEGORIES),
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
