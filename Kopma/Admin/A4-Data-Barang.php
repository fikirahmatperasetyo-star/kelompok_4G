<?php
require_once '../Controller/Session.php';
require_once '../Controller/Database.php';
$session = new Session();
$session->checkLogin();
$session->checkRole('Admin');

$userData = $session->get('user'); 

$db = new Database();
$conn = $db->getConnection();

// --- LOGIKA MENGHITUNG 6 PERIODE MUNDUR & RINCIAN BARANG ---
$laporan_data = [];
$total_keseluruhan_omset = 0;
$total_keseluruhan_barang = 0;
$total_keseluruhan_transaksi = 0;

for ($i = 0; $i < 6; $i++) {
    $offset_start = ($i * 4) + 4; 
    $offset_end = $i * 4;         

    $end_date = date('Y-m-d 23:59:59', strtotime("-{$offset_end} weeks"));
    $start_date = date('Y-m-d 00:00:00', strtotime("-{$offset_start} weeks"));
    $label_tanggal = date('d M Y', strtotime($start_date)) . " s/d " . date('d M Y', strtotime($end_date));

    // 1. Ambil data Omset dan Transaksi (HANYA STATUS SELESAI)
    $q_pesanan = $conn->query("SELECT COUNT(kode_pesanan) as jml_trx, SUM(total_harga) as omset FROM pesanan WHERE status='Selesai' AND waktu_pesan BETWEEN '$start_date' AND '$end_date'");
    $dt_pesanan = $q_pesanan->fetch_assoc();
    $omset = $dt_pesanan['omset'] ? $dt_pesanan['omset'] : 0;
    $transaksi = $dt_pesanan['jml_trx'] ? $dt_pesanan['jml_trx'] : 0;

    // 2. Ambil RINCIAN BARANG APA SAJA YANG TERJUAL PADA PERIODE INI
    $q_barang_detail = $conn->query("
        SELECT d.nama_produk, SUM(d.qty) as total_qty, SUM(d.subtotal) as total_subtotal 
        FROM detail_pesanan d 
        JOIN pesanan p ON d.kode_pesanan = p.kode_pesanan 
        WHERE p.status='Selesai' AND p.waktu_pesan BETWEEN '$start_date' AND '$end_date'
        GROUP BY d.nama_produk
        ORDER BY total_qty DESC
    ");
    
    $list_barang = [];
    $barang_terjual = 0;
    while ($brg = $q_barang_detail->fetch_assoc()) {
        $list_barang[] = $brg;
        $barang_terjual += $brg['total_qty']; 
    }

    // Simpan ke array
    $laporan_data[] = [
        'siklus_id' => $i + 1, // Hanya dipakai untuk ID sistem (tidak ditampilkan ke user)
        'periode' => $label_tanggal,
        'transaksi' => $transaksi,
        'omset' => $omset,
        'barang_terjual' => $barang_terjual,
        'detail_produk' => $list_barang 
    ];

    $total_keseluruhan_omset += $omset;
    $total_keseluruhan_transaksi += $transaksi;
    $total_keseluruhan_barang += $barang_terjual;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kopma Mart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../Style/Dashboard.css">
    <link rel="icon" href="../WebPictures/LOGO KOPMA.png" type="image/png">
    <style>
        .btn-print { background-color: #1976d2; color: white; padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 0.85rem;}
        .btn-print:hover { background-color: #1565c0; }
        
        /* Modal CSS */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fff; margin: 5% auto; padding: 25px; border-radius: 10px; width: 60%; max-width: 800px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); position: relative; max-height: 80vh; overflow-y: auto;}
        .close-btn { position: absolute; right: 20px; top: 15px; color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close-btn:hover { color: red; }
        .detail-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .detail-table th, .detail-table td { border-bottom: 1px solid #ddd; padding: 10px; text-align: left; }
        .detail-table th { background-color: #f4f7f6; color: #333; font-size: 0.9rem;}

        /* CSS KHUSUS PRINT PDF (Diperbaiki agar tidak bocor di background) */
        @media print {
            body { background: white; }
            /* Sembunyikan elemen web utama sepenuhnya */
            .container, .modal, .sidebar { display: none !important; } 
            
            /* Tampilkan HANYA area cetak */
            #print-container { display: block !important; width: 100%; }
            
            /* Penyesuaian garis tabel untuk PDF agar rapi */
            .detail-table { border: 1px solid #000; }
            .detail-table th, .detail-table td { border: 1px solid #000 !important; color: #000 !important; }
        }
    </style>
</head>
<body>

<div class="container">
    <aside class="sidebar">
        <div class="logo-section">
            <img src="LOGO KOPMA.png" alt="Logo Kopma">
            <h2>KOPMA MART</h2>
        </div>

        <nav class="menu">
            <p class="menu-title">Manajemen Kopma Mart</p>
            <ul>
                <a href="A2-Dashboard.php" style="text-decoration:none; color:inherit;">
                <li><i class="fas fa-home"></i> Dashboard</li></a>
                <a href="A3-Produk.php" style="text-decoration:none; color:inherit;">
                <li><i class="fas fa-book"></i> Produk</li></a>
                
                <li class="active"><i class="fas fa-chart-line"></i> Laporan</li>
                
                <a href="A6-Manajemen-Akun.php" style="text-decoration:none; color:inherit;">
                <li><i class="fas fa-users-cog"></i> Manajemen</li></a>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?php echo $userData['name'] ?? 'Admin Kopma'; ?></span>
            </div>
            <a href="../Controller/Logout.php" style="text-decoration: none;">
            <button class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</button></a>
        </div>
    </aside>

    <main class="main-content">
        <header>
            <h1>Laporan Keuangan & Penjualan</h1>
            <p style="color: #666; margin-top: -20px; font-size: 0.9rem;">Rekapitulasi omset bersih per 4 minggu (Total 24 Minggu Terakhir).</p>
        </header>

        <section class="cards-container" style="margin-top: 20px;">
            <div class="card card-income">
                <div class="card-icon"><i class="fas fa-wallet"></i></div>
                <div class="card-info">
                    <p>Omset Keseluruhan (24 Mgg)</p>
                    <h3>Rp <?php echo number_format($total_keseluruhan_omset, 0, ',', '.'); ?></h3>
                </div>
            </div>

            <div class="card card-stock">
                <div class="card-icon"><i class="fas fa-receipt"></i></div>
                <div class="card-info">
                    <p>Total Pesanan Selesai</p>
                    <h3><?php echo number_format($total_keseluruhan_transaksi, 0, ',', '.'); ?> Transaksi</h3>
                </div>
            </div>

            <div class="card card-transaction">
                <div class="card-icon"><i class="fas fa-shopping-cart"></i></div>
                <div class="card-info">
                    <p>Total Barang Terjual</p>
                    <h3><?php echo number_format($total_keseluruhan_barang, 0, ',', '.'); ?> Item</h3>
                </div>
            </div>
        </section>

        <!-- Tabel Laporan Utama -->
        <section class="content-box table-section" style="margin-top: 20px;">
            <div class="box-header">
                <h3>Rincian Laporan per 4 Minggu</h3>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>RENTANG WAKTU TANGGAL</th>
                        <th style="text-align: center;">JUMLAH TRANSAKSI</th>
                        <th style="text-align: center;">BARANG TERJUAL</th>
                        <th>TOTAL OMSET</th>
                        <th style="text-align: center;">AKSI DETAIL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // LOOP 1: KHUSUS UNTUK MEMBANGUN BARIS TABEL
                    foreach ($laporan_data as $laporan) {
                        $isNol = ($laporan['omset'] == 0);
                        $rowStyle = $isNol ? "style='color: #999;'" : "style='font-weight: 500;'";
                        $modal_id = "modal_laporan_" . $laporan['siklus_id'];
                    ?>
                    <tr <?php echo $rowStyle; ?>>
                        <td style="font-weight: bold;"><?php echo $laporan['periode']; ?></td>
                        <td style="text-align: center; color: var(--blue-accent);"><?php echo $laporan['transaksi']; ?> Pesanan</td>
                        <td style="text-align: center;"><?php echo $laporan['barang_terjual']; ?> Item</td>
                        <td style="color: var(--green-accent); font-weight: bold;">Rp <?php echo number_format($laporan['omset'], 0, ',', '.'); ?></td>
                        <td style="text-align: center;">
                            <button class="btn-print" style="background: #3c8a55;" onclick="bukaModal('<?php echo $modal_id; ?>')">
                                <i class="fas fa-list"></i> Detail & Cetak
                            </button>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </section>
    </main>
</div> 

<?php
// LOOP 2: KHUSUS UNTUK MEMBANGUN MODAL
foreach ($laporan_data as $laporan) {
    $modal_id = "modal_laporan_" . $laporan['siklus_id'];
?>
<div id="<?php echo $modal_id; ?>" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="tutupModal('<?php echo $modal_id; ?>')">&times;</span>
        
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 15px;">
            <div>
                <h2 style="color: #3c8a55; margin-bottom: 5px;">Laporan Penjualan</h2>
                <p style="color: #666; font-weight: bold;">Periode: <?php echo $laporan['periode']; ?></p>
            </div>
            <button class="btn-print" onclick="cetakPDF('print_area_<?php echo $laporan['siklus_id']; ?>')">
                <i class="fas fa-file-pdf"></i> Simpan ke PDF
            </button>
        </div>

        <div id="print_area_<?php echo $laporan['siklus_id']; ?>">
            <div style="display: none;" class="kop-surat">
                <h1 style="text-align: center; color: #3c8a55;">KOPMA MART BERDIKARI</h1>
                <h3 style="text-align: center;">Laporan Penjualan</h3>
                <p style="text-align: center; margin-bottom: 20px;">Periode: <?php echo $laporan['periode']; ?></p>
                <hr style="margin-bottom: 20px;">
            </div>

            <div style="display: flex; justify-content: space-between; margin-bottom: 15px; background: #f9f9f9; padding: 15px; border-radius: 8px;">
                <p><strong>Total Transaksi Selesai:</strong> <?php echo $laporan['transaksi']; ?> Pesanan</p>
                <p><strong>Total Barang Fisik Terjual:</strong> <?php echo $laporan['barang_terjual']; ?> Item</p>
                <p><strong>Omset Bersih:</strong> <span style="color: #3c8a55; font-weight: bold;">Rp <?php echo number_format($laporan['omset'], 0, ',', '.'); ?></span></p>
            </div>

            <h4>Daftar Rincian Barang Terjual:</h4>
            <table class="detail-table">
                <tr>
                    <th>Nama Produk</th>
                    <th style="text-align: center;">Total Kuantitas (Qty)</th>
                    <th>Subtotal Pendapatan</th>
                </tr>
                <?php 
                if(empty($laporan['detail_produk'])) {
                    echo "<tr><td colspan='3' style='text-align: center; font-style: italic; color: #999;'>Tidak ada barang terjual pada periode ini.</td></tr>";
                } else {
                    foreach ($laporan['detail_produk'] as $barang) { 
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($barang['nama_produk']); ?></td>
                        <td style="text-align: center; font-weight: bold;"><?php echo $barang['total_qty']; ?>x</td>
                        <td style="font-weight: bold; color: #333;">Rp <?php echo number_format($barang['total_subtotal'], 0, ',', '.'); ?></td>
                    </tr>
                <?php 
                    } 
                }
                ?>
            </table>
        </div>

    </div>
</div>
<?php } ?>

<!-- KONTOLER UNTUK PRINT -->
<div id="print-container" style="display: none;"></div>

<script>
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

    function cetakPDF(areaId) {
        var contentToPrint = document.getElementById(areaId).innerHTML;
        var printContainer = document.getElementById('print-container');
        
        // Pindahkan isi ke div cetak
        printContainer.innerHTML = contentToPrint;
        
        // Tampilkan kop surat
        var kopSurat = printContainer.querySelector('.kop-surat');
        if(kopSurat) kopSurat.style.display = 'block';

        // Lakukan Print (Browser akan menggunakan @media print CSS)
        window.print();
        
        // Bersihkan kembali setelah print dialog ditutup
        printContainer.innerHTML = '';
    }
</script>

</body>
</html>