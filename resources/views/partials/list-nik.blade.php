<div x-show="activeTab === 'list'" x-cloak 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform translate-y-4"
     x-transition:enter-end="opacity-100 transform translate-y-0"
     class="bg-white rounded-lg shadow-lg p-6">
    
    <!-- ✅ SYNC PROGRESS MODAL -->
    <div x-show="syncing" 
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         style="display: none;">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4 shadow-2xl">
            <div class="text-center">
                <div class="mb-4">
                    <i class="fas fa-sync fa-spin text-6xl text-blue-500"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Syncing Data dari SAP</h3>
                <p class="text-gray-600 mb-4" x-text="syncProgress || 'Mohon tunggu, proses sync sedang berjalan...'"></p>
                <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                    <div class="bg-blue-500 h-2 rounded-full animate-pulse" style="width: 100%"></div>
                </div>
                <p class="text-sm text-gray-500">⚠️ Jangan tutup halaman ini</p>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold">
            <i class="fas fa-list text-blue-500 mr-2"></i>Daftar NIK Confirmation
        </h2>
        <div class="flex gap-2">
            <button @click="syncFromSap()" 
                    :disabled="loading || syncing"
                    :class="syncing ? 'bg-orange-500' : 'bg-green-500 hover:bg-green-600'"
                    class="text-white px-4 py-2 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas" :class="syncing ? 'fa-sync fa-spin' : 'fa-sync'"></i>
                <span x-text="syncing ? 'Syncing...' : 'Sync SAP'"></span>
            </button>
            <button @click="loadNikList()" 
                    :disabled="loading || syncing"
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-sync-alt'"></i>
                <span x-text="loading ? 'Loading...' : 'Refresh'"></span>
            </button>
        </div>
    </div>

    <!-- Success Message -->
    <div x-show="successMessage" 
         x-cloak
         x-transition
         class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
        <div class="flex items-start">
            <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
            <div class="flex-1">
                <p class="text-green-800 whitespace-pre-line" x-text="successMessage"></p>
            </div>
            <button @click="successMessage = null" class="text-green-500 hover:text-green-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Error Message -->
    <div x-show="errorMessage" 
         x-cloak
         x-transition
         class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
        <div class="flex items-start">
            <i class="fas fa-exclamation-circle text-red-500 mt-1 mr-3"></i>
            <div class="flex-1">
                <p class="text-red-800" x-text="errorMessage"></p>
            </div>
            <button @click="errorMessage = null" class="text-red-500 hover:text-red-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">PERNR</label>
            <input type="text" 
                   x-model="filters.pernr"
                   @input="debouncedSearch()"
                   :disabled="loading || syncing"
                   placeholder="Cari PERNR..."
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">WERKS</label>
            <input type="text" 
                   x-model="filters.werks"
                   @input="debouncedSearch()"
                   :disabled="loading || syncing"
                   placeholder="Cari WERKS..."
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Nama</label>
            <input type="text" 
                   x-model="filters.search"
                   @input="debouncedSearch()"
                   :disabled="loading || syncing"
                   placeholder="Cari nama..."
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Per Halaman</label>
            <select x-model="filters.per_page" 
                    @change="loadNikList()"
                    :disabled="loading || syncing"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
    </div>

    <div x-show="loading && !syncing" class="text-center py-8">
        <i class="fas fa-spinner fa-spin text-4xl text-blue-500"></i>
        <p class="mt-4 text-gray-600">Loading data...</p>
    </div>

    <div x-show="!loading && nikList.length > 0" x-cloak class="overflow-x-auto border rounded-lg" style="max-height: 600px;">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 sticky top-0 z-10">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">PERNR</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">WERKS</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Created By</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Created On</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Synced At</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="(item, index) in nikList" :key="item.id">
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" 
                            x-text="((pagination.current_page - 1) * pagination.per_page) + index + 1"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="item.pernr_display"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="item.werks"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="item.name1"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="item.created_by"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="item.created_on"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="item.synced_at"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <div x-show="!loading && nikList.length > 0" x-cloak class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 mt-4">
        <div class="flex-1 flex justify-between sm:hidden">
            <button @click="changePage(pagination.current_page - 1)"
                    :disabled="pagination.current_page <= 1 || loading || syncing"
                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                Previous
            </button>
            <button @click="changePage(pagination.current_page + 1)"
                    :disabled="pagination.current_page >= pagination.last_page || loading || syncing"
                    class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                Next
            </button>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    Showing
                    <span class="font-medium" x-text="pagination.from"></span>
                    to
                    <span class="font-medium" x-text="pagination.to"></span>
                    of
                    <span class="font-medium" x-text="pagination.total"></span>
                    results
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <button @click="changePage(pagination.current_page - 1)"
                            :disabled="pagination.current_page <= 1 || loading || syncing"
                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <template x-for="page in paginationPages" :key="page">
                        <button @click="changePage(page)"
                                :disabled="loading || syncing"
                                :class="page === pagination.current_page ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'"
                                class="relative inline-flex items-center px-4 py-2 border text-sm font-medium disabled:cursor-not-allowed"
                                x-text="page"></button>
                    </template>
                    <button @click="changePage(pagination.current_page + 1)"
                            :disabled="pagination.current_page >= pagination.last_page || loading || syncing"
                            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </nav>
            </div>
        </div>
    </div>

    <div x-show="!loading && !syncing && nikList.length === 0" x-cloak class="text-center py-12">
        <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
        <p class="text-gray-500 text-lg">Tidak ada data NIK</p>
        <p class="text-gray-400 text-sm mt-2">Coba ubah filter pencarian atau lakukan sync dari SAP</p>
    </div>
</div>