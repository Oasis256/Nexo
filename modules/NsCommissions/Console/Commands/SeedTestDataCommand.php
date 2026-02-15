<?php

namespace Modules\NsCommissions\Console\Commands;

use Illuminate\Console\Command;
use Modules\NsCommissions\Seeders\NsCommissionsSeeder;

class SeedTestDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nscommissions:seed 
                            {--fresh : Truncate commission data before seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed test data for NsCommissions module (Walk In Customer, Products, Commissions)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->warn('Clearing existing commission data...');
            $this->clearExistingData();
        }

        $this->info('Running NsCommissions seeder...');
        
        $seeder = new NsCommissionsSeeder();
        $seeder->setCommand($this);
        $seeder->run();

        return Command::SUCCESS;
    }

    /**
     * Clear existing commission data
     */
    private function clearExistingData(): void
    {
        \Modules\NsCommissions\Models\CommissionProductValue::truncate();
        \Modules\NsCommissions\Models\CommissionProductCategory::truncate();
        \Modules\NsCommissions\Models\EarnedCommission::truncate();
        \Modules\NsCommissions\Models\Commission::truncate();

        $this->info('Cleared existing commission data');
    }
}
