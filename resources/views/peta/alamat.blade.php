@extends('layouts.public')
@section('title', 'Cek Lokasi')

@section('content')
<div class="toolbar">
    <button id="lokasi-saya" type="button">Lokasi saya</button>
    <span id="status-lokasi" role="status" aria-live="polite"></span>
    <span>Klik di peta atau gunakan lokasi perangkat untuk mencari sekolah terdekat.</span>
</div>

<div id="peta" class="peta"></div>

<div id="hasil" class="panel"
     style="top: 64px; right: 12px; width: 320px; max-height: 70%;
            overflow: auto; display: none;"></div>
@endsection

@push('scripts')
<script>
    const map = Peta.buat('peta');
    const lapisan = L.layerGroup().addTo(map);
    const panel = document.getElementById('hasil');
    const tombolLokasi = document.getElementById('lokasi-saya');
    let sekolahTerdekat = [];
    let operasiLokasi = 0;
    let permintaanGeocodingTerakhir = 0;
    let antreanGeocoding = Promise.resolve();

    function batasSekitar([latitude, longitude], radiusKm) {
        const latitudeDelta = radiusKm / 110.574;
        const cosineLatitude = Math.max(Math.abs(Math.cos(latitude * Math.PI / 180)), 0.01);
        const longitudeDelta = radiusKm / (111.32 * cosineLatitude);

        return L.latLngBounds(
            [
                Math.max(-90, latitude - latitudeDelta),
                Math.max(-180, longitude - longitudeDelta),
            ],
            [
                Math.min(90, latitude + latitudeDelta),
                Math.min(180, longitude + longitudeDelta),
            ]
        );
    }

    async function sekolahTerdekatDari(titik) {
        let radiusKm = 2;

        while (radiusKm <= 128) {
            const fitur = await Peta.ambilSekolah('', '', batasSekitar(titik, radiusKm));
            const terurut = fitur
                .map((fiturSekolah) => ({
                    fitur: fiturSekolah,
                    jarak: Peta.jarakKm(titik, Peta.latlng(fiturSekolah)),
                }))
                .sort((a, b) => a.jarak - b.jarak);

            if (terurut.length >= 3 && terurut[2].jarak <= radiusKm) {
                return {
                    terdekat: terurut.slice(0, 3),
                    jumlahDalamRadius: terurut.filter((item) => item.jarak <= 1).length,
                };
            }

            if (radiusKm === 128) {
                return {
                    terdekat: terurut.slice(0, 3),
                    jumlahDalamRadius: terurut.filter((item) => item.jarak <= 1).length,
                };
            }

            radiusKm *= 2;
        }

        return { terdekat: [], jumlahDalamRadius: 0 };
    }

    function ambilAlamat(latitude, longitude) {
        const permintaan = antreanGeocoding.then(async () => {
            const jeda = Math.max(0, 1100 - (Date.now() - permintaanGeocodingTerakhir));
            if (jeda > 0) {
                await new Promise((resolve) => setTimeout(resolve, jeda));
            }
            permintaanGeocodingTerakhir = Date.now();

            const url = new URL('https://nominatim.openstreetmap.org/reverse');
            url.search = new URLSearchParams({
                format: 'jsonv2',
                addressdetails: '1',
                'accept-language': 'id',
                lat: latitude,
                lon: longitude,
            });
            const respon = await fetch(url);
            if (!respon.ok) throw new Error(`Layanan alamat mengembalikan HTTP ${respon.status}`);

            return respon.json();
        });
        antreanGeocoding = permintaan.then(() => undefined, () => undefined);

        return permintaan;
    }

    async function tampilkanLokasi(titik) {
        const operasi = ++operasiLokasi;
        const [latitude, longitude] = titik;
        lapisan.clearLayers();
        L.circle(titik, {
            radius: 1000,
            color: '#2563eb',
            fillColor: '#3b82f6',
            fillOpacity: 0.12,
            weight: 2,
        }).addTo(lapisan);
        L.circleMarker(titik, {
            radius: 8,
            color: '#fff',
            weight: 3,
            fillColor: '#ef4444',
            fillOpacity: 1,
        }).addTo(lapisan);
        panel.style.display = 'block';
        panel.textContent = 'Mencari sekolah dan alamat...';

        let hasilSekolah = { terdekat: [], jumlahDalamRadius: 0 };
        let pesanSekolah = '';
        try {
            hasilSekolah = await sekolahTerdekatDari(titik);
        } catch (error) {
            pesanSekolah = 'Data sekolah gagal dimuat.';
            console.error(error);
        }

        let bagianAlamat = '<em>Alamat tidak dapat dimuat.</em>';
        try {
            const dataAlamat = await ambilAlamat(latitude, longitude);
            const alamat = dataAlamat.address || {};
            const barisAlamat = [
                ['Jalan', alamat.road],
                ['Kelurahan/Desa', alamat.suburb || alamat.neighbourhood || alamat.village],
                ['Kota/Kabupaten', alamat.city || alamat.town || alamat.municipality || alamat.county],
                ['Provinsi', alamat.state],
                ['Kode pos', alamat.postcode],
            ].map(([label, nilai]) => `
                <tr>
                    <td style="padding: 2px 8px 2px 0; color: #6b7280;">${label}</td>
                    <td>${Peta.esc(nilai) || '-'}</td>
                </tr>`).join('');

            bagianAlamat = `
                <p style="margin: 6px 0;">${Peta.esc(dataAlamat.display_name || '-')}</p>
                <table style="font-size: 13px;">${barisAlamat}</table>`;
        } catch (error) {
            console.error(error);
        }

        if (operasi !== operasiLokasi) return;

        sekolahTerdekat = hasilSekolah.terdekat.map((item) => item.fitur);
        const daftar = hasilSekolah.terdekat.map((item, index) => `
            <li data-index="${index}"
                style="padding: 6px 0; border-top: 1px solid #e5e7eb; cursor: pointer;">
                <strong>${Peta.esc(item.fitur.properties.nama)}</strong><br>
                <small>${item.jarak.toFixed(2)} km (garis lurus)</small>
            </li>`).join('');

        panel.innerHTML = `
            <strong>Koordinat</strong>
            <div style="font-family: monospace;">${latitude.toFixed(6)}, ${longitude.toFixed(6)}</div>
            <strong style="display: block; margin-top: 8px;">Alamat</strong>
            ${bagianAlamat}
            <strong style="display: block; margin-top: 8px;">Jangkauan 1 km</strong>
            <div>${hasilSekolah.jumlahDalamRadius} sekolah dalam lingkaran biru.</div>
            <strong style="display: block; margin-top: 8px;">Sekolah terdekat</strong>
            ${pesanSekolah ? `<p role="alert">${pesanSekolah}</p>` : ''}
            ${daftar ? `<ul style="list-style: none; margin: 4px 0 0; padding: 0;">${daftar}</ul>`
                : '<p>Tidak ada sekolah yang ditemukan di sekitar lokasi.</p>'}`;
    }

    map.on('click', (event) => {
        tampilkanLokasi([event.latlng.lat, event.latlng.lng]);
    });

    panel.addEventListener('click', (event) => {
        const item = event.target.closest('[data-index]');
        if (!item) return;

        const fitur = sekolahTerdekat[Number(item.dataset.index)];
        if (fitur) {
            L.popup().setLatLng(Peta.latlng(fitur)).setContent(Peta.popup(fitur)).openOn(map);
            map.flyTo(Peta.latlng(fitur), 17);
        }
    });

    tombolLokasi.addEventListener('click', () => {
        const status = document.getElementById('status-lokasi');
        status.textContent = '';

        if (!navigator.geolocation) {
            status.textContent = 'Browser ini tidak mendukung lokasi perangkat.';
            return;
        }

        tombolLokasi.disabled = true;
        navigator.geolocation.getCurrentPosition(
            (position) => {
                tombolLokasi.disabled = false;
                const titik = [position.coords.latitude, position.coords.longitude];
                map.setView(titik, 14);
                tampilkanLokasi(titik);
            },
            (error) => {
                tombolLokasi.disabled = false;
                status.textContent = error.code === error.PERMISSION_DENIED
                    ? 'Izin lokasi ditolak. Pilih titik dengan mengeklik peta.'
                    : 'Lokasi tidak dapat diambil. Pilih titik dengan mengeklik peta.';
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    });
</script>
@endpush
