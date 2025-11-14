<?php

namespace App\Services;

use App\Models\NikConfirmation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SapNikConfirmationService
{
    /**
     * Python API base URL for NIK Confirmation
     */
    protected string $apiUrl;

    /**
     * SAP credentials from .env
     */
    protected ?string $sapUsername;
    protected ?string $sapPassword;

    /**
     * Request timeout in seconds
     */
    protected int $timeout;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->apiUrl = rtrim(config('sap.nik_conf_api_url'), '/');
        $this->sapUsername = config('sap.username');
        $this->sapPassword = config('sap.password');
        $this->timeout = config('sap.timeout', 600);
    }

    /**
     * Validate SAP credentials before making requests
     * 
     * @throws \Exception
     */
    protected function validateCredentials(): void
    {
        if (empty($this->sapUsername) || empty($this->sapPassword)) {
            throw new \Exception('SAP credentials not configured. Please set SAP_TEST_USERNAME and SAP_TEST_PASSWORD in .env');
        }
    }

    /**
     * Sync NIK confirmations from SAP to database
     * 
     * @param string|null $pernr Filter by personnel number (optional)
     * @param string|null $werks Filter by plant (optional)
     * @return array Result with statistics
     * @throws \Exception
     */
    public function syncFromSap(?string $pernr = null, ?string $werks = null): array
    {
        // Validate credentials first
        $this->validateCredentials();

        Log::info('Starting NIK Confirmation sync from SAP', [
            'pernr' => $pernr,
            'werks' => $werks,
        ]);

        // Step 1: Fetch data from SAP
        $sapData = $this->fetchFromSap($pernr, $werks);

        if (!$sapData['success']) {
            throw new \Exception('Failed to fetch data from SAP: ' . ($sapData['error'] ?? $sapData['message'] ?? 'Unknown error'));
        }

        $records = $sapData['data'] ?? [];
        $recordCount = count($records);

        Log::info("Fetched {$recordCount} records from SAP");

        if ($recordCount === 0) {
            return [
                'success' => true,
                'message' => 'No data found in SAP',
                'statistics' => [
                    'fetched' => 0,
                    'inserted' => 0,
                    'updated' => 0,
                    'deleted' => 0,
                    'skipped' => 0,
                    'errors' => 0,
                ],
            ];
        }

        // Step 2: Delete all existing data before inserting new data
        $deletedCount = 0;
        try {
            $deletedCount = $this->clearAllData();
            Log::info("Deleted {$deletedCount} existing records from database");
        } catch (\Exception $e) {
            Log::error('Error deleting existing data', ['error' => $e->getMessage()]);
            throw new \Exception('Failed to clear existing data: ' . $e->getMessage());
        }

        // Step 3: Process and save to database (insert only, no update)
        $statistics = $this->processRecords($records);
        $statistics['deleted'] = $deletedCount;

        Log::info('NIK Confirmation sync completed', $statistics);

        return [
            'success' => true,
            'message' => "Sync completed successfully",
            'statistics' => $statistics,
        ];
    }

    /**
     * Fetch data from SAP using Python RFC service
     * 
     * @param string|null $pernr
     * @param string|null $werks
     * @return array
     */
    protected function fetchFromSap(?string $pernr = null, ?string $werks = null): array
    {
        try {
            // Build request payload
            $payload = [
                'username' => $this->sapUsername,
                'password' => $this->sapPassword,
            ];

            // Add filters if provided
            if (!empty($pernr)) {
                $payload['pernr'] = $pernr;
            }

            if (!empty($werks)) {
                $payload['werks'] = $werks;
            }

            Log::info('Calling SAP RFC Z_RFC_DISPLAY_NIK_CONF', [
                'url' => $this->apiUrl . '/api/nik-conf/display',
                'filters' => array_diff_key($payload, ['password' => '']),
            ]);

            // Call Python RFC service
            $response = Http::timeout($this->timeout)
                ->retry(3, 1000) // Retry 3 times with 1 second delay
                ->post($this->apiUrl . '/api/nik-conf/display', $payload);

            if (!$response->successful()) {
                Log::error('SAP RFC call failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'error' => 'HTTP Error ' . $response->status() . ': ' . $response->body(),
                ];
            }

            $result = $response->json();

            Log::info('SAP RFC response received', [
                'success' => $result['success'] ?? false,
                'status' => $result['status'] ?? '',
                'record_count' => $result['record_count'] ?? 0,
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Exception during SAP RFC call', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process and save records to database
     * 
     * @param array $records
     * @return array Statistics
     */
    protected function processRecords(array $records): array
    {
        $statistics = [
            'fetched' => count($records),
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $syncedAt = now();

        foreach ($records as $record) {
            try {
                // Validate required fields
                if (empty($record['PERNR']) || empty($record['WERKS'])) {
                    Log::warning('Skipping record with missing PERNR or WERKS', $record);
                    $statistics['skipped']++;
                    continue;
                }

                // Format data
                $data = $this->formatRecordData($record, $syncedAt);

                // Insert only (since we already deleted all data)
                $nikConf = NikConfirmation::create($data);
                
                $statistics['inserted']++;
                Log::debug('Inserted new NIK confirmation', ['pernr' => $data['pernr'], 'werks' => $data['werks']]);

            } catch (\Exception $e) {
                Log::error('Error processing record', [
                    'record' => $record,
                    'error' => $e->getMessage(),
                ]);
                $statistics['errors']++;
            }
        }

        return $statistics;
    }

    /**
     * Format record data from SAP to database format
     * 
     * @param array $record
     * @param \Carbon\Carbon $syncedAt
     * @return array
     */
    protected function formatRecordData(array $record, Carbon $syncedAt): array
    {
        return [
            'pernr' => str_pad(trim($record['PERNR'] ?? ''), 8, '0', STR_PAD_LEFT),
            'werks' => str_pad(trim($record['WERKS'] ?? ''), 4, ' ', STR_PAD_RIGHT),
            'name1' => trim($record['NAME1'] ?? ''),
            'created_by' => trim($record['CREATED_BY'] ?? ''),
            'created_on' => $this->parseSapDate($record['CREATED_ON'] ?? null),
            'synced_at' => $syncedAt,
        ];
    }

    /**
     * Parse SAP date format (YYYYMMDD) to Carbon instance
     * 
     * @param mixed $dateStr
     * @return \Carbon\Carbon|null
     */
    protected function parseSapDate($dateStr): ?Carbon
    {
        if (empty($dateStr) || $dateStr === '00000000') {
            return null;
        }

        $dateStr = trim($dateStr);

        // If already in Y-m-d format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            try {
                return Carbon::parse($dateStr);
            } catch (\Exception $e) {
                return null;
            }
        }

        // SAP format YYYYMMDD
        if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $dateStr, $matches)) {
            try {
                return Carbon::createFromFormat('Y-m-d', "{$matches[1]}-{$matches[2]}-{$matches[3]}");
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Get sync statistics from database
     * 
     * @return array
     */
    public function getSyncStatistics(): array
    {
        return [
            'total_records' => NikConfirmation::count(),
            'last_sync' => NikConfirmation::max('synced_at'),
            'synced_today' => NikConfirmation::whereDate('synced_at', today())->count(),
            'synced_this_week' => NikConfirmation::where('synced_at', '>=', now()->startOfWeek())->count(),
            'synced_this_month' => NikConfirmation::where('synced_at', '>=', now()->startOfMonth())->count(),
        ];
    }

    /**
     * Clear all NIK confirmations from database
     * 
     * @return int Number of records deleted
     */
    public function clearAllData(): int
    {
        return NikConfirmation::query()->delete();
    }
}