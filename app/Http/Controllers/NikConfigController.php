<?php

namespace App\Http\Controllers;

use App\Models\NikConfirmation;
use App\Services\DualAuthService;
use App\Services\SapNikConfirmationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class NikConfigController extends Controller
{
    protected DualAuthService $authService;
    protected SapNikConfirmationService $sapNikService;
    protected string $sapApiUrl;        // Port 5042 - for INSERT/DELETE
    protected string $sapConfApiUrl;    // Port 5040 - for DISPLAY/SYNC
    protected int $timeout;

    public function __construct(
        DualAuthService $authService,
        SapNikConfirmationService $sapNikService
    ) {
        $this->authService = $authService;
        $this->sapNikService = $sapNikService;
        
        // Port 5042 - untuk INSERT/DELETE operations
        $this->sapApiUrl = config('sap.nik_api_url');
        
        // Port 5040 - untuk DISPLAY/SYNC operations
        $this->sapConfApiUrl = config('sap.nik_conf_api_url');
        
        $this->timeout = config('sap.timeout');
    }

    /**
     * ✅ DISPLAY NIK Confirmation List
     * 
     * GET /api/nik/display
     * 
     * Query Parameters:
     * - pernr: Filter by Personnel Number (optional)
     * - werks: Filter by Plant (optional)
     * - search: Search by employee name (optional)
     * - per_page: Records per page (default: 50)
     * - page: Page number (default: 1)
     * - sort_by: Sort field (default: pernr)
     * - sort_order: Sort order (asc/desc, default: asc)
     * - source: Data source (database/sap/both, default: database)
     */
    public function display(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'pernr' => 'nullable|string',
                'werks' => 'nullable|string',
                'search' => 'nullable|string',
                'per_page' => 'nullable|integer|min:1|max:500',
                'page' => 'nullable|integer|min:1',
                'sort_by' => 'nullable|string|in:pernr,werks,name1,created_on,synced_at',
                'sort_order' => 'nullable|string|in:asc,desc',
                'source' => 'nullable|string|in:database,sap,both',
            ]);

            $source = $validated['source'] ?? 'database';
            
            // If source is SAP or both, fetch from SAP
            if (in_array($source, ['sap', 'both'])) {
                $sapData = $this->fetchFromSap(
                    $validated['pernr'] ?? null,
                    $validated['werks'] ?? null
                );
                
                // If source is only SAP, return SAP data directly
                if ($source === 'sap') {
                    return response()->json($sapData, $sapData['success'] ? 200 : 400);
                }
            }

            // Fetch from database
            $query = NikConfirmation::query();

            // Apply filters
            if (!empty($validated['pernr'])) {
                $query->byPernr($validated['pernr']);
            }

            if (!empty($validated['werks'])) {
                $query->byWerks($validated['werks']);
            }

            if (!empty($validated['search'])) {
                $query->searchName($validated['search']);
            }

            // Apply sorting
            $sortBy = $validated['sort_by'] ?? 'pernr';
            $sortOrder = $validated['sort_order'] ?? 'asc';
            $query->orderBy($sortBy, $sortOrder);

            // Paginate
            $perPage = $validated['per_page'] ?? 50;
            $data = $query->paginate($perPage);

            // Transform data
            $transformedData = $data->map(function ($item) {
                return [
                    'id' => $item->id,
                    'pernr' => $item->pernr,
                    'pernr_display' => $item->pernr_display,
                    'werks' => trim($item->werks),
                    'name1' => $item->name1,
                    'created_by' => $item->created_by,
                    'created_on' => $item->created_on_formatted,
                    'synced_at' => $item->synced_at_formatted,
                ];
            });

            Log::info("NIK display successful", [
                'user' => $this->authService->getUser()->username,
                'filters' => array_filter($validated),
                'total' => $data->total(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data retrieved successfully',
                'data' => $transformedData,
                'pagination' => [
                    'total' => $data->total(),
                    'per_page' => $data->perPage(),
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'from' => $data->firstItem(),
                    'to' => $data->lastItem(),
                ],
                'filters_applied' => array_filter([
                    'pernr' => $validated['pernr'] ?? null,
                    'werks' => $validated['werks'] ?? null,
                    'search' => $validated['search'] ?? null,
                ]),
            ], 200);

        } catch (Exception $e) {
            Log::error("NIK display exception", [
                'user' => $this->authService->getUser()->username ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch data directly from SAP
     */
    protected function fetchFromSap(?string $pernr = null, ?string $werks = null): array
    {
        try {
            $sapCredentials = $this->authService->getSapCredentials();

            if (!$sapCredentials) {
                return [
                    'success' => false,
                    'error' => 'SAP credentials not found. Please login again.'
                ];
            }

            $payload = [];
            if ($pernr) {
                $payload['pernr'] = $pernr;
            }
            if ($werks) {
                $payload['werks'] = $werks;
            }

            // ✅ MENGGUNAKAN PORT 5040 untuk DISPLAY
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-SAP-Username' => $sapCredentials['username'],
                    'X-SAP-Password' => $sapCredentials['password'],
                ])
                ->post("{$this->sapConfApiUrl}/api/nik-conf/display", $payload);

            $result = $response->json();

            if ($response->successful() && ($result['success'] ?? false)) {
                return [
                    'success' => true,
                    'message' => $result['message'] ?? 'Data retrieved from SAP',
                    'data' => $result['data'] ?? [],
                    'record_count' => $result['record_count'] ?? 0,
                    'source' => 'sap',
                ];
            }

            return [
                'success' => false,
                'error' => $result['message'] ?? $result['error'] ?? 'Failed to fetch from SAP',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ✅ INSERT NIK Configuration
     * 
     * POST /api/nik/insert
     * 
     * Body:
     * {
     *   "pernr": "12345",
     *   "werks": "1000"
     * }
     */
    public function insert(Request $request): JsonResponse
    {
        try {
            // Validate input
            $validated = $request->validate([
                'pernr' => 'required|string',
                'werks' => 'required|string',
            ]);

            // Get SAP credentials from session
            $sapCredentials = $this->authService->getSapCredentials();

            if (!$sapCredentials) {
                return response()->json([
                    'success' => false,
                    'error' => 'SAP credentials not found. Please login again.'
                ], 401);
            }

            // ✅ MENGGUNAKAN PORT 5042 untuk INSERT
            // Call SAP Python Service
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-SAP-Username' => $sapCredentials['username'],
                    'X-SAP-Password' => $sapCredentials['password'],
                ])
                ->post("{$this->sapApiUrl}/api/nik-conf/insert", [
                    'pernr' => $validated['pernr'],
                    'werks' => $validated['werks'],
                ]);

            $result = $response->json();

            if ($response->successful() && ($result['success'] ?? false)) {
                Log::info("NIK insert successful", [
                    'user' => $this->authService->getUser()->username,
                    'pernr' => $validated['pernr'],
                    'werks' => $validated['werks'],
                ]);

                return response()->json($result, 200);
            }

            Log::error("NIK insert failed", [
                'user' => $this->authService->getUser()->username,
                'error' => $result['message'] ?? $result['error'] ?? 'Unknown error',
            ]);

            return response()->json($result, 400);

        } catch (Exception $e) {
            Log::error("NIK insert exception", [
                'user' => $this->authService->getUser()->username ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ DELETE NIK Configuration
     * 
     * POST /api/nik/delete
     * 
     * Body:
     * {
     *   "pernr": "12345",
     *   "werks": "1000"
     * }
     */
    public function delete(Request $request): JsonResponse
    {
        try {
            // Validate input
            $validated = $request->validate([
                'pernr' => 'required|string',
                'werks' => 'required|string',
            ]);

            // Get SAP credentials from session
            $sapCredentials = $this->authService->getSapCredentials();

            if (!$sapCredentials) {
                return response()->json([
                    'success' => false,
                    'error' => 'SAP credentials not found. Please login again.'
                ], 401);
            }

            // ✅ MENGGUNAKAN PORT 5042 untuk DELETE (using delete_flag)
            // Call SAP Python Service with delete_flag
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-SAP-Username' => $sapCredentials['username'],
                    'X-SAP-Password' => $sapCredentials['password'],
                ])
                ->post("{$this->sapApiUrl}/api/nik-conf/delete", [
                    'pernr' => $validated['pernr'],
                    'werks' => $validated['werks'],
                    'delete_flag' => 'X',
                ]);

            $result = $response->json();

            if ($response->successful() && ($result['success'] ?? false)) {
                Log::info("NIK delete successful", [
                    'user' => $this->authService->getUser()->username,
                    'pernr' => $validated['pernr'],
                    'werks' => $validated['werks'],
                ]);

                return response()->json($result, 200);
            }

            Log::error("NIK delete failed", [
                'user' => $this->authService->getUser()->username,
                'error' => $result['message'] ?? $result['error'] ?? 'Unknown error',
            ]);

            return response()->json($result, 400);

        } catch (Exception $e) {
            Log::error("NIK delete exception", [
                'user' => $this->authService->getUser()->username ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Sync NIK Confirmations from SAP
     * 
     * POST /api/nik/sync
     * 
     * Body (optional):
     * {
     *   "pernr": "12345",
     *   "werks": "1000"
     * }
     * 
     * Note: This uses SapNikConfirmationService which REPLACES all data
     */
    public function sync(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'pernr' => 'nullable|string',
                'werks' => 'nullable|string',
            ]);

            Log::info("NIK sync request received", [
                'user' => $this->authService->getUser()->username,
                'pernr' => $validated['pernr'] ?? null,
                'werks' => $validated['werks'] ?? null,
            ]);

            // ✅ USE SapNikConfirmationService - which handles:
            // 1. Fetch from SAP
            // 2. Delete all existing data
            // 3. Insert new data
            $result = $this->sapNikService->syncFromSap(
                $validated['pernr'] ?? null,
                $validated['werks'] ?? null
            );

            if ($result['success']) {
                Log::info("NIK sync successful", [
                    'user' => $this->authService->getUser()->username,
                    'statistics' => $result['statistics'],
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Data berhasil di-sync dari SAP',
                    'statistics' => $result['statistics'],
                ], 200);
            } else {
                Log::error("NIK sync failed", [
                    'user' => $this->authService->getUser()->username,
                    'error' => $result['message'] ?? 'Unknown error',
                ]);

                return response()->json([
                    'success' => false,
                    'error' => $result['message'] ?? 'Gagal melakukan sync',
                ], 500);
            }

        } catch (Exception $e) {
            Log::error("NIK sync exception", [
                'user' => $this->authService->getUser()->username ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Terjadi kesalahan saat melakukan sync: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Test SAP Connection
     * 
     * POST /api/nik/test-connection
     */
    public function testConnection(): JsonResponse
    {
        try {
            $sapCredentials = $this->authService->getSapCredentials();

            if (!$sapCredentials) {
                return response()->json([
                    'success' => false,
                    'error' => 'SAP credentials not found.'
                ], 401);
            }

            // ✅ Test connection ke Port 5040 (DISPLAY service)
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-SAP-Username' => $sapCredentials['username'],
                    'X-SAP-Password' => $sapCredentials['password'],
                ])
                ->get("{$this->sapConfApiUrl}/health");

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'SAP connection OK',
                    'data' => $response->json(),
                ], 200);
            }

            return response()->json([
                'success' => false,
                'error' => 'SAP connection failed'
            ], 500);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}