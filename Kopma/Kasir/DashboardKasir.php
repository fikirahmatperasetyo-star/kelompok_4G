<?php
require_once '../Controller/Session.php';
require_once '../Controller/Database.php';

date_default_timezone_set('Asia/Jakarta');

$session = new Session();
$session->checkLogin();
$session->checkRole('Kasir');
$admin = $session->get('user');

$db = new Database();
$conn = $db->getConnection();

// --- 1. PERHITUNGAN OTOMATIS UNTUK KARTU DASHBOARD ---
$q_pendapatan = $conn->query("SELECT SUM(total_harga) as total FROM pesanan WHERE status = 'Selesai' AND waktu_pesan >= DATE_SUB(CURDATE(), INTERVAL 4 WEEK)");
$row_pendapatan = $q_pendapatan->fetch_assoc();
$total_pendapatan = $row_pendapatan['total'] ? $row_pendapatan['total'] : 0;

$q_transaksi = $conn->query("SELECT COUNT(*) as jumlah_transaksi FROM pesanan WHERE status = 'Selesai'");
$row_transaksi = $q_transaksi->fetch_assoc();
$total_transaksi = $row_transaksi['jumlah_transaksi'] ? $row_transaksi['jumlah_transaksi'] : 0;

$q_stok = $conn->query("SELECT COUNT(*) as jumlah_tipis FROM produk2 WHERE stok <= 10");
$row_stok = $q_stok->fetch_assoc();
$total_stok_tipis = $row_stok['jumlah_tipis'] ? $row_stok['jumlah_tipis'] : 0;

$tgl_sekarang = date('d M Y');
$tgl_4_minggu_lalu = date('d M Y', strtotime('-4 weeks'));

// --- 2. QUERY PRODUK TERLARIS (Sama seperti Admin, 4 Minggu Terakhir) ---
$q_terlaris = $conn->query("
    SELECT d.nama_produk, MAX(p2.kategori) as kategori, SUM(d.qty) as total_terjual 
    FROM detail_pesanan d 
    JOIN pesanan p ON d.kode_pesanan = p.kode_pesanan 
    LEFT JOIN produk2 p2 ON d.nama_produk = p2.nama_produk 
    WHERE p.status = 'Selesai' AND p.waktu_pesan >= DATE_SUB(CURDATE(), INTERVAL 4 WEEK)
    GROUP BY d.nama_produk 
    ORDER BY total_terjual DESC 
    LIMIT 4
");

// --- 3. LOGIKA GRAFIK STATISTIK (Sama seperti Admin) ---
$chart_labels = [];
$chart_data = [];

for ($i = 3; $i >= 0; $i--) {
    $start_days = ($i * 7) + 7;
    $end_days = $i * 7;
    
    $start_date = date('Y-m-d 00:00:00', strtotime("-{$start_days} days"));
    $end_date = date('Y-m-d 23:59:59', strtotime("-{$end_days} days"));
    
    $label = date('d M', strtotime($start_date)) . ' - ' . date('d M', strtotime($end_date));
    $chart_labels[] = $label;
    
    $q_chart = $conn->query("
        SELECT SUM(d.qty) as total_item 
        FROM detail_pesanan d 
        JOIN pesanan p ON d.kode_pesanan = p.kode_pesanan 
        WHERE p.status = 'Selesai' AND p.waktu_pesan BETWEEN '$start_date' AND '$end_date'
    ");
    $res_chart = $q_chart->fetch_assoc();
    $chart_data[] = $res_chart['total_item'] ? $res_chart['total_item'] : 0;
}

// --- 4. LOGIKA PAGINATION UNTUK TABEL RIWAYAT TRANSAKSI ---
$limit = 10; // Jumlah baris data per halaman
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$halaman = ($halaman > 0) ? $halaman : 1; // Pastikan halaman minimal 1
$offset = ($halaman - 1) * $limit;

// Menghitung total data khusus untuk status Selesai & Batal
$q_total_data = $conn->query("SELECT COUNT(*) as total FROM pesanan WHERE status IN ('Selesai', 'Batal')");
$row_total = $q_total_data->fetch_assoc();
$total_data = $row_total['total'];
$total_halaman = ceil($total_data / $limit);

$result = $conn->query("SELECT * FROM pesanan WHERE status IN ('Selesai', 'Batal') ORDER BY waktu_pesan DESC LIMIT $limit OFFSET $offset");

// --- 5. MENGAMBIL DAFTAR PRODUK UNTUK DROPDOWN KASIR OFFLINE ---
$q_produk_kasir = $conn->query("SELECT id, nama_produk, harga, stok FROM produk2 WHERE stok > 0 ORDER BY nama_produk ASC");
$opsi_produk = "";
while($p = $q_produk_kasir->fetch_assoc()) {
    $opsi_produk .= "<option value='".$p['nama_produk']."' data-harga='".$p['harga']."' data-stok='".$p['stok']."'>".$p['nama_produk']." (Sisa: ".$p['stok']." | Rp ".number_format($p['harga'],0,',','.').")</option>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kopma Mart</title>
    <link rel="icon" href="../WebPictureS/LOGO KOPMA.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../Style/Dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .status { padding: 5px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; display: inline-block;}
        .status.selesai { background: #d1fae5; color: #065f46; }
        .status.pending { background: #fef08a; color: #a16207; }
        .status.batal { background: #fee2e2; color: #991b1b; } 

        .chart-js-container { width: 100%; height: 300px; padding: 20px 0; }

        .btn-aksi { padding: 6px 10px; border: none; border-radius: 6px; cursor: pointer; color: white; font-size: 0.8rem; text-decoration: none; display: inline-block; }
        .btn-abu { background-color: #6b7280; }
        .btn-green-tambah { background-color: #2ecc71; padding: 8px 15px; font-size: 0.9rem; font-weight: bold; border-radius: 8px;}
        .btn-green-tambah:hover { background-color: #27ae60; }
        .btn-aksi:hover { opacity: 0.8; }

        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fff; margin: 5% auto; padding: 25px; border-radius: 10px; width: 50%; max-width: 600px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); position: relative; max-height: 90vh; overflow-y: auto;}
        .close-btn { position: absolute; right: 20px; top: 15px; color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close-btn:hover { color: red; }
        .detail-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .detail-table th, .detail-table td { border-bottom: 1px solid #ddd; padding: 10px; text-align: left; }
        .detail-table th { background-color: #f4f7f6; color: #333; font-size: 0.9rem;}
        
        /* Form Kasir Offline */
        .form-kasir label { display: block; font-weight: bold; margin-top: 10px; margin-bottom: 5px; color: #555; font-size: 0.9rem;}
        .form-kasir input, .form-kasir select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        .item-kasir-baris { display: flex; gap: 10px; align-items: center; margin-bottom: 10px; background: #f9f9f9; padding: 10px; border-radius: 6px;}
        .btn-tambah-item { background: #3498db; color: white; border: none; padding: 8px; border-radius: 6px; cursor: pointer; margin-top: 10px; width: 100%; font-weight: bold;}
        .btn-submit-kasir { background: #3c8a55; color: white; border: none; padding: 12px; border-radius: 6px; cursor: pointer; width: 100%; font-weight: bold; font-size: 1.1rem; margin-top: 20px;}

        /* --- CSS UNTUK PAGINATION --- */
        .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 25px; list-style: none; padding: 0; }
        .pagination li a { display: block; padding: 8px 14px; border: 1px solid #ddd; background-color: #fff; color: #3c8a55; text-decoration: none; border-radius: 6px; font-weight: bold; transition: all 0.3s; }
        .pagination li a:hover { background-color: #e8f5e9; border-color: #3c8a55; }
        .pagination li.active a { background-color: #3c8a55; color: white; border-color: #3c8a55; }
        .pagination li.disabled a { color: #ccc; cursor: not-allowed; border-color: #eee; background-color: #fafafa; }
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
            <p class="menu-title">Kasir</p>
            <ul>
                <li class="active"><i class="fas fa-home"></i> Dashboard</li>
                <a href="Pesanan.php" style="text-decoration:none; color:inherit;">
                <li><i class="fas fa-bell"></i> Pesanan</li></a>
                <a href="Produk.php" style="text-decoration:none; color:inherit;">
                <li><i class="fas fa-box-open"></i> Produk</li></a>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?php echo $admin['name'] ?? 'Kasir'; ?></span>
            </div>
            <a href="../Controller/Logout.php" style="text-decoration: none;">
            <button class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button></a>
        </div>
    </aside>

    <main class="main-content">
        <header>
            <h1>Dashboard & Penjualan (Kasir)</h1>
        </header>

        <section class="cards-container">
            <div class="card card-income">
                <div class="card-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div class="card-info">
                    <p>Total Pendapatan (4 Minggu)</p>
                    <h3>Rp <?= number_format($total_pendapatan, 0, ',', '.'); ?></h3>
                    <small><?= $tgl_4_minggu_lalu; ?> - <?= $tgl_sekarang; ?></small>
                </div>
            </div>

            <div class="card card-transaction">
                <div class="card-icon"><i class="fas fa-receipt"></i></div>
                <div class="card-info">
                    <p>Total Transaksi (Selesai)</p>
                    <h3><?= $total_transaksi; ?> Transaksi</h3>
                    <small>Keseluruhan waktu</small>
                </div>
            </div>

            <div class="card card-stock">
                <div class="card-icon"><i class="fas fa-box-open"></i></div>
                <div class="card-info">
                    <p>Peringatan Stok Tipis</p>
                    <h3><?= $total_stok_tipis; ?> Produk</h3>
                    <small>Sisa stok 10 atau kurang</small>
                </div>
            </div>
        </section>

        <section class="analytics-grid" style="margin-bottom: 30px;">
            <div class="content-box table-section">
                <div class="box-header">
                    <h3>Produk Terlaris (4 Minggu)</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($q_terlaris && $q_terlaris->num_rows > 0) {
                            while($produk = $q_terlaris->fetch_assoc()): 
                        ?>
                            <tr>
                                <td style="font-weight: bold;"><?= htmlspecialchars($produk['nama_produk']); ?></td>
                                <td><?= htmlspecialchars($produk['kategori'] ?? 'Lainnya'); ?></td>
                                <td class="text-green" style="font-weight: bold; color: #3c8a55;">Terjual <?= $produk['total_terjual']; ?></td>
                            </tr>
                        <?php 
                            endwhile;
                        } else {
                            echo "<tr><td colspan='3' style='text-align:center;'>Belum ada data penjualan selesai bulan ini.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="content-box chart-section">
                <div class="box-header">
                    <h3>Statistik Penjualan (4 Minggu)</h3>
                </div>
                <div class="chart-js-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </section>

        <section class="sales-data-section content-box">
             <div class="box-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3>Riwayat Transaksi (Selesai & Batal)</h3>
                <button class="btn-aksi btn-green-tambah" onclick="bukaModal('modalKasirOffline')">
                    <i class="fas fa-plus-circle"></i> Tambah Penjualan (Offline)
                </button>
            </div>

            <table style="width: 100%; text-align: left; margin-top: 15px;">
                <thead>
                    <tr>
                        <th>KODE PESANAN</th>
                        <th>WAKTU PESAN</th>
                        <th>NAMA PEMBELI</th>
                        <th>METODE PEMBAYARAN</th>
                        <th>TOTAL</th>
                        <th>DETAIL</th>
                        <th>STATUS</th>
                    </tr>
                </thead>

                <tbody>
                <?php 
                if ($result && $result->num_rows > 0): 
                    while($row = $result->fetch_assoc()): 
                        // Logika Tampilan Metode Pembayaran
                        $metode_tampil = "";
                        if ($row['metode_pembayaran'] === 'QRIS') {
                            $metode_tampil = "<span style='color:#3b82f6; font-weight:bold;'><i class='fas fa-mobile-alt'></i> QRIS (Online)</span>";
                        } elseif ($row['metode_pembayaran'] === 'Tunai') {
                            $metode_tampil = "<span style='color:#e67e22; font-weight:bold;'><i class='fas fa-money-bill'></i> Tunai (Offline)</span>";
                        } else {
                            $metode_tampil = htmlspecialchars($row['metode_pembayaran']);
                        }
                ?>
                    <tr>
                        <td style="font-weight: bold; color: #3c8a55;"><?= htmlspecialchars($row['kode_pesanan']); ?></td>
                        <td><?= date('d-m-Y H:i', strtotime($row['waktu_pesan'])); ?></td>
                        <td><?= htmlspecialchars($row['nama_pembeli'] ?? 'Pembeli Offline'); ?></td>
                        <td><?= $metode_tampil; ?></td>
                        <td style="font-weight: bold;">Rp <?= number_format($row['total_harga'], 0, ',', '.'); ?></td>
                        <td>
                            <button class="btn-aksi btn-abu" onclick="bukaModal('modal-<?= htmlspecialchars($row['kode_pesanan']); ?>')"><i class="fas fa-list"></i> Lihat</button>
                            
                            <div id="modal-<?= htmlspecialchars($row['kode_pesanan']); ?>" class="modal" style="white-space: normal; text-align: left;">
                                <div class="modal-content">
                                    <span class="close-btn" onclick="tutupModal('modal-<?= htmlspecialchars($row['kode_pesanan']); ?>')">&times;</span>
                                    <h2 style="color: #3c8a55; margin-bottom: 10px;">Detail Riwayat Transaksi</h2>
                                    
                                    <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                                        <p><strong>Kode:</strong> <?= htmlspecialchars($row['kode_pesanan']); ?></p>
                                        <p><strong>Pemesan:</strong> <?= htmlspecialchars($row['nama_pembeli'] ?? 'Pembeli Offline'); ?> <?= !empty($row['no_hp']) ? '('.htmlspecialchars($row['no_hp']).')' : ''; ?></p>
                                        <p><strong>Metode Pembayaran:</strong> <?= strip_tags($metode_tampil); ?></p>
                                        
                                        <?php if(!empty($row['tanggal_pengambilan']) && $row['tanggal_pengambilan'] != '0000-00-00'): ?>
                                            <p><strong>Tgl Transaksi Selesai:</strong> <span style="color: red; font-weight: bold;"><?= date('d-m-Y', strtotime($row['tanggal_pengambilan'])); ?></span></p>
                                        <?php endif; ?>
                                        
                                        <?php if(!empty($row['alamat'])): ?>
                                            <p><strong>Alamat:</strong> <?= htmlspecialchars($row['alamat']); ?></p>
                                        <?php endif; ?>
                                        
                                        <?php if($row['metode_pembayaran'] === 'QRIS' && !empty($row['bukti_pembayaran'])): ?>
                                            <p style="margin-top:10px;"><a href="../Beranda/uploads/<?= htmlspecialchars($row['bukti_pembayaran']); ?>" target="_blank" style="color:#3b82f6; text-decoration:underline; font-weight:bold;">Lihat Foto Bukti Transfer QRIS</a></p>
                                        <?php endif; ?>
                                    </div>

                                    <h4>Daftar Barang:</h4>
                                    <table class="detail-table">
                                        <tr>
                                            <th>Produk</th>
                                            <th>Qty</th>
                                            <th>Subtotal</th>
                                        </tr>
                                        <?php
                                        $kdp = $row['kode_pesanan'];
                                        $q_detail = $conn->query("SELECT * FROM detail_pesanan WHERE kode_pesanan = '$kdp'");
                                        if($q_detail) {
                                            while($d = $q_detail->fetch_assoc()){
                                                echo "<tr>
                                                        <td>" . htmlspecialchars($d['nama_produk']) . "</td>
                                                        <td>{$d['qty']}x</td>
                                                        <td style='font-weight:bold;'>Rp " . number_format($d['subtotal'], 0, ',', '.') . "</td>
                                                      </tr>";
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td colspan="2" style="text-align: right; font-weight: bold;">Total Keseluruhan:</td>
                                            <td style="font-weight: bold; color: #3c8a55; font-size: 1.1rem;">Rp <?= number_format($row['total_harga'], 0, ',', '.'); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="status <?= strtolower($row['status']); ?>">
                                <?= htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                    </tr>
                <?php 
                    endwhile; 
                else: 
                ?>
                    <tr><td colspan='7' style='text-align:center; padding: 20px;'>Belum ada riwayat transaksi.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total_halaman > 1): ?>
            <ul class="pagination">
                <li class="<?= ($halaman <= 1) ? 'disabled' : ''; ?>">
                    <a href="<?= ($halaman <= 1) ? '#' : '?halaman=' . ($halaman - 1); ?>">&laquo; Prev</a>
                </li>

                <?php
                // Tampilkan maksimal 5 angka berdekatan agar tidak terlalu panjang
                $start_number = ($halaman > 2) ? $halaman - 2 : 1;
                $end_number = ($halaman < ($total_halaman - 2)) ? $halaman + 2 : $total_halaman;
                
                if ($start_number > 1) {
                    echo '<li><a href="?halaman=1">1</a></li>';
                    if ($start_number > 2) echo '<li class="disabled"><a href="#">...</a></li>';
                }

                for ($i = $start_number; $i <= $end_number; $i++) {
                    $active = ($halaman == $i) ? 'active' : '';
                    echo "<li class='$active'><a href='?halaman=$i'>$i</a></li>";
                }

                if ($end_number < $total_halaman) {
                    if ($end_number < $total_halaman - 1) echo '<li class="disabled"><a href="#">...</a></li>';
                    echo "<li><a href='?halaman=$total_halaman'>$total_halaman</a></li>";
                }
                ?>

                <li class="<?= ($halaman >= $total_halaman) ? 'disabled' : ''; ?>">
                    <a href="<?= ($halaman >= $total_halaman) ? '#' : '?halaman=' . ($halaman + 1); ?>">Next &raquo;</a>
                </li>
            </ul>
            <?php endif; ?>
            </section>
    </main>
</div>

<div id="modalKasirOffline" class="modal">
    <div class="modal-content form-kasir">
        <span class="close-btn" onclick="tutupModal('modalKasirOffline')">&times;</span>
        <h2 style="color: #3c8a55; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 15px;">
            <i class="fas fa-cash-register"></i> Kasir Pembelian Tunai (Offline)
        </h2>

        <form action="../Controller/Prose-Tambah-Penjualan.php" method="POST">
            <label>Nama Pelanggan (Opsional)</label>
            <input type="text" name="nama_pembeli" placeholder="Masukkan nama pembeli jika ada...">

            <label>No HP Pelanggan (Opsional)</label>
            <input type="number" name="no_hp" placeholder="Contoh: 08123456789">

            <hr style="margin: 20px 0; border: 1px dashed #ddd;">
            <h4 style="margin-bottom: 10px; color:#333;">Daftar Barang yang Dibeli:</h4>
            
            <div id="area-barang-kasir">
                <div class="item-kasir-baris">
                    <div style="flex-grow: 1;">
                        <label style="margin-top:0;">Pilih Produk</label>
                        <select name="nama_produk[]" required>
                            <option value="">-- Pilih Produk --</option>
                            <?= $opsi_produk; ?>
                        </select>
                    </div>
                    <div style="width: 100px;">
                        <label style="margin-top:0;">Jumlah</label>
                        <input type="number" name="qty[]" min="1" value="1" required>
                    </div>
                </div>
            </div>

            <button type="button" class="btn-tambah-item" onclick="tambahBarisProduk()">
                <i class="fas fa-plus"></i> Tambah Produk Lain
            </button>

            <div style="background: #e8f5e9; padding: 15px; border-radius: 8px; margin-top: 20px; text-align: right;">
                <span style="font-weight: bold; color: #555;">Pembayaran:</span> 
                <span style="font-weight: bold; color: #3c8a55; font-size: 1.2rem;">TUNAI (OFFLINE)</span>
            </div>

            <button type="submit" class="btn-submit-kasir">PROSES TRANSAKSI SELESAI</button>
        </form>
    </div>
</div>
<script>
    // Fungsi Buka Tutup Modal
    function bukaModal(modalId) {
        document.getElementById(modalId).style.display = "block";
    }
    function tutupModal(modalId) {
        document.getElementById(modalId).style.display = "none";
    }
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = "none";
        }
    }

    // Fungsi JavaScript Menambah Baris Form Produk Kasir
    function tambahBarisProduk() {
        var area = document.getElementById('area-barang-kasir');
        var barisBaru = document.createElement('div');
        barisBaru.className = 'item-kasir-baris';
        barisBaru.innerHTML = `
            <div style="flex-grow: 1;">
                <select name="nama_produk[]" required>
                    <option value="">-- Pilih Produk --</option>
                    <?= str_replace(["\r", "\n"], '', $opsi_produk); ?>
                </select>
            </div>
            <div style="width: 100px;">
                <input type="number" name="qty[]" min="1" value="1" required>
            </div>
            <button type="button" onclick="this.parentElement.remove()" style="background:#e74c3c; color:white; border:none; padding:10px; border-radius:6px; cursor:pointer;"><i class="fas fa-times"></i></button>
        `;
        area.appendChild(barisBaru);
    }
</script>

<script>
// Logika Chart.js Terhubung ke PHP
const ctx = document.getElementById('salesChart');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels); ?>,
        datasets: [{
            label: 'Jumlah Barang Fisik Terjual',
            data: <?= json_encode($chart_data); ?>,
            backgroundColor: ['#2ecc71', '#f1c40f', '#e74c3c', '#3498db'],
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>

</body>
</html>