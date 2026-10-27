<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

/*
|--------------------------------------------------------------------------
| Fungsi bantu
|--------------------------------------------------------------------------
*/

// Modul 2: fungsi sederhana dengan percabangan
function statusUsiaAset(int $tahun): string
{
    if ($tahun >= 2023) {
        return 'Baru';
    }

    return 'Lama';
}

// Modul 3: sanitasi input (trim + strip_tags)
function bersihkanInputInventaris(Request $request): array
{
    return [
        'kode_barang' => strip_tags(trim((string) $request->input('kode_barang'))),
        'nama_barang' => strip_tags(trim((string) $request->input('nama_barang'))),
        'nomor_aset' => strip_tags(trim((string) $request->input('nomor_aset'))),
        'tahun_perolehan' => trim((string) $request->input('tahun_perolehan')),
        'ruang_id' => trim((string) $request->input('ruang_id')),
    ];
}

// Modul 3: validasi server-side
function validatorInventaris(array $data): \Illuminate\Contracts\Validation\Validator
{
    return Validator::make($data, [
        'kode_barang' => ['required', 'regex:/^[A-Za-z0-9]{10}$/'],
        'nama_barang' => ['required', 'min:3', 'max:100'],
        'nomor_aset' => ['required', 'min:5', 'max:30'],
        'tahun_perolehan' => ['required', 'integer', 'between:2000,2026'],
        'ruang_id' => ['required', 'integer'],
    ], [
        'kode_barang.required' => 'Kode barang wajib diisi.',
        'kode_barang.regex' => 'Kode barang harus tepat 10 karakter huruf/angka.',
        'nama_barang.required' => 'Nama barang wajib diisi.',
        'nama_barang.min' => 'Nama barang minimal 3 karakter.',
        'nama_barang.max' => 'Nama barang maksimal 100 karakter.',
        'nomor_aset.required' => 'Nomor aset wajib diisi.',
        'nomor_aset.min' => 'Nomor aset minimal 5 karakter.',
        'nomor_aset.max' => 'Nomor aset maksimal 30 karakter.',
        'tahun_perolehan.required' => 'Tahun perolehan wajib diisi.',
        'tahun_perolehan.integer' => 'Tahun perolehan harus berupa angka.',
        'tahun_perolehan.between' => 'Tahun perolehan harus antara 2000 dan 2026.',
        'ruang_id.required' => 'Ruang wajib dipilih.',
        'ruang_id.integer' => 'Ruang tidak valid.',
    ]);
}

// Memastikan ruang benar-benar ada di tabel master
function ruangAda(\PDO $pdo, int $id): bool
{
    $cek = $pdo->prepare('SELECT COUNT(*) FROM ruang WHERE id = :id');
    $cek->execute(['id' => $id]);

    return (int) $cek->fetchColumn() > 0;
}

/*
|--------------------------------------------------------------------------
| Route
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// READ + pencarian + filter
Route::get('/inventaris', function (Request $request) {
    $kataKunci = trim((string) $request->query('q', ''));
    $ruangId = $request->query('ruang_id', '');

    try {
        $pdo = DB::connection()->getPdo();

        $sql = 'SELECT i.id, i.kode_barang, i.nama_barang, i.nomor_aset,
                       i.tahun_perolehan, i.ruang_id, r.nama_ruang
                FROM inventaris AS i
                JOIN ruang AS r
                  ON r.id = i.ruang_id
                WHERE 1 = 1';
        $parameter = [];

        if ($kataKunci !== '') {
            $sql .= ' AND (i.kode_barang LIKE :kata_kunci_kode
                      OR i.nama_barang LIKE :kata_kunci_nama
                      OR i.nomor_aset LIKE :kata_kunci_aset)';
            $nilaiPencarian = '%' . $kataKunci . '%';
            $parameter['kata_kunci_kode'] = $nilaiPencarian;
            $parameter['kata_kunci_nama'] = $nilaiPencarian;
            $parameter['kata_kunci_aset'] = $nilaiPencarian;
        }

        if ($ruangId !== '') {
            $sql .= ' AND i.ruang_id = :ruang_id';
            $parameter['ruang_id'] = (int) $ruangId;
        }

        $sql .= ' ORDER BY i.kode_barang';

        $ambilInventaris = $pdo->prepare($sql);
        $ambilInventaris->execute($parameter);
        $daftarInventaris = $ambilInventaris->fetchAll(\PDO::FETCH_ASSOC);

        $ambilRuang = $pdo->prepare(
            'SELECT id, nama_ruang FROM ruang ORDER BY nama_ruang'
        );
        $ambilRuang->execute();
        $daftarRuang = $ambilRuang->fetchAll(\PDO::FETCH_ASSOC);

        return view('inventaris', compact(
            'daftarInventaris',
            'daftarRuang',
            'kataKunci',
            'ruangId'
        ));
    } catch (\Throwable $error) {
        report($error);

        return response(
            'Data tidak dapat dibaca. Periksa koneksi dan query.',
            500
        );
    }
})->name('inventaris.index');

// CREATE
Route::post('/inventaris', function (Request $request) {
    $data = bersihkanInputInventaris($request);
    $validator = validatorInventaris($data);

    if ($validator->fails()) {
        return back()->withErrors($validator)->withInput($data);
    }

    try {
        $pdo = DB::connection()->getPdo();

        if (!ruangAda($pdo, (int) $data['ruang_id'])) {
            return back()->withInput($data)->with(
                'gagal',
                'Ruang yang dipilih tidak tersedia.'
            );
        }

        $cek = $pdo->prepare(
            'SELECT COUNT(*) FROM inventaris
             WHERE kode_barang = :kode_barang OR nomor_aset = :nomor_aset'
        );
        $cek->execute([
            'kode_barang' => $data['kode_barang'],
            'nomor_aset' => $data['nomor_aset'],
        ]);

        if ((int) $cek->fetchColumn() > 0) {
            return back()->withInput($data)->with(
                'gagal',
                'Kode barang atau nomor aset sudah digunakan.'
            );
        }

        $simpan = $pdo->prepare(
            'INSERT INTO inventaris
             (kode_barang, nama_barang, nomor_aset, tahun_perolehan, ruang_id)
             VALUES
             (:kode_barang, :nama_barang, :nomor_aset, :tahun_perolehan, :ruang_id)'
        );
        $simpan->execute([
            'kode_barang' => $data['kode_barang'],
            'nama_barang' => $data['nama_barang'],
            'nomor_aset' => $data['nomor_aset'],
            'tahun_perolehan' => (int) $data['tahun_perolehan'],
            'ruang_id' => (int) $data['ruang_id'],
        ]);

        return redirect()->route('inventaris.index')->with(
            'berhasil',
            'Data inventaris berhasil ditambahkan.'
        );
    } catch (\Throwable $error) {
        report($error);

        return back()->withInput($data)->with(
            'gagal',
            'Data gagal disimpan. Periksa koneksi dan data.'
        );
    }
})->name('inventaris.store');

// FORM EDIT
Route::get('/inventaris/{id}/edit', function (int $id) {
    try {
        $pdo = DB::connection()->getPdo();

        $ambilInventaris = $pdo->prepare(
            'SELECT id, kode_barang, nama_barang, nomor_aset,
                    tahun_perolehan, ruang_id
             FROM inventaris WHERE id = :id'
        );
        $ambilInventaris->execute(['id' => $id]);
        $inventaris = $ambilInventaris->fetch(\PDO::FETCH_ASSOC);

        if (!$inventaris) {
            return redirect()->route('inventaris.index')->with(
                'gagal',
                'Data inventaris tidak ditemukan.'
            );
        }

        $ambilRuang = $pdo->prepare(
            'SELECT id, nama_ruang FROM ruang ORDER BY nama_ruang'
        );
        $ambilRuang->execute();
        $daftarRuang = $ambilRuang->fetchAll(\PDO::FETCH_ASSOC);

        return view('inventaris-edit', compact('inventaris', 'daftarRuang'));
    } catch (\Throwable $error) {
        report($error);

        return redirect()->route('inventaris.index')->with(
            'gagal',
            'Data gagal dibuka untuk diubah.'
        );
    }
})->name('inventaris.edit');

// UPDATE
Route::put('/inventaris/{id}', function (Request $request, int $id) {
    $data = bersihkanInputInventaris($request);
    $validator = validatorInventaris($data);

    if ($validator->fails()) {
        return redirect()->route('inventaris.edit', $id)
            ->withErrors($validator)
            ->withInput($data);
    }

    try {
        $pdo = DB::connection()->getPdo();

        if (!ruangAda($pdo, (int) $data['ruang_id'])) {
            return redirect()->route('inventaris.edit', $id)->withInput($data)->with(
                'gagal',
                'Ruang yang dipilih tidak tersedia.'
            );
        }

        $cek = $pdo->prepare(
            'SELECT COUNT(*) FROM inventaris
             WHERE (kode_barang = :kode_barang OR nomor_aset = :nomor_aset)
               AND id <> :id'
        );
        $cek->execute([
            'kode_barang' => $data['kode_barang'],
            'nomor_aset' => $data['nomor_aset'],
            'id' => $id,
        ]);

        if ((int) $cek->fetchColumn() > 0) {
            return redirect()->route('inventaris.edit', $id)->withInput($data)->with(
                'gagal',
                'Kode barang atau nomor aset sudah digunakan barang lain.'
            );
        }

        $ubah = $pdo->prepare(
            'UPDATE inventaris
             SET kode_barang = :kode_barang, nama_barang = :nama_barang,
                 nomor_aset = :nomor_aset,
                 tahun_perolehan = :tahun_perolehan,
                 ruang_id = :ruang_id
             WHERE id = :id'
        );
        $ubah->execute([
            'kode_barang' => $data['kode_barang'],
            'nama_barang' => $data['nama_barang'],
            'nomor_aset' => $data['nomor_aset'],
            'tahun_perolehan' => (int) $data['tahun_perolehan'],
            'ruang_id' => (int) $data['ruang_id'],
            'id' => $id,
        ]);

        return redirect()->route('inventaris.index')->with(
            'berhasil',
            'Data inventaris berhasil diperbarui.'
        );
    } catch (\Throwable $error) {
        report($error);

        return redirect()->route('inventaris.edit', $id)->withInput($data)->with(
            'gagal',
            'Data gagal diperbarui. Periksa koneksi dan data.'
        );
    }
})->name('inventaris.update');

// DELETE
Route::delete('/inventaris/{id}', function (int $id) {
    try {
        $pdo = DB::connection()->getPdo();

        $hapus = $pdo->prepare('DELETE FROM inventaris WHERE id = :id');
        $hapus->execute(['id' => $id]);

        if ($hapus->rowCount() === 0) {
            return redirect()->route('inventaris.index')->with(
                'gagal',
                'Data inventaris tidak ditemukan.'
            );
        }

        return redirect()->route('inventaris.index')->with(
            'berhasil',
            'Data inventaris berhasil dihapus.'
        );
    } catch (\Throwable $error) {
        report($error);

        return redirect()->route('inventaris.index')->with(
            'gagal',
            'Data gagal dihapus. Periksa relasi atau koneksi.'
        );
    }
})->name('inventaris.destroy');
