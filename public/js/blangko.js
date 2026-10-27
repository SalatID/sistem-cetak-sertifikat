/**
 * Desainer blangko: drag & drop penempatan elemen teks di atas gambar blangko.
 *
 * Semua koordinat dalam milimeter — satuan yang sama dipakai saat render PDF,
 * sehingga posisi di layar sama persis dengan hasil cetak.
 *
 * Perilaku:
 * - Klik kotak        → pilih elemen (form terisi, bisa diedit / dihapus).
 * - Seret kotak       → pindahkan; posisi tersimpan otomatis (tanpa reload).
 * - Seret sudut       → ubah ukuran; ukuran tersimpan otomatis.
 * - Tombol Simpan     → simpan seluruh properti elemen (teks, font, warna, …).
 * - Tombol Hapus      → hapus elemen dari blangko.
 */
document.addEventListener('DOMContentLoaded', () => {
    const kanvas = document.getElementById('kanvas');

    if (! kanvas) {
        return;
    }

    const MM = 96 / 25.4; // 1 mm dalam piksel @96dpi
    const AMBANG_GESER = 2; // piksel; di bawah ini dianggap klik, bukan geser
    const dataElemen = JSON.parse(document.getElementById('data-elemen').textContent);
    const formElemen = document.getElementById('form-elemen');
    const token = document.querySelector('meta[name="csrf-token"]').content;

    const f = {
        id: document.getElementById('elemen_id'),
        label: document.getElementById('f_label'),
        tipe: document.getElementById('f_tipe'),
        sumber: document.getElementById('f_sumber_field'),
        isi: document.getElementById('f_isi'),
        varian: document.getElementById('f_varian_key'),
        x: document.getElementById('f_x_mm'),
        y: document.getElementById('f_y_mm'),
        lebar: document.getElementById('f_lebar_mm'),
        tinggi: document.getElementById('f_tinggi_mm'),
        fontFamily: document.getElementById('f_font_family'),
        fontSize: document.getElementById('f_font_size'),
        align: document.getElementById('f_align'),
        valign: document.getElementById('f_valign'),
        warna: document.getElementById('f_warna'),
        zIndex: document.getElementById('f_z_index'),
        tebal: document.getElementById('f_tebal'),
        miring: document.getElementById('f_miring'),
        tampil: document.getElementById('f_tampil'),
    };

    const wrapSumber = document.getElementById('wrap-sumber');
    const wrapIsi = document.getElementById('wrap-isi');
    const wrapVarian = document.getElementById('wrap-varian');
    const btnHapus = document.getElementById('btn-hapus');
    const btnReset = document.getElementById('btn-reset');
    const judulForm = document.getElementById('judul-form-elemen');

    let terpilih = null; // id elemen yang sedang diedit (null = elemen baru)

    /* ------------------------------------------------------------------ */
    /* Utilitas                                                            */
    /* ------------------------------------------------------------------ */

    function angka(nilai, cadangan) {
        const n = parseFloat(nilai);

        return Number.isFinite(n) ? n : cadangan;
    }

    function flash(pesan, jenis = 'sukses') {
        let box = document.getElementById('flash-desainer');

        if (! box) {
            box = document.createElement('div');
            box.id = 'flash-desainer';
            document.body.appendChild(box);
        }

        box.className = 'fixed bottom-4 right-4 z-50 rounded-lg px-4 py-2 text-sm text-white shadow-lg '
            + (jenis === 'gagal' ? 'bg-rose-600' : 'bg-slate-900');
        box.textContent = pesan;

        setTimeout(() => box.remove(), 2500);
    }

    function pesanGalat(payload, cadangan) {
        if (payload && payload.errors) {
            const pertama = Object.values(payload.errors)[0];

            if (Array.isArray(pertama) && pertama.length) {
                return pertama[0];
            }
        }

        return (payload && payload.message) || cadangan;
    }

    function elemenDariId(id) {
        return dataElemen.find((el) => String(el.id) === String(id));
    }

    function kotakDariId(id) {
        return kanvas.querySelector('.elemen[data-id="' + id + '"]');
    }

    function barisDaftarDariId(id) {
        return document.querySelector('[data-baris-elemen="' + id + '"]');
    }

    /* ------------------------------------------------------------------ */
    /* Form                                                                */
    /* ------------------------------------------------------------------ */

    function perbaruiTipeForm() {
        const tipe = f.tipe.value;

        wrapSumber.classList.toggle('hidden', tipe !== 'data');
        wrapIsi.classList.toggle('hidden', tipe !== 'teks');
        wrapVarian.classList.toggle('hidden', tipe !== 'varian');
    }

    function isiForm(elemen) {
        f.id.value = elemen.id ?? '';
        f.label.value = elemen.label ?? '';
        f.tipe.value = elemen.tipe ?? 'teks';
        f.sumber.value = elemen.sumber_field ?? '';
        f.isi.value = elemen.isi ?? '';
        f.varian.value = elemen.varian_key ?? '';
        f.x.value = elemen.x_mm ?? 0;
        f.y.value = elemen.y_mm ?? 0;
        f.lebar.value = elemen.lebar_mm ?? 80;
        f.tinggi.value = elemen.tinggi_mm ?? 10;
        f.fontFamily.value = elemen.font_family ?? 'sans';
        f.fontSize.value = elemen.font_size ?? 11;
        f.align.value = elemen.align ?? 'left';
        f.valign.value = elemen.valign ?? 'top';
        f.warna.value = elemen.warna ?? '#0f172a';
        f.zIndex.value = elemen.z_index ?? 10;
        f.tebal.checked = Boolean(elemen.tebal);
        f.miring.checked = Boolean(elemen.miring);
        f.tampil.checked = elemen.tampil === undefined ? true : Boolean(elemen.tampil);

        btnHapus.classList.toggle('hidden', ! elemen.id);
        judulForm.textContent = elemen.id ? 'Edit Elemen' : 'Elemen Baru';
        perbaruiTipeForm();
    }

    function sorot(id) {
        kanvas.querySelectorAll('.elemen').forEach((kotak) => {
            const aktif = id !== null && String(kotak.dataset.id) === String(id);

            kotak.classList.toggle('ring-2', aktif);
            kotak.classList.toggle('ring-indigo-600', aktif);
            kotak.classList.toggle('bg-indigo-400/30', aktif);
        });

        document.querySelectorAll('[data-baris-elemen]').forEach((baris) => {
            const aktif = id !== null && String(baris.dataset.barisElemen) === String(id);

            baris.classList.toggle('border-indigo-400', aktif);
            baris.classList.toggle('bg-indigo-50', aktif);
        });
    }

    function pilih(id) {
        const el = elemenDariId(id);

        if (! el) {
            return;
        }

        terpilih = String(id);
        isiForm(el);
        sorot(terpilih);
    }

    function kosongkanForm() {
        terpilih = null;
        isiForm({
            tipe: 'teks',
            x_mm: 20,
            y_mm: 20,
            lebar_mm: 80,
            tinggi_mm: 10,
            font_family: 'sans',
            font_size: 11,
            align: 'left',
            valign: 'top',
            warna: '#0f172a',
            z_index: 10,
            tampil: true,
        });
        sorot(null);
        f.label.focus();
    }

    /* ------------------------------------------------------------------ */
    /* Sinkronisasi tampilan setelah simpan                                */
    /* ------------------------------------------------------------------ */

    function perbaruiKotak(el) {
        const kotak = kotakDariId(el.id);

        if (! kotak) {
            return;
        }

        kotak.dataset.x = el.x_mm;
        kotak.dataset.y = el.y_mm;
        kotak.dataset.w = el.lebar_mm;
        kotak.dataset.h = el.tinggi_mm;
        kotak.style.left = el.x_mm + 'mm';
        kotak.style.top = el.y_mm + 'mm';
        kotak.style.width = el.lebar_mm + 'mm';
        kotak.style.height = el.tinggi_mm + 'mm';
        kotak.style.zIndex = el.z_index;

        const label = kotak.querySelector('.label-elemen');

        if (label) {
            label.textContent = el.label;
        }

        kotak.style.display = el.tampil ? '' : 'none';
    }

    function perbaruiBarisDaftar(el) {
        const baris = barisDaftarDariId(el.id);

        if (! baris) {
            return;
        }

        const label = baris.querySelector('[data-label-elemen]');
        const detail = baris.querySelector('[data-detail-elemen]');

        if (label) {
            label.textContent = el.label;
        }

        if (detail) {
            detail.textContent = detail.textContent
                .replace(/X [\d.]+ \/ Y [\d.]+/, 'X ' + el.x_mm + ' / Y ' + el.y_mm)
                .replace(/[\d.]+×[\d.]+ mm/, el.lebar_mm + '×' + el.tinggi_mm + ' mm');
        }
    }

    function simpan() {
        const data = new FormData(formElemen);
        const baru = ! f.id.value;

        fetch(formElemen.action, {
            method: 'POST',
            body: data,
            headers: {
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })
            .then(async (res) => {
                const payload = await res.json().catch(() => null);

                if (! res.ok) {
                    throw new Error(pesanGalat(payload, 'Gagal menyimpan elemen.'));
                }

                return payload;
            })
            .then((payload) => {
                if (baru) {
                    // Elemen baru perlu dirender server agar kotak & daftar ikut muncul.
                    sessionStorage.setItem('flash-blangko', payload?.pesan || 'Elemen tersimpan.');
                    window.location.reload();

                    return;
                }

                const el = payload?.elemen;

                if (el) {
                    const index = dataElemen.findIndex((item) => String(item.id) === String(el.id));

                    if (index >= 0) {
                        dataElemen[index] = el;
                    }

                    perbaruiKotak(el);
                    perbaruiBarisDaftar(el);
                    isiForm(el);
                    sorot(terpilih);
                }

                flash(payload?.pesan || 'Elemen tersimpan.');
            })
            .catch((error) => flash(error.message, 'gagal'));
    }

    function hapus(id) {
        if (! id || ! window.confirm('Hapus elemen ini dari blangko?')) {
            return;
        }

        const url = formElemen.action.replace(/\/elemen$/, '/elemen/' + id);

        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })
            .then(async (res) => {
                const payload = await res.json().catch(() => null);

                if (! res.ok) {
                    throw new Error(pesanGalat(payload, 'Gagal menghapus elemen.'));
                }

                return payload;
            })
            .then((payload) => {
                kotakDariId(id)?.remove();
                barisDaftarDariId(id)?.remove();

                const index = dataElemen.findIndex((item) => String(item.id) === String(id));

                if (index >= 0) {
                    dataElemen.splice(index, 1);
                }

                if (String(terpilih) === String(id)) {
                    kosongkanForm();
                }

                flash(payload?.pesan || 'Elemen dihapus.');
            })
            .catch((error) => flash(error.message, 'gagal'));
    }

    /* ------------------------------------------------------------------ */
    /* Geser & ubah ukuran                                                 */
    /* ------------------------------------------------------------------ */

    function mulaiSeret(event, kotak, ubahUkuran) {
        event.preventDefault();
        event.stopPropagation();

        const id = kotak.dataset.id;

        pilih(id);

        const mulaiX = event.clientX;
        const mulaiY = event.clientY;
        const x0 = angka(kotak.dataset.x, angka(kotak.style.left.replace('mm', ''), 0));
        const y0 = angka(kotak.dataset.y, angka(kotak.style.top.replace('mm', ''), 0));
        const w0 = angka(kotak.dataset.w, angka(kotak.style.width.replace('mm', ''), 5));
        const h0 = angka(kotak.dataset.h, angka(kotak.style.height.replace('mm', ''), 3));

        let bergerak = false;

        const geser = (ev) => {
            const dx = ev.clientX - mulaiX;
            const dy = ev.clientY - mulaiY;

            if (! bergerak && Math.abs(dx) < AMBANG_GESER && Math.abs(dy) < AMBANG_GESER) {
                return;
            }

            bergerak = true;

            if (ubahUkuran) {
                kotak.style.width = Math.max(5, w0 + dx / MM) + 'mm';
                kotak.style.height = Math.max(3, h0 + dy / MM) + 'mm';
            } else {
                kotak.style.left = Math.max(0, x0 + dx / MM) + 'mm';
                kotak.style.top = Math.max(0, y0 + dy / MM) + 'mm';
            }
        };

        const lepas = () => {
            document.removeEventListener('mousemove', geser);
            document.removeEventListener('mouseup', lepas);

            if (! bergerak) {
                // Klik biasa: cukup pilih elemen, jangan simpan/reload.
                return;
            }

            const el = elemenDariId(id);

            if (! el) {
                return;
            }

            el.x_mm = Math.round(angka(kotak.style.left.replace('mm', ''), x0) * 10) / 10;
            el.y_mm = Math.round(angka(kotak.style.top.replace('mm', ''), y0) * 10) / 10;
            el.lebar_mm = Math.round(angka(kotak.style.width.replace('mm', ''), w0) * 10) / 10;
            el.tinggi_mm = Math.round(angka(kotak.style.height.replace('mm', ''), h0) * 10) / 10;

            isiForm(el);
            sorot(terpilih);
            simpan();
        };

        document.addEventListener('mousemove', geser);
        document.addEventListener('mouseup', lepas);
    }

    /* ------------------------------------------------------------------ */
    /* Pemasangan event                                                    */
    /* ------------------------------------------------------------------ */

    kanvas.querySelectorAll('.elemen').forEach((kotak) => {
        kotak.addEventListener('mousedown', (event) => {
            mulaiSeret(event, kotak, event.target.classList.contains('handle'));
        });
    });

    formElemen.addEventListener('submit', (event) => {
        event.preventDefault();
        simpan();
    });

    f.tipe.addEventListener('change', perbaruiTipeForm);

    btnReset.addEventListener('click', kosongkanForm);

    btnHapus.addEventListener('click', () => hapus(f.id.value));

    // Panel "Daftar Elemen": klik baris / tombol Edit → pilih, tombol Hapus → hapus.
    document.querySelectorAll('[data-pilih-elemen]').forEach((tombol) => {
        tombol.addEventListener('click', () => pilih(tombol.dataset.pilihElemen));
    });

    document.querySelectorAll('[data-hapus-elemen]').forEach((tombol) => {
        tombol.addEventListener('click', () => hapus(tombol.dataset.hapusElemen));
    });

    // Varian teks: tombol edit mengisi form varian.
    document.querySelectorAll('[data-varian-edit]').forEach((tombol) => {
        tombol.addEventListener('click', () => {
            const varian = JSON.parse(tombol.dataset.varianEdit);

            document.getElementById('varian_id').value = varian.id;
            document.getElementById('v_key').value = varian.key;
            document.getElementById('v_judul').value = varian.judul;
            document.getElementById('v_isi').value = varian.isi;
            document.getElementById('v_isi').scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });

    // Pesan dari proses sebelum reload (mis. setelah menambah elemen baru).
    const pesanTertunda = sessionStorage.getItem('flash-blangko');

    if (pesanTertunda) {
        sessionStorage.removeItem('flash-blangko');
        flash(pesanTertunda);
    }

    perbaruiTipeForm();
});
