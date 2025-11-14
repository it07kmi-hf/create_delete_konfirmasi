<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Exception;

class DualAuthService
{
    protected string $sapApiUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->sapApiUrl = config('sap.nik_api_url', 'http://192.168.254.252:5042');
        $this->timeout = config('sap.timeout', 30);
    }

    public function login(string $username, string $passwordLaravel, string $passwordSap): array
    {
        try {
            Log::info("Attempting dual authentication", ['username' => $username]);

            $user = User::where('username', $username)->active()->first();

            if (!$user) {
                throw new Exception('User tidak ditemukan atau tidak aktif');
            }

            if (!Hash::check($passwordLaravel, $user->password)) {
                throw new Exception('Password Laravel salah');
            }

            $sapResponse = $this->testSapConnection($username, $passwordSap);

            if (!$sapResponse['success']) {
                throw new Exception('Koneksi SAP gagal: ' . ($sapResponse['error'] ?? 'Unknown error'));
            }

            Auth::login($user, true);

            Session::put('sap_credentials', [
                'username' => $username,
                'password' => encrypt($passwordSap),
                'logged_in_at' => now()->toDateTimeString(),
            ]);

            $user->updateSapLogin(Session::getId());

            Log::info("Dual authentication successful", [
                'username' => $username,
                'user_id' => $user->id,
            ]);

            return [
                'success' => true,
                'message' => 'Login berhasil',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'name' => $user->name,
                    'role' => $user->role,
                ],
            ];

        } catch (Exception $e) {
            Log::error("Dual authentication failed", [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    protected function testSapConnection(string $username, string $password): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-SAP-Username' => $username,
                    'X-SAP-Password' => $password,
                ])
                ->post("{$this->sapApiUrl}/api/sap-login");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error'] ?? 'SAP connection failed',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function logout(): array
    {
        try {
            $username = Auth::user()?->username;

            Session::forget('sap_credentials');
            Auth::logout();
            Session::invalidate();
            Session::regenerateToken();

            Log::info("Dual logout successful", ['username' => $username]);

            return [
                'success' => true,
                'message' => 'Logout berhasil',
            ];

        } catch (Exception $e) {
            Log::error("Logout failed", ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function isAuthenticated(): bool
    {
        return Auth::check() && Session::has('sap_credentials');
    }

    public function getSapCredentials(): ?array
    {
        if (!Session::has('sap_credentials')) {
            return null;
        }

        $credentials = Session::get('sap_credentials');

        return [
            'username' => $credentials['username'],
            'password' => decrypt($credentials['password']),
        ];
    }

    public function getUser(): ?User
    {
        return Auth::user();
    }

    public function validateSapSession(): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        try {
            $credentials = $this->getSapCredentials();
            
            if (!$credentials) {
                return false;
            }

            $response = $this->testSapConnection(
                $credentials['username'],
                $credentials['password']
            );

            return $response['success'];

        } catch (Exception $e) {
            Log::error("SAP session validation failed", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}