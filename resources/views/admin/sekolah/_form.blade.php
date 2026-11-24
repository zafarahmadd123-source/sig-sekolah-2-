@php
    $inputClass = 'w-full rounded border border-slate-400 bg-blue-50 px-3 py-2 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200';
    $invalidClass = 'border-red-500';
@endphp

<div class="grid gap-8 lg:grid-cols-2">
    <div class="grid content-start gap-4">
        <div>
            <label for="npsn" class="mb-1 block font-medium">NPSN (8 digit)</label>
            <input type="text" id="npsn" name="npsn" maxlength="8" inputmode="numeric"
                value="{{ old('npsn', $sekolah->npsn ?? '') }}"
                class="{{ $inputClass }} @error('npsn') {{ $invalidClass }} @enderror">
            @error('npsn')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="nama" class="mb-1 block font-medium">Nama Sekolah</label>
            <input type="text" id="nama" name="nama"
                value="{{ old('nama', $sekolah->nama ?? '') }}"
                class="{{ $inputClass }} @error('nama') {{ $invalidClass }} @enderror">
            @error('nama')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="kecamatan" class="mb-1 block font-medium">Kecamatan</label>
            <input type="text" id="kecamatan" name="kecamatan"
                value="{{ old('kecamatan', $sekolah->kecamatan ?? '') }}"
                class="{{ $inputClass }} @error('kecamatan') {{ $invalidClass }} @enderror">
            @error('kecamatan')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="telepon" class="mb-1 block font-medium">Telepon</label>
            <input type="text" id="telepon" name="telepon"
                value="{{ old('telepon', $sekolah->telepon ?? '') }}"
                class="{{ $inputClass }} @error('telepon') {{ $invalidClass }} @enderror">
            @error('telepon')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="jenjang" class="mb-1 block font-medium">Jenjang</label>
            <select id="jenjang" name="jenjang"
                class="{{ $inputClass }} @error('jenjang') {{ $invalidClass }} @enderror">
                <option value="">-- Pilih Jenjang --</option>
                @foreach (['SD', 'SMP', 'SMA', 'SMK'] as $jenjang)
                    <option value="{{ $jenjang }}"
                        @selected(old('jenjang', $sekolah->jenjang ?? '') === $jenjang)>
                        {{ $jenjang }}
                    </option>
                @endforeach
            </select>
            @error('jenjang')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="status" class="mb-1 block font-medium">Status</label>
            <select id="status" name="status"
                class="{{ $inputClass }} @error('status') {{ $invalidClass }} @enderror">
                <option value="">-- Pilih Status --</option>
                @foreach (['Negeri', 'Swasta'] as $status)
                    <option value="{{ $status }}"
                        @selected(old('status', $sekolah->status ?? '') === $status)>
                        {{ $status }}
                    </option>
                @endforeach
            </select>
            @error('status')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <label for="alamat" class="font-medium">Alamat</label>
                <button type="button" id="alamat-dari-peta"
                    class="rounded bg-indigo-600 px-3 py-1 text-sm text-white hover:bg-indigo-700">
                    Isi alamat dari peta
                </button>
            </div>
            <textarea id="alamat" name="alamat" rows="3"
                class="{{ $inputClass }} @error('alamat') {{ $invalidClass }} @enderror">{{ old('alamat', $sekolah->alamat ?? '') }}</textarea>
            <p id="status-alamat" class="mt-1 min-h-5 text-sm text-slate-600"
                role="status" aria-live="polite"></p>
            @error('alamat')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid content-start gap-4">
        <div>
            <p class="font-medium">Klik peta atau geser marker untuk menentukan lokasi sekolah</p>
            <button type="button" id="gunakan-lokasi"
                class="mt-3 rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700">
                Gunakan Lokasi Saya
            </button>
            <p id="status-lokasi" class="mt-2 min-h-5 text-sm text-red-600" role="status" aria-live="polite"></p>
        </div>

        <div id="peta-sekolah" class="h-[300px] w-full rounded border border-slate-400"></div>
        <p id="status-peta" class="hidden text-sm text-red-600" role="alert">
            Peta gagal dimuat. Periksa koneksi internet lalu muat ulang halaman.
        </p>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="latitude" class="mb-1 block font-medium">Latitude</label>
                @if (auth()->user()->isSekolah())
                    <p class="mb-1 text-sm text-gray-500">
                        Perubahan koordinat dibatasi maksimal
                        {{ number_format(\App\Models\Sekolah::MAX_COORDINATE_DRIFT_METERS / 1000, 0, ',', '.') }}
                        km dari titik awal.
                    </p>
                @endif
                <input type="number" step="any" id="latitude" name="latitude"
                    value="{{ old('latitude', $sekolah->latitude ?? '') }}"
                    class="{{ $inputClass }} @error('latitude') {{ $invalidClass }} @enderror">
                @error('latitude')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="longitude" class="mb-1 block font-medium">Longitude</label>
                <input type="number" step="any" id="longitude" name="longitude"
                    value="{{ old('longitude', $sekolah->longitude ?? '') }}"
                    class="{{ $inputClass }} @error('longitude') {{ $invalidClass }} @enderror">
                @error('longitude')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex flex-wrap gap-3 pt-1">
            <button type="submit" class="rounded bg-blue-700 px-5 py-2 text-white hover:bg-blue-800">
                Simpan
            </button>
            <a href="{{ auth()->user()->isDinas() ? route('dinas.sekolah.index') : route('dashboard') }}"
                class="rounded bg-slate-300 px-5 py-2 text-gray-800 hover:bg-slate-400">
                Batal
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (() => {
        const mapElement = document.getElementById('peta-sekolah');
        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');
        const locationStatus = document.getElementById('status-lokasi');
        const mapStatus = document.getElementById('status-peta');
        const fallbackLocation = [-3.3194, 114.5908];
        let lastReverseGeocodingAt = 0;

        if (!mapElement || typeof L === 'undefined') {
            if (mapStatus) {
                mapStatus.classList.remove('hidden');
            }

            return;
        }

        const initialLatitude = Number.parseFloat(latitudeInput.value);
        const initialLongitude = Number.parseFloat(longitudeInput.value);
        const hasInitialLocation = Number.isFinite(initialLatitude)
            && Number.isFinite(initialLongitude);
        const initialLocation = hasInitialLocation
            ? [initialLatitude, initialLongitude]
            : fallbackLocation;
        const map = L.map(mapElement).setView(initialLocation, hasInitialLocation ? 15 : 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const marker = L.marker(initialLocation, { draggable: true }).addTo(map);

        function setLocation(latitude, longitude, zoom = null) {
            latitudeInput.value = latitude.toFixed(7);
            longitudeInput.value = longitude.toFixed(7);
            marker.setLatLng([latitude, longitude]);

            if (zoom !== null) {
                map.setView([latitude, longitude], zoom);
            }
        }

        map.on('click', (event) => {
            setLocation(event.latlng.lat, event.latlng.lng);
            locationStatus.textContent = '';
        });

        marker.on('dragend', () => {
            const position = marker.getLatLng();
            setLocation(position.lat, position.lng);
            locationStatus.textContent = '';
        });

        function updateMarkerFromInputs() {
            const latitude = Number.parseFloat(latitudeInput.value);
            const longitude = Number.parseFloat(longitudeInput.value);

            if (Number.isFinite(latitude) && Number.isFinite(longitude)
                && latitude >= -90 && latitude <= 90
                && longitude >= -180 && longitude <= 180) {
                marker.setLatLng([latitude, longitude]);
                map.panTo([latitude, longitude]);
            }
        }

        latitudeInput.addEventListener('change', updateMarkerFromInputs);
        longitudeInput.addEventListener('change', updateMarkerFromInputs);

        document.getElementById('alamat-dari-peta').addEventListener('click', async (event) => {
            const button = event.currentTarget;
            const latitude = Number.parseFloat(latitudeInput.value);
            const longitude = Number.parseFloat(longitudeInput.value);

            if (!Number.isFinite(latitude) || !Number.isFinite(longitude)
                || latitude < -90 || latitude > 90
                || longitude < -180 || longitude > 180) {
                document.getElementById('status-alamat').textContent =
                    'Pilih koordinat yang valid pada peta terlebih dahulu.';
                return;
            }

            const addressStatus = document.getElementById('status-alamat');
            button.disabled = true;
            addressStatus.textContent = 'Mencari alamat...';

            try {
                const delay = Math.max(0, 1100 - (Date.now() - lastReverseGeocodingAt));
                if (delay > 0) {
                    await new Promise((resolve) => setTimeout(resolve, delay));
                }
                lastReverseGeocodingAt = Date.now();

                const url = new URL('https://nominatim.openstreetmap.org/reverse');
                url.search = new URLSearchParams({
                    format: 'jsonv2',
                    lat: latitude,
                    lon: longitude,
                    'accept-language': 'id',
                });
                const response = await fetch(url);
                if (!response.ok) {
                    throw new Error(`Layanan alamat mengembalikan HTTP ${response.status}`);
                }

                const data = await response.json();
                if (!data.display_name) {
                    throw new Error('Alamat tidak ditemukan untuk koordinat tersebut.');
                }

                document.getElementById('alamat').value = data.display_name;
                addressStatus.textContent = 'Alamat berhasil diisi dari koordinat peta.';
            } catch (error) {
                addressStatus.textContent = 'Alamat gagal dimuat. Silakan coba lagi.';
                console.error(error);
            } finally {
                button.disabled = false;
            }
        });

        document.getElementById('gunakan-lokasi').addEventListener('click', () => {
            locationStatus.textContent = '';

            if (!navigator.geolocation) {
                locationStatus.textContent = 'Browser ini tidak mendukung lokasi perangkat.';
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    setLocation(position.coords.latitude, position.coords.longitude, 16);
                },
                (error) => {
                    locationStatus.textContent = error.code === error.PERMISSION_DENIED
                        ? 'Izin lokasi ditolak. Pilih titik dengan mengeklik peta.'
                        : 'Lokasi tidak dapat diambil. Pilih titik dengan mengeklik peta.';
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        });

        window.requestAnimationFrame(() => map.invalidateSize());
    })();
</script>
@endpush
