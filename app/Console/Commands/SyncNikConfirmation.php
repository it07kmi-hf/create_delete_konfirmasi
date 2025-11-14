<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SapNikConfirmationService;

class SyncNikConfirmation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sap:syncconfnik 
                            {--pernr= : Filter by Personnel Number (optional)}
                            {--werks= : Filter by Plant (optional)}
                            {--stats : Show sync statistics only}
                            {--clear : Clear all data before sync}
                            {--confirm : Enable confirmation prompts (default: auto-yes)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync NIK Confirmation data from SAP to local database (auto-execute by default, replaces all data)';

    /**
     * SAP NIK Confirmation Service
     *
     * @var SapNikConfirmationService
     */
    protected SapNikConfirmationService $service;

    /**
     * Create a new command instance.
     */
    public function __construct(SapNikConfirmationService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║        SAP NIK CONFIRMATION SYNC COMMAND                   ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Check if confirmation mode is enabled
        $needsConfirmation = $this->option('confirm');
        
        if (!$needsConfirmation) {
            $this->comment('⚡ AUTO-EXECUTE MODE: Running without confirmations');
            $this->warn('⚠ WARNING: This will REPLACE all existing data in the database!');
            $this->newLine();
        }

        // Show statistics only
        if ($this->option('stats')) {
            return $this->showStatistics();
        }

        // Clear data if requested
        if ($this->option('clear')) {
            if ($needsConfirmation && !$this->confirm('Are you sure you want to clear all NIK confirmation data?', false)) {
                $this->warn('Clear operation cancelled.');
                $this->newLine();
            } else {
                $this->clearData();
            }
        }

        // Get filters
        $pernr = $this->option('pernr');
        $werks = $this->option('werks');

        // Show configuration
        $this->displayConfiguration($pernr, $werks);

        // Confirm before sync ONLY if --confirm flag is present
        if ($needsConfirmation) {
            if (!$this->confirm('Start syncing NIK confirmation data from SAP? (This will REPLACE all data)', true)) {
                $this->warn('Sync operation cancelled.');
                return Command::FAILURE;
            }
        }

        // Start sync
        $this->newLine();
        $this->info('Starting sync...');
        $this->newLine();

        $startTime = microtime(true);

        try {
            $result = $this->service->syncFromSap($pernr, $werks);

            $duration = round(microtime(true) - $startTime, 2);

            if ($result['success']) {
                $this->displayResults($result['statistics'], $duration);
                return Command::SUCCESS;
            } else {
                $this->error('Sync failed: ' . ($result['message'] ?? 'Unknown error'));
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error('Error during sync: ' . $e->getMessage());
            $this->newLine();
            
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }
            
            return Command::FAILURE;
        }
    }

    /**
     * Display configuration
     */
    protected function displayConfiguration(?string $pernr, ?string $werks): void
    {
        $this->info('Configuration:');
        $this->line('  Python API: ' . config('sap.nik_conf_api_url'));
        $this->line('  SAP User: ' . config('sap.username'));
        $this->line('  Mode: REPLACE (Delete all data, then insert new)');
        
        if ($pernr) {
            $this->line('  Filter PERNR: ' . $pernr);
        }
        
        if ($werks) {
            $this->line('  Filter WERKS: ' . $werks);
        }
        
        if (!$pernr && !$werks) {
            $this->line('  Filters: None (syncing all data)');
        }
        
        $this->newLine();
    }

    /**
     * Display sync results
     */
    protected function displayResults(array $statistics, float $duration): void
    {
        $this->newLine();
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║                    SYNC COMPLETED                          ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Records Fetched', $statistics['fetched']],
                ['Records Deleted', '<fg=red>' . $statistics['deleted'] . '</>'],
                ['Records Inserted', '<fg=green>' . $statistics['inserted'] . '</>'],
                ['Records Updated', '<fg=yellow>' . ($statistics['updated'] ?? 0) . '</>'],
                ['Records Skipped', '<fg=gray>' . $statistics['skipped'] . '</>'],
                ['Errors', $statistics['errors'] > 0 ? '<fg=red>' . $statistics['errors'] . '</>' : $statistics['errors']],
            ]
        );

        $this->newLine();
        $this->info("Duration: {$duration} seconds");
        $this->newLine();

        // Show warning if there were errors
        if ($statistics['errors'] > 0) {
            $this->warn("⚠ {$statistics['errors']} error(s) occurred during sync. Check logs for details.");
            $this->newLine();
        }

        // Show success message
        $total = $statistics['inserted'];
        if ($total > 0) {
            $this->info("✓ Successfully replaced and synced {$total} record(s)!");
            $this->comment("  Old records deleted: {$statistics['deleted']}");
        } else {
            $this->comment('No records were synced.');
        }
    }

    /**
     * Show statistics
     */
    protected function showStatistics(): int
    {
        $this->info('Fetching statistics from database...');
        $this->newLine();

        try {
            $stats = $this->service->getSyncStatistics();

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Records', number_format($stats['total_records'])],
                    ['Last Sync', $stats['last_sync'] ? $stats['last_sync']->format('Y-m-d H:i:s') : 'Never'],
                    ['Synced Today', number_format($stats['synced_today'])],
                    ['Synced This Week', number_format($stats['synced_this_week'])],
                    ['Synced This Month', number_format($stats['synced_this_month'])],
                ]
            );

            $this->newLine();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error fetching statistics: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Clear all data
     */
    protected function clearData(): void
    {
        $this->warn('Clearing all NIK confirmation data...');
        
        try {
            $deleted = $this->service->clearAllData();
            $this->info("✓ Cleared {$deleted} record(s).");
            $this->newLine();
        } catch (\Exception $e) {
            $this->error('Error clearing data: ' . $e->getMessage());
            $this->newLine();
        }
    }
}