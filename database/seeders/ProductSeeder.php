<?php

namespace Database\Seeders;

use App\Models\Workspace;
use App\Services\DefaultCatalogService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = app(DefaultCatalogService::class);
        $workspaces = $this->targetWorkspaces();

        if ($workspaces->isEmpty()) {
            return;
        }

        foreach ($workspaces as $workspace) {
            $catalog->seedForWorkspace($workspace);
        }

        $this->command?->info(sprintf(
            'Productos asegurados: %d por workspace (%d workspace/s).',
            $catalog->productCount(),
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
