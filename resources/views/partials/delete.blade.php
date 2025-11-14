<div x-show="activeTab === 'delete'" x-cloak 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform translate-y-4"
     x-transition:enter-end="opacity-100 transform translate-y-0"
     class="bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-xl font-bold mb-4">
        <i class="fas fa-trash-alt text-red-500 mr-2"></i>Delete NIK Configuration
    </h2>
    
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700">
                    <strong>Peringatan:</strong> Aksi ini akan menghapus data NIK dari SAP. Pastikan data yang akan dihapus sudah benar.
                </p>
            </div>
        </div>
    </div>
    
    <form @submit.prevent="deleteNik()" class="space-y-4">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                Personnel Number (PERNR) <span class="text-red-500">*</span>
            </label>
            <input type="text" 
                   x-model="deleteForm.pernr"
                   placeholder="Contoh: 12345"
                   required
                   class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                   :disabled="loading">
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                Plant (WERKS) <span class="text-red-500">*</span>
            </label>
            <input type="text" 
                   x-model="deleteForm.werks"
                   placeholder="Contoh: 1000"
                   required
                   class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                   :disabled="loading">
        </div>

        <button type="submit"
                :disabled="loading"
                class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-3 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed">
            <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-trash'"></i>
            <span x-text="loading ? 'Processing...' : 'Delete NIK'"></span>
        </button>
    </form>
</div>