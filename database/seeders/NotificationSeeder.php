<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Services\StockService;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(StockService::class);

        Inventory::with('product')->get()->each(function ($inventory) use ($service) {
            $service->checkLowStock($inventory);
        });
    }
}
