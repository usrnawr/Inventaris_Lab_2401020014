<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Inventaris</title>
</head>
<body>
    <h1>Ubah Inventaris</h1>

    @if (session('gagal'))
        <p>{{ session('gagal') }}</p>
    @endif

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('inventaris.update', $inventaris['id']) }}"
          method="POST" novalidate>
        @csrf
        @method('PUT')
        <p>Kode Barang<br>
            <input type="text" name="kode_barang"
                   value="{{ old('kode_barang', $inventaris['kode_barang']) }}">
        </p>
        <p>Nama Barang<br>
            <input type="text" name="nama_barang"
                   value="{{ old('nama_barang', $inventaris['nama_barang']) }}">
        </p>
        <p>Nomor Aset<br>
            <input type="text" name="nomor_aset"
                   value="{{ old('nomor_aset', $inventaris['nomor_aset']) }}">
        </p>
        <p>Tahun Perolehan<br>
            <input type="number" name="tahun_perolehan"
                   value="{{ old('tahun_perolehan', $inventaris['tahun_perolehan']) }}">
        </p>
        <p>Ruang<br>
            <select name="ruang_id">
                @foreach ($daftarRuang as $ruang)
                    <option value="{{ $ruang['id'] }}"
                        @selected(old('ruang_id', $inventaris['ruang_id']) == $ruang['id'])>
                        {{ $ruang['nama_ruang'] }}
                    </option>
                @endforeach
            </select>
        </p>
        <button type="submit">Simpan Perubahan</button>
        <a href="{{ route('inventaris.index') }}">Kembali</a>
    </form>
</body>
</html>
