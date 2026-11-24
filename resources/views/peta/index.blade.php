@extends('layouts.public')
@section('title', 'Peta Sekolah')

@push('styles')
<link rel="stylesheet"
      href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css">
<link rel="stylesheet"
      href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css">
@endpush

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
    <button id="unduh-geojson" type="button">Unduh GeoJSON</button>
    <span id="jumlah"></span>
</div>
<div id="peta" class="peta"></div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<script>
    const map = Peta.buat('peta');
    const markerLayer = L.layerGroup().addTo(map);
    const clusterLayer = L.markerClusterGroup({
        showCoverageOnHover: false,
        maxClusterRadius: 60,
        spiderfyOnMaxZoom: true,
        disableClusteringAtZoom: 17,
        chunkedLoading: true,
    });
    const heatLayer = L.heatLayer([], {
        radius: 30,
        blur: 25,
        maxZoom: 17,
        gradient: {
            0.2: '#2563eb',
            0.4: '#06b6d4',
            0.6: '#84cc16',
            0.8: '#facc15',
            1.0: '#dc2626',
        },
    });
    const featureLayers = {
        Marker: markerLayer,
        Klaster: clusterLayer,
        'Peta panas': heatLayer,
    };
    L.control.layers(null, featureLayers, { collapsed: false }).addTo(map);
    let fiturTerlihat = [];
    let permintaanAktif = null;
    let pemuat;

    async function muat() {
        if (permintaanAktif) permintaanAktif.abort();
        permintaanAktif = new AbortController();

        try {
            fiturTerlihat = await Peta.ambilSekolah(
                document.getElementById('filter-jenjang').value,
                document.getElementById('filter-status').value,
                map.getBounds(),
                permintaanAktif.signal
            );
            markerLayer.clearLayers();
            clusterLayer.clearLayers();
            const heatPoints = [];

            fiturTerlihat.forEach((fitur) => {
                const marker = L.marker(Peta.latlng(fitur)).bindPopup(Peta.popup(fitur));
                markerLayer.addLayer(marker);
                clusterLayer.addLayer(L.marker(Peta.latlng(fitur)).bindPopup(Peta.popup(fitur)));
                heatPoints.push([
                    ...Peta.latlng(fitur),
                    Math.max((fitur.properties.jumlah_siswa ?? 0) / 1000, 0.2),
                ]);
            });
            heatLayer.setLatLngs(heatPoints);
            document.getElementById('jumlah').textContent =
                `${fiturTerlihat.length} sekolah di area peta`;
        } catch (e) {
            if (e.name !== 'AbortError') {
                document.getElementById('jumlah').textContent = 'Gagal memuat data';
                console.error(e);
            }
        }
    }

    function unduhGeoJSON() {
        const geojson = {
            type: 'FeatureCollection',
            features: fiturTerlihat,
        };
        const file = new Blob([JSON.stringify(geojson, null, 2)], {
            type: 'application/geo+json',
        });
        const tautan = document.createElement('a');
        tautan.href = URL.createObjectURL(file);
        tautan.download = 'sekolah-dalam-area-peta.geojson';
        tautan.click();
        setTimeout(() => URL.revokeObjectURL(tautan.href), 1000);
    }

    document.getElementById('filter-jenjang').addEventListener('change', muat);
    document.getElementById('filter-status').addEventListener('change', muat);
    document.getElementById('unduh-geojson').addEventListener('click', unduhGeoJSON);
    map.on('moveend', () => {
        clearTimeout(pemuat);
        pemuat = setTimeout(muat, 200);
    });
    muat();
</script>
@endpush