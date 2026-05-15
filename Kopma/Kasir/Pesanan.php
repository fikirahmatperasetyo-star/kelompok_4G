<?php
require_once '../Controller/Session.php'; 
require_once '../Controller/Database.php';
date_default_timezone_set('Asia/Jakarta');
$session = new Session();
$session->checkLogin(); 
$session->checkRole('Kasir');
$user = $session->get('user');

$db = new Database();
$conn = $db->getConnection();

// --- LOGIKA UPDATE STATUS (TOMBOL CHECKLIST / SILANG) ---
if (isset($_GET['action']) && isset($_GET['kode'])) {
    $aksi = $_GET['action'];
    $kode_pesanan = mysqli_real_escape_string($conn, $_GET['kode']);
    
    if ($aksi === 'selesai') {
        // 1. Ambil daftar barang apa saja yang dibeli pada pesanan ini
        $q_detail = mysqli_query($conn, "SELECT nama_produk, qty FROM detail_pesanan WHERE kode_pesanan = '$kode_pesanan'");
        
        if ($q_detail) {
            // 2. Lakukan perulangan untuk mengurangi stok setiap barang di tabel produk2
            while ($item = mysqli_fetch_assoc($q_detail)) {
                $nama_produk_dibeli = mysqli_real_escape_string($conn, $item['nama_produk']);
                $jumlah_dibeli = (int)$item['qty'];
                
                // Perintah SQL untuk mengurangi stok lama dengan jumlah yang dibeli
                mysqli_query($conn, "UPDATE produk2 SET stok = stok - $jumlah_dibeli WHERE nama_produk = '$nama_produk_dibeli'");
            }
        }

        // 3. Setelah stok berhasil dikurangi, baru update status pesanan menjadi Selesai
        mysqli_query($conn, "UPDATE pesanan SET status = 'Selesai', tanggal_pengambilan = CURDATE() WHERE kode_pesanan = '$kode_pesanan'");
        
    } elseif ($aksi === 'batal') {
        // Jika batal, stok tidak perlu dikurangi
        mysqli_query($conn, "UPDATE pesanan SET status = 'Batal' WHERE kode_pesanan = '$kode_pesanan'");
    }
    
    // Refresh halaman agar pesanan yang sudah diupdate hilang dari antrean
    header("Location: Pesanan.php");
    exit;
}
// --------------------------------------------------------

// Mengambil HANYA pesanan yang berstatus 'Pending', diurutkan dari yang paling lama antre (ASC)
$query = mysqli_query($conn, "SELECT * FROM pesanan WHERE status = 'Pending' ORDER BY waktu_pesan ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kopma Mart</title>
    <link rel="icon" href="../WebPictures/LOGO KOPMA.png" type="image/png">
    <link rel="stylesheet" href="../Style/Dashboard.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .badge { padding: 5px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; }
        .bg-warning { background: #fef08a; color: #a16207; } 
        
        .btn-aksi { padding: 8px 12px; border: none; border-radius: 6px; cursor: pointer; color: white; font-size: 0.9rem; text-decoration: none; display: inline-block; margin: 2px; }
        .btn-hijau { background-color: #10b981; } 
        .btn-merah { background-color: #ef4444; } 
        .btn-abu { background-color: #6b7280; font-size: 0.8rem; padding: 6px 10px; } 
        .btn-aksi:hover { opacity: 0.8; transform: scale(1.05); transition: 0.2s; }
        
        td { vertical-align: middle; }

        /* --- CSS UNTUK MODAL (POP-UP) DETAIL PESANAN --- */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; top: 0; width: 100%; height: 100%; 
            background-color: rgba(0,0,0,0.5); 
        }
        .modal-content {
            background-color: #fff; 
            margin: 10% auto; 
            padding: 25px; 
            border-radius: 10px; 
            width: 50%; 
            max-width: 600px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
        }
        .close-btn {
            position: absolute; right: 20px; top: 15px; 
            color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer;
        }
        .close-btn:hover { color: red; }
        .detail-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .detail-table th, .detail-table td { border-bottom: 1px solid #ddd; padding: 10px; text-align: left; }
        .detail-table th { background-color: #f4f7f6; color: #333; }
    </style>
</head>
<body>

<div class="container">
    <aside class="sidebar">
        <div class="logo-section">
            <img src="../WebPictures/LOGO KOPMA.png" alt="Logo Kopma" style="width: 60px; margin-bottom: 10px;">
            <h2>KOPMA MART</h2>
        </div>

        <nav class="menu">
            <p class="menu-title">Kasir</p>
            <ul>
                <a href="DashboardKasir.php" style="text-decoration:none; color:inherit;">
                <li><i class="fas fa-home"></i> Dashboard</li></a>
                <li class="active"><i class="fas fa-bell"></i> Pesanan</li>
                <a href="Produk.php" style="text-decoration:none; color:inherit;">
                <li><i class="fas fa-box-open"></i> Produk</li></a>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?php echo $user['name'] ?? 'Kasir'; ?></span>
            </div>
            <a href="../Controller/Logout.php" style="text-decoration: none;">
                <button class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</button>
            </a>
        </div>
    </aside>

    <main class="main-content">
        <header>
            <h1>Pesanan Masuk</h1>
            <p style="color: #666; margin-top: -20px; font-size: 0.9rem;">Hanya menampilkan pesanan yang belum diproses (Pending).</p>
        </header>

        <section class="content-box table-section" style="margin-top: 20px;">
            <div class="box-header">
                <h3>Daftar Antrean</h3>
            </div>
            
            <table style="width: 100%; text-align: left;">
                <thead>
                    <tr>
                        <th>Kode Pesanan</th>
                        <th>Nama Pembeli</th>
                        <th>Tgl Pemesanan</th>
                        <th>Nomor HP</th>
                        <th>Metode Pembayaran</th>
                        <th>Detail Pesanan</th>
                        <th>Total Pembelian</th>
                        <th>Status</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($query) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($query)): ?>
                            <tr>
                                <td style="font-weight:bold; color: #3c8a55;"><?php echo $row['kode_pesanan']; ?></td>
                                <td style="font-weight:600;"><?php echo $row['nama_pembeli']; ?></td>
                                <td><?php echo date('d-m-Y H:i', strtotime($row['waktu_pesan'])); ?></td>
                                <td><?php echo $row['no_hp']; ?></td>
                                <td>
                                    <?php echo $row['metode_pembayaran']; ?>
                                    <?php if($row['metode_pembayaran'] === 'QRIS' && !empty($row['bukti_pembayaran'])): ?>
                                        <br><a href="../Beranda/uploads/<?php echo $row['bukti_pembayaran']; ?>" target="_blank" style="font-size: 0.75rem; color: #3b82f6; text-decoration: underline;">Lihat Bukti</a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <!-- Tombol Pemicu Modal JavaScript -->
                                    <button class="btn-aksi btn-abu" onclick="bukaModal('modal-<?php echo $row['kode_pesanan']; ?>')"><i class="fas fa-list"></i> Lihat</button>
                                    
                                    <!-- MODAL DETAIL DIPINDAHKAN KE DALAM TD INI AGAR HTML TIDAK ERROR -->
                                    <div id="modal-<?php echo $row['kode_pesanan']; ?>" class="modal" style="white-space: normal; text-align: left;">
                                        <div class="modal-content">
                                            <span class="close-btn" onclick="tutupModal('modal-<?php echo $row['kode_pesanan']; ?>')">&times;</span>
                                            <h2 style="color: #3c8a55; margin-bottom: 10px;">Detail Pesanan</h2>
                                            
                                            <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                                                <p><strong>Kode:</strong> <?php echo $row['kode_pesanan']; ?></p>
                                                <p><strong>Pemesan:</strong> <?php echo $row['nama_pembeli']; ?> (<?php echo $row['no_hp']; ?>)</p>
                                                <p><strong>Tgl Pengambilan:</strong> <span style="color: red; font-weight: bold;"><?php echo date('d-m-Y | H:i', strtotime($row['tanggal_pengambilan'])); ?> WIB</span></p>
                                                <p><strong>Alamat:</strong> <?php echo $row['alamat']; ?></p>
                                            </div>

                                            <h4>Daftar Barang:</h4>
                                            <table class="detail-table">
                                                <tr>
                                                    <th>Produk</th>
                                                    <th>Qty</th>
                                                    <th>Subtotal</th>
                                                </tr>
                                                <?php
                                                // Mengambil detail belanjaan khusus untuk kode_pesanan ini
                                                $kdp = $row['kode_pesanan'];
                                                $q_detail = mysqli_query($conn, "SELECT * FROM detail_pesanan WHERE kode_pesanan = '$kdp'");
                                                while($d = mysqli_fetch_assoc($q_detail)){
                                                    echo "<tr>
                                                            <td>{$d['nama_produk']}</td>
                                                            <td>{$d['qty']}x</td>
                                                            <td style='font-weight:bold;'>Rp " . number_format($d['subtotal'], 0, ',', '.') . "</td>
                                                          </tr>";
                                                }
                                                ?>
                                                <tr>
                                                    <td colspan="2" style="text-align: right; font-weight: bold;">Total Keseluruhan:</td>
                                                    <td style="font-weight: bold; color: #3c8a55; font-size: 1.1rem;">Rp <?php echo number_format($row['total_harga'], 0, ',', '.'); ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                    <!-- AKHIR MODAL -->
                                </td>
                                <td style="font-weight: bold;">Rp <?php echo number_format($row['total_harga'], 0, ',', '.'); ?></td>
                                <td>
                                    <span class="badge bg-warning"><?php echo $row['status']; ?></span>
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <!-- Mengirim data lewat URL (GET) ke bagian atas PHP file ini -->
                                    <a href="?action=selesai&kode=<?php echo $row['kode_pesanan']; ?>" class="btn-aksi btn-hijau" title="Selesaikan Pesanan" onclick="return confirm('Tandai pesanan ini Selesai?');"><i class="fas fa-check"></i></a>
                                    <a href="?action=batal&kode=<?php echo $row['kode_pesanan']; ?>" class="btn-aksi btn-merah" title="Tolak/Batalkan Pesanan" onclick="return confirm('Yakin ingin menolak/membatalkan pesanan ini?');"><i class="fas fa-times"></i></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align:center; padding: 40px; color: #888;">
                                <i class="fas fa-box-open" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                Antrean kosong. Belum ada pesanan baru.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>

<!-- JAVASCRIPT UNTUK MENGENDALIKAN MODAL POP-UP -->
<script>
    // Fungsi untuk membuka modal berdasarkan ID
    function bukaModal(modalId) {
        document.getElementById(modalId).style.display = "block";
    }

    // Fungsi untuk menutup modal berdasarkan ID
    function tutupModal(modalId) {
        document.getElementById(modalId).style.display = "none";
    }

    // Jika user mengklik di luar area kotak putih modal, tutup modalnya
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = "none";
        }
    }
</script>

</body>
</html>