<script>
function nikDashboard() {
    return {
        user: {},
        activeTab: 'insert',
        loading: false,
        syncing: false,
        syncProgress: null,
        successMessage: null,
        errorMessage: null,
        
        insertForm: {
            pernr: '',
            werks: ''
        },
        
        deleteForm: {
            pernr: '',
            werks: ''
        },

        nikList: [],
        pagination: {
            total: 0,
            per_page: 50,
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0
        },
        filters: {
            pernr: '',
            werks: '',
            search: '',
            per_page: 50
        },
        searchTimeout: null,

        async init() {
            await this.loadUser();
            
            this.$watch('activeTab', (value) => {
                if (value === 'list' && this.nikList.length === 0) {
                    this.loadNikList();
                }
            });
        },

        async loadUser() {
            try {
                const response = await fetch('/api/auth/user', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const result = await response.json();
                if (result.success) {
                    this.user = result.user;
                }
            } catch (error) {
                console.error('Failed to load user:', error);
            }
        },

        async insertNik() {
            this.loading = true;
            this.errorMessage = null;
            this.successMessage = null;

            try {
                const response = await fetch('/api/nik/insert', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.insertForm)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    this.successMessage = result.message || 'NIK berhasil ditambahkan';
                    this.insertForm = { pernr: '', werks: '' };
                    
                    if (this.activeTab === 'list') {
                        await this.loadNikList();
                    }
                } else {
                    this.errorMessage = result.error || result.message || 'Gagal menambahkan NIK';
                }
            } catch (error) {
                this.errorMessage = 'Terjadi kesalahan koneksi';
            } finally {
                this.loading = false;
            }
        },

        async deleteNik() {
            if (!confirm('Apakah Anda yakin ingin menghapus NIK ini?')) {
                return;
            }

            this.loading = true;
            this.errorMessage = null;
            this.successMessage = null;

            try {
                const response = await fetch('/api/nik/delete', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.deleteForm)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    this.successMessage = result.message || 'NIK berhasil dihapus';
                    this.deleteForm = { pernr: '', werks: '' };
                    
                    if (this.activeTab === 'list') {
                        await this.loadNikList();
                    }
                } else {
                    this.errorMessage = result.error || result.message || 'Gagal menghapus NIK';
                }
            } catch (error) {
                this.errorMessage = 'Terjadi kesalahan koneksi';
            } finally {
                this.loading = false;
            }
        },

        // ✅ UPDATED: Sync tanpa konfirmasi, notifikasi sederhana
        async syncFromSap() {
            this.syncing = true;
            this.loading = true;
            this.errorMessage = null;
            this.successMessage = null;
            this.syncProgress = 'Memulai proses sync...';

            try {
                const response = await fetch('/api/nik/sync', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        pernr: this.filters.pernr || null,
                        werks: this.filters.werks || null
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    // ✅ Notifikasi sederhana
                    this.successMessage = 'Sinkroning berhasil';
                    this.syncProgress = null;
                    
                    // Auto reload list setelah 1 detik
                    setTimeout(() => {
                        this.loadNikList();
                    }, 1000);
                } else {
                    this.errorMessage = result.error || result.message || 'Gagal melakukan sync dari SAP';
                    this.syncProgress = null;
                }
            } catch (error) {
                this.errorMessage = 'Terjadi kesalahan koneksi saat sync: ' + error.message;
                this.syncProgress = null;
            } finally {
                this.syncing = false;
                this.loading = false;
            }
        },

        async loadNikList(page = 1) {
            this.loading = true;
            this.errorMessage = null;

            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: this.filters.per_page,
                    ...(this.filters.pernr && { pernr: this.filters.pernr }),
                    ...(this.filters.werks && { werks: this.filters.werks }),
                    ...(this.filters.search && { search: this.filters.search })
                });

                const response = await fetch(`/api/nik/display?${params}`, {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    this.nikList = result.data;
                    this.pagination = result.pagination;
                } else {
                    this.errorMessage = result.error || 'Gagal memuat data';
                    this.nikList = [];
                }
            } catch (error) {
                this.errorMessage = 'Terjadi kesalahan koneksi';
                this.nikList = [];
            } finally {
                this.loading = false;
            }
        },

        debouncedSearch() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.loadNikList(1);
            }, 500);
        },

        changePage(page) {
            if (page < 1 || page > this.pagination.last_page) {
                return;
            }
            this.loadNikList(page);
        },

        get paginationPages() {
            const pages = [];
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;
            
            pages.push(1);
            
            for (let i = Math.max(2, current - 2); i <= Math.min(last - 1, current + 2); i++) {
                if (i > 1 && i < last) {
                    pages.push(i);
                }
            }
            
            if (last > 1) {
                pages.push(last);
            }
            
            return pages;
        },

        async logout() {
            if (!confirm('Apakah Anda yakin ingin logout?')) {
                return;
            }

            try {
                await fetch('/api/auth/logout', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                window.location.href = '/login';
            } catch (error) {
                console.error('Logout error:', error);
            }
        }
    }
}
</script>