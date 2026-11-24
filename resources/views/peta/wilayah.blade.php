@extends('layouts.public')
@section('title', 'Wilayah')

@section('content')
<div class="toolbar">
    <label>Jenjang
        <select id="filter-jenjang">
            <option value="">Semua jenjang</option>
            <option value="SD">SD</option>
            <option value="SMP">SMP</option>
            <option value="SMA">SMA</option>
            <option value="SMK">SMK</option>
        </select>
    </label>
    <label>Status
        <select id="filter-status">
            <option value="">Negeri dan Swasta</option>
            <option value="Negeri">Negeri</option>
            <option value="Swasta">Swasta</option>
        </select>
    </label>
    <span id="jumlah"></span>
</div>

<div id="peta" class="peta"></div>

<div class="panel" style="right: 12px; bottom: 24px; font-size: 13px;">
    <strong>Jumlah sekolah</strong>
    <div><span style="color: #dc2626;">&#9632;</span> Terbanyak</div>
    <div><span style="color: #ea580c;">&#9632;</span> Banyak</div>
    <div><span style="color: #f59e0b;">&#9632;</span> Sedang</div>
    <div><span style="color: #3b82f6;">&#9632;</span> Sedikit</div>
    <small style="color: #6b7280;">Relatif terhadap kecamatan terbanyak</small>
    <small style="display: block; margin-top: 6px; color: #6b7280;">
        Sumber batas: <a href="https://geoservices.big.go.id/rbi/rest/services/BATASWILAYAH/BATAS_KECAMATAN_AR/MapServer"
            target="_blank" rel="noopener noreferrer">Badan Informasi Geospasial</a>, edisi Juni 2026.
    </small>
</div>
@endsection

@push('scripts')
<script>
    const map = Peta.buat('peta');
    let batasKecamatan = null;
    let batasKecamatanLayer = null;
    let batasAwalDiperbesar = false;

    function warna(rasio) {
        if (rasio >= 0.8) return '#dc2626';
        if (rasio >= 0.6) return '#ea580c';
        if (rasio >= 0.4) return '#f59e0b';
        return '#3b82f6';
    }

    async function muat() {
        const url = new URL(window.PETA_URL.wilayah, window.location.origin);
        const jenjang = document.getElementById('filter-jenjang').value;
        const status = document.getElementById('filter-status').value;
        if (jenjang) url.searchParams.set('jenjang', jenjang);
        if (status) url.searchParams.set('status', status);

        const [responJumlah, responBatas] = await Promise.all([
            fetch(url),
            batasKecamatan ? Promise.resolve(null) : fetch(window.PETA_URL.batasKecamatan),
        ]);
        if (!responJumlah.ok || (responBatas && !responBatas.ok)) {
            throw new Error('Gagal memuat data wilayah');
        }

        const dataJumlah = await responJumlah.json();
        if (responBatas) batasKecamatan = await responBatas.json();

        const features = batasKecamatan.features.map((fitur) => ({
            ...fitur,
            properties: {
                ...fitur.properties,
                jumlah_sekolah: dataJumlah.sekolah_per_kecamatan[fitur.properties.WADMKC] ?? 0,
            },
        }));
        const maksimum = Math.max(1, ...features.map((fitur) => fitur.properties.jumlah_sekolah));

        if (batasKecamatanLayer) map.removeLayer(batasKecamatanLayer);
        batasKecamatanLayer = L.geoJSON({ ...batasKecamatan, features }, {
            style: (fitur) => {
                const warnaWilayah = warna(fitur.properties.jumlah_sekolah / maksimum);
                return {
                    color: warnaWilayah,
                    fillColor: warnaWilayah,
                    fillOpacity: 0.3,
                    weight: 3,
                };
            },
            onEachFeature: (fitur, layer) => {
                const { WADMKC: kecamatan, jumlah_sekolah: jumlahSekolah } = fitur.properties;
                layer.bindTooltip(Peta.esc(kecamatan), { sticky: true });
                layer.bindPopup(`
                    <strong>${Peta.esc(kecamatan)}</strong><br>
                    Jumlah sekolah: ${jumlahSekolah}
                `);
            },
        }).addTo(map);

        document.getElementById('jumlah').textContent = `${features.length} wilayah`;
        if (!batasAwalDiperbesar && features.length > 0) {
            map.fitBounds(batasKecamatanLayer.getBounds().pad(0.1));
            batasAwalDiperbesar = true;
        }
    }

    async function muatDenganStatus() {
        try {
            await muat();
        } catch (error) {
            document.getElementById('jumlah').textContent = 'Gagal memuat data wilayah';
            console.error(error);
        }
    }

    document.getElementById('filter-jenjang').addEventListener('change', muatDenganStatus);
    document.getElementById('filter-status').addEventListener('change', muatDenganStatus);
    muatDenganStatus();
</script>
@endpush
