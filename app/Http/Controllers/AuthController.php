<?php

namespace App\Http\Controllers;

use App\Services\DualAuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class AuthController extends Controller
{
    protected DualAuthService $authService;

    public function __construct(DualAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * ✅ Login dengan autentikasi gabungan Laravel + SAP
     * 
     * POST /api/auth/login
     * 
     * Body:
     * {
     *   "username": "john_doe",
     *   "password_laravel": "laravel123",
     *   "password_sap": "sap123"
     * }
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'username' => 'required|string',
                'password_laravel' => 'required|string',
                'password_sap' => 'required|string',
            ]);

            $result = $this->authService->login(
                $validated['username'],
                $validated['password_laravel'],
                $validated['password_sap']
            );

            return response()->json($result, 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login gagal',
                'error' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * ✅ Logout dari Laravel + SAP
     * 
     * POST /api/auth/logout
     */
    public function logout(): JsonResponse
    {
        try {
            $result = $this->authService->logout();
            return response()->json($result, 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout gagal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Check authentication status
     * 
     * GET /api/auth/check
     */
    public function check(): JsonResponse
    {
        if (!$this->authService->isAuthenticated()) {
            return response()->json([
                'authenticated' => false,
                'message' => 'Not authenticated'
            ], 401);
        }

        $user = $this->authService->getUser();

        return response()->json([
            'authenticated' => true,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ], 200);
    }

    /**
     * ✅ Get current user info
     * 
     * GET /api/auth/user
     */
    public function user(): JsonResponse
    {
        if (!$this->authService->isAuthenticated()) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated'
            ], 401);
        }

        $user = $this->authService->getUser();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_admin' => $user->isAdmin(),
                'last_sap_login' => $user->last_sap_login_at,
            ],
        ], 200);
    }

    /**
     * ✅ Validate SAP session
     * 
     * POST /api/auth/validate-sap
     */
    public function validateSap(): JsonResponse
    {
        try {
            $isValid = $this->authService->validateSapSession();

            return response()->json([
                'success' => true,
                'valid' => $isValid,
                'message' => $isValid ? 'SAP session valid' : 'SAP session invalid'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}