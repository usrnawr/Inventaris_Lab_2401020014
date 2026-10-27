USE inventaris_lab_2401020014;

INSERT INTO ruang (nama_ruang) VALUES
    ('Lab Pemrograman'),
    ('Lab Jaringan');

INSERT INTO inventaris
    (kode_barang, nama_barang, nomor_aset, tahun_perolehan, ruang_id)
VALUES
    ('IV00000001', 'Komputer Praktikum', 'AST-LAB-0001', 2023, 1),
    ('IV00000002', 'Proyektor',          'AST-LAB-0002', 2022, 1),
    ('IV00000003', 'Router',             'AST-LAB-0003', 2024, 2);
