<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Inventaris Laboratorium</title>
</head>
<body>
    <h1>Data Inventaris Laboratorium</h1>

    @if (session('berhasil'))
        <p>{{ session('berhasil') }}</p>
    @endif
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

    <h2>Tambah Inventaris</h2>
    <form action="{{ route('inventaris.store') }}" method="POST" novalidate>
        @csrf
        <p>Kode Barang<br>
            <input type="text" name="kode_barang" value="{{ old('kode_barang') }}">
        </p>
        <p>Nama Barang<br>
            <input type="text" name="nama_barang" value="{{ old('nama_barang') }}">
        </p>
        <p>Nomor Aset<br>
            <input type="text" name="nomor_aset" value="{{ old('nomor_aset') }}">
        </p>
        <p>Tahun Perolehan<br>
            <input type="number" name="tahun_perolehan" value="{{ old('tahun_perolehan') }}">
        </p>
        <p>Ruang<br>
            <select name="ruang_id">
                <option value="">-- Pilih Ruang --</option>
                @foreach ($daftarRuang as $ruang)
                    <option value="{{ $ruang['id'] }}"
                        @selected(old('ruang_id') == $ruang['id'])>
                        {{ $ruang['nama_ruang'] }}
                    </option>
                @endforeach
            </select>
        </p>
        <button type="submit">Tambah Data</button>
    </form>

    <h2>Pencarian dan Filter</h2>
    <form action="{{ route('inventaris.index') }}" method="GET">
        <p>Kata Kunci (kode, nama barang, nomor aset)<br>
            <input type="text" name="q" value="{{ $kataKunci }}">
        </p>
        <p>Ruang<br>
            <select name="ruang_id">
                <option value="">-- Semua Ruang --</option>
                @foreach ($daftarRuang as $ruang)
                    <option value="{{ $ruang['id'] }}"
                        @selected((string) $ruangId === (string) $ruang['id'])>
                        {{ $ruang['nama_ruang'] }}
                    </option>
                @endforeach
            </select>
        </p>
        <button type="submit">Cari</button>
        <a href="{{ route('inventaris.index') }}">Reset</a>
    </form>

    <h2>Daftar Inventaris</h2>
    <table border="1" cellpadding="6" cellspacing="0">
        <tr>
            <th>No</th><th>Kode</th><th>Nama Barang</th><th>Nomor Aset</th>
            <th>Tahun</th><th>Status</th><th>Ruang</th><th>Aksi</th>
        </tr>
        @forelse ($daftarInventaris as $barang)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $barang['kode_barang'] }}</td>
                <td>{{ $barang['nama_barang'] }}</td>
                <td>{{ $barang['nomor_aset'] }}</td>
                <td>{{ $barang['tahun_perolehan'] }}</td>
                <td>
                    @if (statusUsiaAset((int) $barang['tahun_perolehan']) === 'Baru')
                        Baru
                    @else
                        Lama
                    @endif
                </td>
                <td>{{ $barang['nama_ruang'] }}</td>
                <td>
                    <a href="{{ route('inventaris.edit', $barang['id']) }}">Ubah</a>
                    <form action="{{ route('inventaris.destroy', $barang['id']) }}"
                          method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit">Hapus</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="8">Data tidak ditemukan.</td></tr>
        @endforelse
    </table>
</body>
</html>
