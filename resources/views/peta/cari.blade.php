@extends('layouts.public')
@section('title', 'Pencarian')

@push('styles')
<style>
    #hasil { list-style: none; margin: 8px 0 0; padding: 0; }
    #hasil li { padding: 8px 4px; border-top: 1px solid #e5e7eb;
                cursor: pointer; }
    #hasil li:hover { background: #eff6ff; }
    .paginasi { display: flex; align-items: center; justify-content: space-between;
                gap: 8px; margin-top: 10px; }
    .paginasi button:disabled { opacity: .5; cursor: not-allowed; }
</style>
@endpush

@section('content')
<div class="toolbar">
    <input type="search" id="kata" size="32"
           placeholder="Cari nama, NPSN, atau kecamatan"
           aria-label="Cari nama, NPSN, atau kecamatan">
    <select id="jenjang" aria-label="Filter jenjang">
        <option value="">Semua jenjang</option>
        <option value="SD">SD</option>
        <option value="SMP">SMP</option>
        <option value="SMA">SMA</option>
        <option value="SMK">SMK</option>
    </select>
    <button id="hapus" type="button">Hapus</button>
    <span id="jumlah" role="status" aria-live="polite">
        Masukkan minimal 2 karakter untuk mencari.
    </span>
</div>

<div id="peta" class="peta"></div>

<div class="panel"
     style="top: 64px; left: 60px; width: 280px; max-height: 55%; overflow: auto;">
    <strong>Hasil pencarian</strong>
    <ul id="hasil"></ul>
    <div id="paginasi" class="paginasi" hidden>
        <button id="sebelumnya" type="button">Sebelumnya</button>
        <span id="halaman"></span>
        <button id="selanjutnya" type="button">Selanjutnya</button>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const map = Peta.buat('peta');
    const lapisan = L.layerGroup().addTo(map);
    const inputKata = document.getElementById('kata');
    const inputJenjang = document.getElementById('jenjang');
    const daftarHasil = document.getElementById('hasil');
    const jumlah = document.getElementById('jumlah');
    const paginasi = document.getElementById('paginasi');
    let pengendaliPermintaan = null;
    let permintaanTerbaru = 0;
    let waktuTunda;
    let halamanAktif = 1;
    let bukaPopupTerjadwal = null;

    function batalkanPopupTerjadwal() {
        if (bukaPopupTerjadwal) {
            map.off('moveend', bukaPopupTerjadwal);
            bukaPopupTerjadwal = null;
        }
    }

    function kosongkanHasil() {
        batalkanPopupTerjadwal();
        lapisan.clearLayers();
        daftarHasil.replaceChildren();
        paginasi.hidden = true;
    }

    function tampilkanHasil(fitur) {
        kosongkanHasil();

        fitur.forEach((item) => {
            const properti = item.properties;
            const marker = L.marker(Peta.latlng(item))
                .bindPopup(Peta.popup(item))
                .addTo(lapisan);
            const baris = document.createElement('li');
            baris.textContent =
                `${properti.nama} - ${properti.npsn} (${properti.kecamatan})`;
            baris.addEventListener('click', () => {
                batalkanPopupTerjadwal();
                const posisi = L.latLng(Peta.latlng(item));
                const petaSudahDiLokasi =
                    map.getZoom() === 17 && map.getCenter().distanceTo(posisi) < 1;

                if (petaSudahDiLokasi) {
                    marker.openPopup();
                    return;
                }

                bukaPopupTerjadwal = () => {
                    bukaPopupTerjadwal = null;
                    marker.openPopup();
                };
                map.once('moveend', bukaPopupTerjadwal);
                map.flyTo(posisi, 17);
            });
            daftarHasil.appendChild(baris);
        });
    }

    function perbaruiPaginasi(info) {
        halamanAktif = info.current_page;
        paginasi.hidden = info.last_page <= 1;
        document.getElementById('halaman').textContent =
            `Halaman ${info.current_page} dari ${info.last_page}`;
        document.getElementById('sebelumnya').disabled = info.current_page <= 1;
        document.getElementById('selanjutnya').disabled =
            info.current_page >= info.last_page;
    }

    async function cari(halaman = 1) {
        clearTimeout(waktuTunda);
        if (pengendaliPermintaan) {
            pengendaliPermintaan.abort();
        }

        const kata = inputKata.value.trim();
        if (kata.length < 2) {
            permintaanTerbaru++;
            pengendaliPermintaan = null;
            kosongkanHasil();
            jumlah.textContent = 'Masukkan minimal 2 karakter untuk mencari.';
            return;
        }

        const idPermintaan = ++permintaanTerbaru;
        pengendaliPermintaan = new AbortController();
        kosongkanHasil();
        jumlah.textContent = 'Mencari...';

        try {
            const data = await Peta.cariSekolah(
                kata,
                inputJenjang.value,
                halaman,
                pengendaliPermintaan.signal,
            );
            if (idPermintaan !== permintaanTerbaru) return;

            tampilkanHasil(data.features);
            perbaruiPaginasi(data.pagination);
            jumlah.textContent =
                `${data.pagination.total.toLocaleString('id-ID')} sekolah ditemukan`;
        } catch (error) {
            if (error.name === 'AbortError' || idPermintaan !== permintaanTerbaru) return;
            kosongkanHasil();
            jumlah.textContent = 'Pencarian gagal. Silakan coba lagi.';
            console.error(error);
        }
    }

    inputKata.addEventListener('input', () => {
        clearTimeout(waktuTunda);
        if (pengendaliPermintaan) {
            pengendaliPermintaan.abort();
            pengendaliPermintaan = null;
        }
        permintaanTerbaru++;
        kosongkanHasil();

        if (inputKata.value.trim().length < 2) {
            jumlah.textContent = 'Masukkan minimal 2 karakter untuk mencari.';
            return;
        }

        jumlah.textContent = 'Menunggu pencarian...';
        waktuTunda = setTimeout(() => cari(1), 300);
    });

    inputJenjang.addEventListener('change', () => cari(1));
    document.getElementById('hapus').addEventListener('click', () => {
        inputKata.value = '';
        inputJenjang.value = '';
        cari();
    });
    document.getElementById('sebelumnya').addEventListener('click', () => {
        if (halamanAktif > 1) cari(halamanAktif - 1);
    });
    document.getElementById('selanjutnya').addEventListener('click', () => {
        cari(halamanAktif + 1);
    });
    inputKata.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') cari(1);
    });
</script>
@endpush
