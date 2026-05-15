<?php
// --- TAMBAHAN PENTING: Atur zona waktu ke WIB (Waktu Indonesia Barat) ---
date_default_timezone_set('Asia/Jakarta');

require_once 'Session.php';
require_once 'Database.php';

$session = new Session();
$session->checkLogin();

// Cek apakah data dikirim melalui tombol submit (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $conn = $db->getConnection();

    // 1. Tangkap Data Identitas Pembeli
    // Jika kasir mengosongkan nama, otomatis diisi 'Pembeli Offline'
    $nama_pembeli = !empty($_POST['nama_pembeli']) ? mysqli_real_escape_string($conn, $_POST['nama_pembeli']) : 'Pembeli Offline';
    $no_hp = !empty($_POST['no_hp']) ? mysqli_real_escape_string($conn, $_POST['no_hp']) : '-';
    
    // 2. Siapkan Data Transaksi
    // Buat Kode Pesanan Unik (Contoh: KOP-OFF-260507143000)
    $kode_pesanan = 'KOP-OFF-' . date('ymdHis');
    $metode_pembayaran = 'Tunai'; 
    $status = 'Selesai'; // Karena offline/bayar di tempat, otomatis selesai
    $waktu_pesan = date('Y-m-d H:i:s'); // Sekarang sudah otomatis mengikuti jam WIB
    $tanggal_pengambilan = date('Y-m-d');
    $alamat = 'Pembelian Langsung di Kopma';
    
    $total_harga_semua = 0;
    $pesanan_berhasil = false;

    // Tangkap array produk dan qty dari form dinamis
    $produk_array = $_POST['nama_produk'] ?? [];
    $qty_array = $_POST['qty'] ?? [];

    if (!empty($produk_array)) {
        // 3. Insert dulu data utama pesanan (Total harga dikosongkan dulu/0)
        $q_pesanan = "INSERT INTO pesanan (kode_pesanan, nama_pembeli, no_hp, alamat, metode_pembayaran, status, waktu_pesan, tanggal_pengambilan, total_harga) 
                      VALUES ('$kode_pesanan', '$nama_pembeli', '$no_hp', '$alamat', '$metode_pembayaran', '$status', '$waktu_pesan', '$tanggal_pengambilan', 0)";
        
        if (mysqli_query($conn, $q_pesanan)) {
            
            // 4. Lakukan perulangan untuk setiap barang yang di-input kasir
            for ($i = 0; $i < count($produk_array); $i++) {
                $nama_produk = mysqli_real_escape_string($conn, $produk_array[$i]);
                $qty = (int)$qty_array[$i];

                if (!empty($nama_produk) && $qty > 0) {
                    // Ambil harga satuan langsung dari database agar aman dan akurat
                    $q_harga = mysqli_query($conn, "SELECT harga FROM produk2 WHERE nama_produk = '$nama_produk'");
                    if ($row_harga = mysqli_fetch_assoc($q_harga)) {
                        $harga_satuan = $row_harga['harga'];
                        $subtotal = $harga_satuan * $qty;
                        
                        // Tambahkan subtotal ini ke grand total keseluruhan
                        $total_harga_semua += $subtotal;

                        // Insert ke tabel keranjang (detail_pesanan)
                        mysqli_query($conn, "INSERT INTO detail_pesanan (kode_pesanan, nama_produk, qty, harga, subtotal) 
                                             VALUES ('$kode_pesanan', '$nama_produk', $qty, $harga_satuan, $subtotal)");

                        // OTOMATIS MEMOTONG STOK BARANG DI KATALOG
                        mysqli_query($conn, "UPDATE produk2 SET stok = stok - $qty WHERE nama_produk = '$nama_produk'");
                    }
                }
            }

            // 5. Setelah semua barang dihitung, Update Total Harga sebenarnya ke tabel pesanan
            mysqli_query($conn, "UPDATE pesanan SET total_harga = $total_harga_semua WHERE kode_pesanan = '$kode_pesanan'");
            
            $pesanan_berhasil = true;
        }
    }

    // 6. Tampilkan Pesan Sukses / Gagal
    if ($pesanan_berhasil) {
        echo "<script>
                alert('Transaksi Penjualan Offline Berhasil!\\nTotal: Rp " . number_format($total_harga_semua, 0, ',', '.') . "'); 
                window.location.href='../Kasir/DashboardKasir.php';
              </script>";
    } else {
        echo "<script>alert('Gagal memproses transaksi!'); window.location.href='../Kasir/DashboardKasir.php';</script>";
    }
} else {
    // Jika ada yang mencoba mengakses file ini secara langsung via URL
    header("Location: ../Kasir/DashboardKasir.php");
}
?>