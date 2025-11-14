<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NikConfigController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ============================================
// PUBLIC ROUTES
// ============================================

Route::get('/', function () {
    return redirect()->route('login');
});

// Login Page
Route::get('/login', function () {
    return view('login');
})->name('login');

// ============================================
// AUTHENTICATION API ROUTES
// ============================================
Route::prefix('api/auth')->name('api.auth.')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('check', [AuthController::class, 'check'])->name('check');
    Route::get('user', [AuthController::class, 'user'])->name('user');
    Route::post('validate-sap', [AuthController::class, 'validateSap'])->name('validate-sap');
});

// ============================================
// PROTECTED ROUTES (Requires Dual Authentication)
// ============================================
Route::middleware('dual.auth')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    
    // NIK Confirmation Page
    Route::get('/nik-confirmation', function () {
        return view('nik-confirmation');
    })->name('nik-confirmation');
    
    // NIK Configuration API
    Route::prefix('api/nik')
        ->name('api.nik.')
        ->middleware('throttle:60,1')
        ->group(function () {
            // CRUD Operations
            Route::get('display', [NikConfigController::class, 'display'])->name('display');
            Route::post('insert', [NikConfigController::class, 'insert'])->name('insert');
            Route::post('delete', [NikConfigController::class, 'delete'])->name('delete');
            Route::post('sync', [NikConfigController::class, 'sync'])->name('sync');
            
            // Utilities
            Route::post('test-connection', [NikConfigController::class, 'testConnection'])->name('test-connection');
        });
});

// ============================================
// DEBUG ROUTES (REMOVE IN PRODUCTION!)
// ============================================
if (config('app.debug')) {
    Route::get('/debug/routes', function () {
        $routes = collect(Route::getRoutes())->map(function ($route) {
            return [
                'method' => implode('|', $route->methods()),
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'middleware' => $route->middleware(),
            ];
        })->filter(function ($route) {
            return str_contains($route['uri'], 'auth') || 
                   str_contains($route['uri'], 'nik') ||
                   str_contains($route['uri'], 'dashboard');
        })->values();
        
        return response()->json([
            'success' => true,
            'total_routes' => $routes->count(),
            'routes' => $routes,
        ], 200, [], JSON_PRETTY_PRINT);
    });
    
    Route::get('/debug/session', function () {
        return response()->json([
            'session_data' => session()->all(),
            'csrf_token' => csrf_token(),
        ], 200, [], JSON_PRETTY_PRINT);
    })->middleware('dual.auth');
}