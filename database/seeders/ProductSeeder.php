<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ProductSeeder extends Seeder
{
    /**
     * Catálogo de ejemplo. Cada producto es [nombre, unidad].
     *
     * @var array<string, array<int, array{0: string, 1: string}>>
     */
    public const CATALOG = [
        'Frutas' => [
            ['Manzana Roja', 'kg'],
            ['Banana', 'kg'],
            ['Pera', 'kg'],
            ['Naranja', 'kg'],
            ['Palta', 'kg'],
            ['Uva', 'kg'],
        ],
        'Verduras' => [
            ['Papa', 'kg'],
            ['Cebolla', 'kg'],
            ['Zanahoria', 'kg'],
            ['Tomate', 'kg'],
            ['Lechuga', 'kg'],
            ['Zapallo', 'kg'],
        ],
        'Lácteos' => [
            ['Leche Entera', 'unit'],
            ['Yogur Natural', 'unit'],
            ['Queso Cremoso', 'kg'],
            ['Manteca', 'unit'],
            ['Crema de Leche', 'unit'],
        ],
        'Carnes' => [
            ['Asado', 'kg'],
            ['Milanesas', 'kg'],
            ['Carne Picada', 'kg'],
            ['Pollo', 'kg'],
            ['Chorizo', 'kg'],
        ],
        'Pescados' => [
            ['Merluza', 'kg'],
            ['Salmón', 'kg'],
            ['Atún Fresco', 'kg'],
        ],
        'Panadería' => [
            ['Pan Francés', 'unit'],
            ['Pan de Molde', 'unit'],
            ['Facturas', 'unit'],
            ['Medialunas', 'unit'],
        ],
        'Fiambres' => [
            ['Jamón Cocido', 'kg'],
            ['Queso Fresco', 'kg'],
            ['Salame', 'kg'],
            ['Mortadela', 'kg'],
        ],
        'Bebidas' => [
            ['Agua Mineral', 'unit'],
            ['Gaseosa', 'unit'],
            ['Jugo de Naranja', 'unit'],
            ['Cerveza', 'unit'],
            ['Vino', 'unit'],
        ],
        'Almacén' => [
            ['Arroz', 'unit'],
            ['Fideos', 'unit'],
            ['Lentejas', 'unit'],
            ['Aceite de Girasol', 'unit'],
            ['Harina', 'unit'],
            ['Azúcar', 'unit'],
        ],
        'Congelados' => [
            ['Papas Fritas', 'unit'],
            ['Hamburguesas', 'unit'],
            ['Verdura Mix', 'unit'],
            ['Helado', 'unit'],
        ],
        'Limpieza' => [
            ['Detergente', 'unit'],
            ['Lavandina', 'unit'],
            ['Jabón en Polvo', 'unit'],
            ['Limpiavidrios', 'unit'],
            ['Papel Higiénico', 'unit'],
        ],
        'Perfumería' => [
            ['Shampoo', 'unit'],
            ['Jabón de Tocador', 'unit'],
            ['Pasta Dental', 'unit'],
            ['Desodorante', 'unit'],
        ],
        'Bebés' => [
            ['Pañales', 'unit'],
            ['Toallitas', 'unit'],
            ['Leche en Polvo', 'unit'],
        ],
        'Mascotas' => [
            ['Alimento para Perro', 'unit'],
            ['Alimento para Gato', 'unit'],
            ['Arena Sanitaria', 'unit'],
        ],
        'Snacks' => [
            ['Papas Chips', 'unit'],
            ['Galletitas', 'unit'],
            ['Chocolate', 'unit'],
            ['Maní', 'unit'],
        ],
        'Desayuno' => [
            ['Café', 'unit'],
            ['Té', 'unit'],
            ['Mate Cocido', 'unit'],
            ['Mermelada', 'unit'],
            ['Cereal', 'unit'],
        ],
        'Pastas' => [
            ['Tallarines', 'unit'],
            ['Ravioles', 'unit'],
            ['Ñoquis', 'unit'],
        ],
        'Conservas' => [
            ['Tomate Triturado', 'unit'],
            ['Atún en Lata', 'unit'],
            ['Arvejas', 'unit'],
            ['Choclo', 'unit'],
        ],
        'Especias' => [
            ['Sal', 'unit'],
            ['Pimienta', 'unit'],
            ['Orégano', 'unit'],
            ['Comino', 'unit'],
            ['Pimentón', 'unit'],
        ],
        'Hogar' => [
            ['Pilas', 'unit'],
            ['Fósforos', 'unit'],
            ['Bolsas de Residuos', 'unit'],
            ['Servilletas', 'unit'],
        ],
    ];

    public function run(): void
    {
        $this->call(CategorySeeder::class);

        $workspaces = $this->targetWorkspaces();

        if ($workspaces->isEmpty()) {
            return;
        }

        foreach ($workspaces as $workspace) {
            $categoryIds = Category::query()
                ->where('workspace_id', $workspace->id)
                ->pluck('id', 'name');

            foreach (self::CATALOG as $categoryName => $products) {
                $categoryId = $categoryIds->get($categoryName);

                foreach ($products as [$name, $unit]) {
                    Product::firstOrCreate(
                        [
                            'workspace_id' => $workspace->id,
                            'name' => $name,
                        ],
                        [
                            'category_id' => $categoryId,
                            'unit' => $unit,
                        ],
                    );
                }
            }
        }

        $this->command?->info(sprintf(
            'Productos asegurados: %d por workspace (%d workspace/s).',
            $this->productCount(),
            $workspaces->count(),
        ));
    }

    private function productCount(): int
    {
        return collect(self::CATALOG)->sum(fn (array $products): int => count($products));
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
