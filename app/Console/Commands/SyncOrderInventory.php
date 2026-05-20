<?php

namespace App\Console\Commands;

use App\Services\OrderInventoryService;
use Illuminate\Console\Command;

class SyncOrderInventory extends Command
{
    protected $signature = 'orders:sync-inventory';

    protected $description = 'Synchronize product stock from order pickup and return dates.';

    public function handle(OrderInventoryService $inventory): int
    {
        $inventory->syncDueOrders();

        $this->info('Order inventory synchronized.');

        return self::SUCCESS;
    }
}
