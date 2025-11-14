<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard - NIK Configuration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50" x-data="nikDashboard()" x-init="init()">
    
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('logo.png') }}" alt="Logo" class="h-12">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">NIK Configuration</h1>
                        <p class="text-sm text-gray-600">Management System</p>
                    </div>
                </div>
                
                <div class="text-right">
                    <p class="text-sm text-gray-600">Login sebagai:</p>
                    <p class="font-bold text-gray-800" x-text="user.name"></p>
                    <button @click="logout()" 
                            class="mt-2 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm transition-all">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-6 py-8">
        
        <!-- Alerts -->
        <div x-show="successMessage" x-cloak 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-90"
             x-transition:enter-end="opacity-100 transform scale-100"
             class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg shadow-md">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-check-circle text-2xl"></i>
                    <p x-text="successMessage"></p>
                </div>
                <button @click="successMessage = null" class="text-green-700 hover:text-green-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <div x-show="errorMessage" x-cloak 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-90"
             x-transition:enter-end="opacity-100 transform scale-100"
             class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-md">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-2xl"></i>
                    <p x-text="errorMessage"></p>
                </div>
                <button @click="errorMessage = null" class="text-red-700 hover:text-red-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex gap-4 mb-6 overflow-x-auto">
            <button @click="activeTab = 'insert'" 
                    :class="activeTab === 'insert' ? 'bg-blue-500 text-white' : 'bg-white text-gray-700'"
                    class="flex-1 py-3 px-6 rounded-lg font-semibold transition-all shadow-sm hover:shadow-md whitespace-nowrap">
                <i class="fas fa-plus-circle mr-2"></i>Insert NIK
            </button>
            <button @click="activeTab = 'delete'" 
                    :class="activeTab === 'delete' ? 'bg-blue-500 text-white' : 'bg-white text-gray-700'"
                    class="flex-1 py-3 px-6 rounded-lg font-semibold transition-all shadow-sm hover:shadow-md whitespace-nowrap">
                <i class="fas fa-trash-alt mr-2"></i>Delete NIK
            </button>
            <button @click="activeTab = 'list'" 
                    :class="activeTab === 'list' ? 'bg-blue-500 text-white' : 'bg-white text-gray-700'"
                    class="flex-1 py-3 px-6 rounded-lg font-semibold transition-all shadow-sm hover:shadow-md whitespace-nowrap">
                <i class="fas fa-list mr-2"></i>Daftar NIK
            </button>
        </div>

        <!-- INSERT TAB -->
        @include('partials.insert')

        <!-- DELETE TAB -->
        @include('partials.delete')

        <!-- LIST TAB -->
        @include('partials.list-nik')

    </main>

    <!-- Alpine.js Script -->
    @include('partials.scripts')
</body>
</html>