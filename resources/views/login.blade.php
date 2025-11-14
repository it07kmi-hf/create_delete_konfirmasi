<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - NIK Configuration System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
        
        .logo-container {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 1rem;
            padding: 1rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .logo-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-400 via-blue-500 to-purple-600">
    <div x-data="loginForm()" class="min-h-screen flex items-center justify-center p-6">
        <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md">
            
            <!-- Logo & Title -->
            <div class="text-center mb-8">
                <div class="logo-container mx-auto mb-4">
                    <img src="{{ asset('logo.png') }}" alt="Company Logo">
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">NIK Configuration</h1>
                <p class="text-gray-600">Management System</p>
            </div>

            <!-- Success Alert -->
            <div x-show="successMessage" x-cloak 
                 class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
                <div class="flex items-center gap-2">
                    <i class="fas fa-check-circle"></i>
                    <p x-text="successMessage" class="text-sm"></p>
                </div>
            </div>

            <!-- Error Alert -->
            <div x-show="errorMessage" x-cloak 
                 class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                <div class="flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i>
                    <p x-text="errorMessage" class="text-sm"></p>
                </div>
            </div>

            <!-- Login Form -->
            <form @submit.prevent="login()" class="space-y-4">
                
                <!-- Username -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-user mr-2"></i>Username
                    </label>
                    <input 
                        type="text" 
                        x-model="username"
                        placeholder="Username untuk Laravel dan SAP"
                        required
                        autofocus
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        :disabled="loading">
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-info-circle"></i> Username sama untuk Laravel dan SAP
                    </p>
                </div>

                <!-- Password Laravel -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2"></i>Password Laravel
                    </label>
                    <input 
                        type="password" 
                        x-model="passwordLaravel"
                        placeholder="Password Laravel"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        :disabled="loading">
                </div>

                <!-- Password SAP -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-key mr-2"></i>Password SAP
                    </label>
                    <input 
                        type="password" 
                        x-model="passwordSap"
                        placeholder="Password SAP"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        :disabled="loading">
                </div>

                <!-- Login Button -->
                <button 
                    type="submit"
                    :disabled="loading"
                    class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-lg hover:shadow-xl">
                    <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-sign-in-alt'"></i>
                    <span x-text="loading ? 'Authenticating...' : 'Login'"></span>
                </button>
            </form>

            <!-- Info -->
            <div class="mt-6 text-center text-sm text-gray-600 bg-blue-50 p-4 rounded-lg">
                <p class="font-semibold mb-2"><i class="fas fa-shield-alt mr-1"></i> Dual Authentication</p>
                <p class="text-xs">Sistem menggunakan autentikasi gabungan Laravel + SAP</p>
            </div>

            <!-- Footer -->
            <div class="mt-8 pt-6 border-t border-gray-200 text-center text-xs text-gray-500">
                <p>NIK Configuration Management System</p>
                <p class="mt-1">© {{ date('Y') }} - Powered by Laravel & Python RFC</p>
            </div>
        </div>
    </div>

    <script>
        function loginForm() {
            return {
                username: '',
                passwordLaravel: '',
                passwordSap: '',
                loading: false,
                errorMessage: null,
                successMessage: null,

                async login() {
                    // Validation
                    if (!this.username || !this.passwordLaravel || !this.passwordSap) {
                        this.errorMessage = 'Semua field harus diisi!';
                        return;
                    }

                    this.loading = true;
                    this.errorMessage = null;
                    this.successMessage = null;

                    try {
                        const response = await fetch('/api/auth/login', {
                            method: 'POST',
                            credentials: 'include',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                username: this.username,
                                password_laravel: this.passwordLaravel,
                                password_sap: this.passwordSap
                            })
                        });

                        const result = await response.json();

                        if (response.ok && result.success) {
                            this.successMessage = 'Login berhasil! Redirecting...';
                            
                            // Redirect to dashboard
                            setTimeout(() => {
                                window.location.href = '/dashboard';
                            }, 500);
                        } else {
                            this.errorMessage = result.error || result.message || 'Login gagal';
                            this.passwordLaravel = '';
                            this.passwordSap = '';
                        }
                    } catch (error) {
                        console.error('Login error:', error);
                        this.errorMessage = 'Terjadi kesalahan koneksi';
                        this.passwordLaravel = '';
                        this.passwordSap = '';
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
</body>
</html>