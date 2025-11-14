<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\DualAuthService;
use Illuminate\Support\Facades\Log;

class DualAuth
{
    protected DualAuthService $authService;

    public function __construct(DualAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ✅ Check if user authenticated to both Laravel and SAP
        if (!$this->authService->isAuthenticated()) {
            Log::warning("Dual authentication required", [
                'url' => $request->fullUrl(),
                'ip' => $request->ip()
            ]);

            // Jika request dari API
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required. Please login first.',
                    'error' => 'Unauthorized'
                ], 401);
            }
            
            // Redirect ke halaman login untuk web request
            return redirect()->route('login')
                ->with('error', 'Please login first');
        }

        return $next($request);
    }
}