<?php
require_once '../Controller/Session.php';
require_once '../Controller/Database.php';

$session = new Session();
$session->checkLogin(); // Proteksi halaman
$session->checkRole('Admin');
$admin = $session->get('user'); // Ambil data admin untuk sidebar

$db = new Database();
$conn = $db->getConnection();
$query = mysqli_query($conn, "SELECT * FROM produk2 ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kopma Mart</title>
    <link rel="icon" href="../WebPictures/LOGO KOPMA.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../Style/Dashboard.css">
    
    <style>
        /* Overlay Latar Belakang Gelap */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000; justify-content: center; align-items: center; }
        /* Kotak Modal Putih */
        .modal-box { background: #fff; width: 600px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); overflow: hidden; animation: slideDown 0.3s ease-out; }
        @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        /* Header Modal */
        .modal-header { padding: 20px 30px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h2 { color: #999; margin: 0; font-size: 20px; font-weight: normal; }
        .close-btn { background: none; border: 2px solid #ccc; color: #ccc; font-size: 20px; width: 35px; height: 35px; border-radius: 50%; cursor: pointer; display: flex; justify-content: center; align-items: center; transition: 0.2s; }
        .close-btn:hover { border-color: #ff4d4d; color: #ff4d4d; }
        /* Body Modal (Form) */
        .modal-body { padding: 30px; display: flex; gap: 20px; }
        .form-kolom-kiri, .form-kolom-kanan { flex: 1; display: flex; flex-direction: column; gap: 15px; }
        .form-group label { display: block; font-weight: bold; color: #666; margin-bottom: 5px; font-size: 13px; }
        .form-group input, .form-group select { width: 100%; padding: 10px 15px; border: 1px solid #ccc; border-radius: 8px; background-color: #fdfaf6; font-weight: bold; color: #000; }
        
        /* Area Gambar Upload - DIPERBAIKI */
        .image-upload-area { border: 2px dashed #ccc; border-radius: 8px; height: 150px; display: flex; flex-direction: column; justify-content: center; align-items: center; color: #999; cursor: pointer; background-color: #f9f9f9; overflow: hidden; position: relative; }
        .image-upload-area img { width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0; display: none; z-index: 10; } /* Tambah z-index dan posisi agar menutupi icon */
        
        /* Footer Modal */
        .modal-footer { padding: 15px 30px; border-top: 1px solid #ddd; display: flex; justify-content: flex-end; gap: 15px; background: #f9f9f9; }
        .btn-modal-batal { padding: 10px 25px; border: 1px solid #ff4d4d; background: white; color: #ff4d4d; border-radius: 5px; font-weight: bold; cursor: pointer; }
        .btn-modal-simpan { padding: 10px 25px; border: none; background: #2d5a3a; color: white; border-radius: 5px; font-weight: bold; cursor: pointer; }

        /* Badge Status Stok */
        .badge-status { padding: 5px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; }
        .status-aman { background-color: #d1fae5; color: #065f46; }
        .status-rendah { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

<div class="container">
    
    <aside class="sidebar">
        <div class="logo-section">
            <img src="../WebPictures/LOGO KOPMA.png" alt="Logo Kopma">
            <h2>KOPMA MART</h2>
        </div>

        <nav class="menu">
            <p class="menu-title">Manajemen Kopma Mart</p>
            <ul>
                <a href="A2-Dashboard.php" style="text-decoration:none; color:inherit;">
                <li><i class="fas fa-home"></i> Dashboard</li></a>
                <li class="active"><i class="fas fa-book"></i> Produk</li>
                <a href="A4-Data-Barang.php" style="text-decoration:none; color:inherit;">
                <li><i class="fas fa-chart-line"></i> Laporan</li></a>
                <a href="A6-Manajemen-Akun.php" style="text-decoration:none; color:inherit;">
                <li><i class="fas fa-users-cog"></i> Manajemen</li></a>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?php echo $admin['name']; ?></span>
            </div>
            <a href="../Controller/logout.php" style="text-decoration: none;">
                <button class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </a>
        </div>
    </aside>

    <main class="main-content">
        <header>
            <h1>Daftar Produk</h1>
            <p style="color: #666; margin-top: -20px; font-size: 0.9rem;">Kelola katalog barang yang akan tampil di halaman pembeli.</p>
        </header>

        <section class="content-box table-section" style="margin-top: 20px;">
            <div class="box-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3>Manajemen Produk</h3>
                <button class="btn-green" onclick="bukaModalTambah()" style="background: #2d5a3a; color: white; padding: 10px 15px; border-radius: 8px; border: none; cursor: pointer;">
                    + Tambah Produk
                </button>
            </div>

            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; border-bottom: 2px solid #eee;">
                        <th style="padding: 10px;">PRODUK</th>
                        <th>KATEGORI</th>
                        <th>HARGA</th>
                        <th>STOK</th>
                        <th>STATUS</th>
                        <th>AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($query)) { 
                        // Logika Penentuan Status Stok
                        $stok = $row['stok'];
                        if ($stok <= 10) { 
                            $teks_status = "Stok Rendah";
                            $class_status = "status-rendah";
                        } else {
                            $teks_status = "Stok Aman";
                            $class_status = "status-aman";
                        }
                    ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td class="produk" style="padding: 10px; display: flex; align-items: center; gap: 15px;">
                            <img src="assets/<?php echo $row['gambar']; ?>" width="40" style="border-radius: 5px; background: #f9f9f9; padding: 2px;">
                            <span style="font-weight: 600;"><?php echo $row['nama_produk']; ?></span>
                        </td>
                        <td><?php echo $row['kategori']; ?></td>
                        <td>Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
                        <td style="font-weight: bold;"><?php echo $row['stok']; ?> buah</td>
                        <td>
                            <span class="badge-status <?php echo $class_status; ?>">
                                <?php echo $teks_status; ?>
                            </span>
                        </td>
                        <td>
                            <!-- PENTING: Tambahkan assets/ pada parameter gambar agar modal edit tau alamat gambarnya -->
                            <button class="edit" onclick="bukaModalEdit(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['nama_produk'], ENT_QUOTES); ?>', '<?php echo $row['kategori']; ?>', <?php echo $row['harga']; ?>, <?php echo $row['stok']; ?>, 'assets/<?php echo $row['gambar']; ?>')" style="background: #f1c40f; border: none; padding: 6px 10px; border-radius: 5px; cursor: pointer; color: white;"><i class="fas fa-edit"></i></button>
                            
                            <button class="hapus" onclick="hapusProduk(<?php echo $row['id']; ?>)" style="background: #e74c3c; border: none; padding: 6px 10px; border-radius: 5px; cursor: pointer; color: white;"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </section>
    </main>
</div>

<div class="modal-overlay" id="modalTambahProduk">
    <div class="modal-box">
        
        <div class="modal-header">
            <h2 id="judulModal">Tambah Produk Baru</h2>
            <button class="close-btn" onclick="tutupModal()"><i class="fas fa-times"></i></button>
        </div>

        <form action="../Controller/Proses-Insert-Table-Produk.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_produk" id="id_produk" value="">

            <div class="modal-body">
                <div class="form-kolom-kiri">
                    <div class="form-group">
                        <label>NAMA PRODUK</label>
                        <input type="text" name="nama" id="inputNama" placeholder="Contoh: Beng - beng" required>
                    </div>
                    <div class="form-group">
                        <label>KATEGORI</label>
                        <select name="kategori" id="inputKategori" required>
                            <option value="Makanan">Makanan</option>
                            <option value="Minuman">Minuman</option>
                            <option value="ATK">Alat Tulis Kantor</option>
                            <option value="Dapur">Bahan Dapur</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>HARGA JUAL (RP)</label>
                        <input type="number" name="harga" id="inputHarga" placeholder="Contoh: 3500" required>
                    </div>
                    <div class="form-group">
                        <label>STOK</label>
                        <input type="number" name="stok" id="inputStok" placeholder="Contoh: 125" required>
                    </div>
                </div>

                <div class="form-kolom-kanan">
                    <div class="form-group">
                        <label>GAMBAR PRODUK (Kosongkan jika tidak diubah)</label>
                        <div class="image-upload-area" onclick="document.getElementById('inputFoto').click()">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 30px; margin-bottom: 10px;"></i>
                            <span>Klik untuk upload foto</span>
                            <img id="previewFoto" src="" alt="Preview">
                        </div>
                        <input type="file" id="inputFoto" name="gambar" accept="image/*" style="display: none;" onchange="tampilkanPreview(this)">
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-modal-batal" onclick="tutupModal()">Batal</button>
                <button type="submit" class="btn-modal-simpan">Simpan Produk</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Membuka modal untuk mode TAMBAH
    function bukaModalTambah() {
        document.getElementById('modalTambahProduk').style.display = 'flex';
        document.getElementById('judulModal').innerText = 'Tambah Produk Baru';
        document.getElementById('id_produk').value = ''; 
        document.querySelector('form').reset(); 
        
        // Sembunyikan preview gambar
        var preview = document.getElementById('previewFoto');
        preview.src = '';
        preview.style.display = 'none';
    }

    // Membuka modal untuk mode EDIT dan otomatis mengisi data
    function bukaModalEdit(id, nama, kategori, harga, stok, gambarPath) {
        document.getElementById('modalTambahProduk').style.display = 'flex';
        document.getElementById('judulModal').innerText = 'Edit Data Produk';
        
        document.getElementById('id_produk').value = id;
        document.getElementById('inputNama').value = nama;
        document.getElementById('inputKategori').value = kategori;
        document.getElementById('inputHarga').value = harga;
        document.getElementById('inputStok').value = stok;
        
        // Tampilkan foto produk saat ini jika diedit
        var preview = document.getElementById('previewFoto');
        if (gambarPath && !gambarPath.endsWith('assets/')) {
            preview.src = gambarPath;
            preview.style.display = 'block';
        } else {
            preview.src = '';
            preview.style.display = 'none';
        }
    }

    // Fungsi HAPUS dengan konfirmasi
    function hapusProduk(id) {
        if(confirm('Apakah Anda yakin ingin menghapus produk ini dari katalog?')) {
            window.location.href = '../Controller/Proses-Hapus-Produk.php?id=' + id;
        }
    }

    function tutupModal() {
        document.getElementById('modalTambahProduk').style.display = 'none';
    }

    // Memicu gambar langsung muncul setelah diupload
    function tampilkanPreview(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var preview = document.getElementById('previewFoto');
                preview.src = e.target.result;
                preview.style.display = 'block'; // Ini akan langsung menimpa area dengan gambar baru
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

</body>
</html>