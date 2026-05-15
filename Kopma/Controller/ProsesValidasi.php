<?php
session_start();
// Sesuaikan path ke file Database.php milik Admin
require_once 'Database.php'; 

// Cek apakah keranjang kosong atau akses bukan dari form POST
if (empty($_SESSION['keranjang']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../Kasir/ValidasiPemesanan.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// --- 1. AMBIL DATA DARI FORM ---
// htmlspecialchars digunakan untuk keamanan (mencegah XSS)
$nama     = htmlspecialchars($_POST['nama']);
$tanggal  = htmlspecialchars($_POST['tanggal']);
$waktu    = htmlspecialchars($_POST['waktu']);
$hp       = htmlspecialchars($_POST['hp']);
$alamat   = htmlspecialchars($_POST['alamat']);
$metode   = htmlspecialchars($_POST['metode']);

$tanggal_lengkap = $tanggal . " " . $waktu;
// Generate Kode Pesanan unik (Contoh: KOP-2605041234)
$kode_pesanan = "KOP-" . date('ymd') . rand(1000, 9999);

// --- 2. HITUNG TOTAL HARGA ---
$total_harga = 0;
foreach ($_SESSION['keranjang'] as $item) {
    $total_harga += ($item['harga'] * $item['qty']);
}

// --- 3. PROSES UPLOAD FOTO (JIKA METODE = QRIS) ---
$nama_file_bukti = NULL; // Default null jika tunai

if ($metode === 'QRIS' && isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['error'] === 0) {
    $direktori_upload = "../Beranda/uploads/";
    
    // Buat folder uploads jika belum ada
    if (!file_exists($direktori_upload)) {
        mkdir($direktori_upload, 0777, true);
    }

    $nama_file_asli = $_FILES['bukti_pembayaran']['name'];
    $ekstensi = pathinfo($nama_file_asli, PATHINFO_EXTENSION);
    
    // Beri nama file baru agar tidak bentrok (Contoh: KOP-1234_bukti.jpg)
    $nama_file_bukti = $kode_pesanan . "_bukti." . $ekstensi;
    $path_tujuan = $direktori_upload . $nama_file_bukti;

    // Pindahkan file dari tempat sementara ke folder uploads
    if (!move_uploaded_file($_FILES['bukti_pembayaran']['tmp_name'], $path_tujuan)) {
        die("Gagal mengunggah bukti pembayaran. Silakan coba lagi.");
    }
}

// --- 4. MULAI TRANSAKSI DATABASE ---
// Gunakan begin_transaction agar jika ada error di tengah jalan, database batal terisi
$conn->begin_transaction();

try {
    // A. Masukkan data ke tabel `pesanan`
    $stmt_pesanan = $conn->prepare("INSERT INTO pesanan (kode_pesanan, nama_pembeli, tanggal_pengambilan, no_hp, alamat, metode_pembayaran, bukti_pembayaran, total_harga, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
    
    // Huruf "sssssssd" artinya: string, string, string, string, string, string, string, double
    $stmt_pesanan->bind_param("sssssssd", $kode_pesanan, $nama, $tanggal_lengkap, $hp, $alamat, $metode, $nama_file_bukti, $total_harga);
    $stmt_pesanan->execute();

    // B. Masukkan data ke tabel `detail_pesanan` untuk setiap barang di keranjang
    $stmt_detail = $conn->prepare("INSERT INTO detail_pesanan (kode_pesanan, nama_produk, qty, harga, subtotal) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($_SESSION['keranjang'] as $nama_produk => $item) {
        $qty = $item['qty'];
        $harga = $item['harga'];
        $subtotal = $qty * $harga;
        
        $stmt_detail->bind_param("ssidd", $kode_pesanan, $nama_produk, $qty, $harga, $subtotal);
        $stmt_detail->execute();
    }

    // Jika semua perintah sukses, simpan secara permanen ke database
    $conn->commit();

    // Kosongkan keranjang setelah berhasil pesan
    unset($_SESSION['keranjang']);

    // Arahkan ke halaman sukses (atau bisa langsung kembali ke katalog)
    echo "<script>
            alert('Pesanan berhasil dibuat! Kode Pesanan Anda: $kode_pesanan. Silakan ambil di Kopma sesuai tanggal.');
            window.location.href = '../Beranda/Katalog-Beranda.php';
          </script>";

} catch (Exception $e) {
    // Jika ada error, batalkan semua perintah insert (rollback)
    $conn->rollback();
    die("Terjadi kesalahan sistem: " . $e->getMessage());
}
?>