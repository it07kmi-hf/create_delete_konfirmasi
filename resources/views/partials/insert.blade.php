<div x-show="activeTab === 'insert'" x-cloak 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform translate-y-4"
     x-transition:enter-end="opacity-100 transform translate-y-0"
     class="bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-xl font-bold mb-4">
        <i class="fas fa-plus-circle text-blue-500 mr-2"></i>Insert NIK Configuration
    </h2>
    
    <form @submit.prevent="insertNik()" class="space-y-4">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                Personnel Number (PERNR) <span class="text-red-500">*</span>
            </label>
            <input type="text" 
                   x-model="insertForm.pernr"
                   placeholder="Contoh: 12345"
                   required
                   class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   :disabled="loading">
            <p class="text-xs text-gray-500 mt-1">Format: 8 digit angka (akan otomatis diformat dengan leading zeros)</p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                Plant (WERKS) <span class="text-red-500">*</span>
            </label>
            <input type="text" 
                   x-model="insertForm.werks"
                   placeholder="Contoh: 1000"
                   required
                   class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   :disabled="loading">
            <p class="text-xs text-gray-500 mt-1">Format: 4 karakter</p>
        </div>

        <button type="submit"
                :disabled="loading"
                class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed">
            <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-save'"></i>
            <span x-text="loading ? 'Processing...' : 'Insert NIK'"></span>
        </button>
    </form>
</div>