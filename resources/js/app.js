/**
 * Interaksi ringan (tanpa framework JS):
 * 1. Konfirmasi sebelum menghapus data.
 * 2. Pilih semua baris di halaman daftar peserta.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Konfirmasi hapus
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (! window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    // 2. Select all checkbox pada tabel peserta
    const selectAll = document.getElementById('select-all');

    if (selectAll) {
        const checkboxes = () => document.querySelectorAll('.peserta-checkbox');

        selectAll.addEventListener('change', () => {
            checkboxes().forEach((checkbox) => {
                checkbox.checked = selectAll.checked;
            });
        });

        document.addEventListener('change', (event) => {
            if (! event.target.classList.contains('peserta-checkbox')) {
                return;
            }

            const all = Array.from(checkboxes());
            const checked = all.filter((checkbox) => checkbox.checked);
            selectAll.checked = all.length > 0 && checked.length === all.length;
            selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
        });

        // 3. Informasi jumlah peserta yang dipilih
        const info = document.getElementById('pilihan-info');

        if (info) {
            const perbarui = () => {
                const jumlah = document.querySelectorAll('.peserta-checkbox:checked').length;
                info.textContent = jumlah + ' peserta dipilih';
            };

            document.addEventListener('change', (event) => {
                if (event.target.classList.contains('peserta-checkbox')) {
                    perbarui();
                }
            });

            selectAll.addEventListener('change', perbarui);
            perbarui();
        }
    }
});
