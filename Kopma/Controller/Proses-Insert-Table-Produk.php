<?php
require_once 'Database.php';
require_once 'Session.php';

$session = new Session();
$session->checkLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = new Database();
    $conn = $db->getConnection();

    // 1. Ambil data dari form
    // Ambil id_produk (kosong jika tambah baru, ada isinya jika edit)
    $id_produk = $_POST['id_produk'] ?? ''; 
    $nama      = $_POST['nama'];
    $kategori  = $_POST['kategori'];
    $harga     = $_POST['harga'];
    $stok      = $_POST['stok'];
    
    // --- CEK APAKAH INI PROSES EDIT ATAU TAMBAH BARU ---
    if (!empty($id_produk)) {
        
        // --- PROSES EDIT DATA ---
        // Jika kasir juga mengupload gambar baru saat edit
        if (!empty($_FILES['gambar']['name'])) {
            $nama_file = $_FILES['gambar']['name'];
            $tmp_name  = $_FILES['gambar']['tmp_name'];
            $nama_gambar_baru = time() . "_" . $nama_file;
            $path_simpan = "assets/" . $nama_gambar_baru;
            
            if (move_uploaded_file($tmp_name, $path_simpan)) {
                // Query UPDATE lengkap dengan gambar
                $update_sql = "UPDATE produk2 SET nama_produk = ?, kategori = ?, harga = ?, stok = ?, gambar = ? WHERE id = ?";
                $stmt_update = $conn->prepare($update_sql);
                // Bind parameter sesuaikan dengan urutan tanda tanya (?)
                $stmt_update->bind_param("ssiisi", $nama, $kategori, $harga, $stok, $nama_gambar_baru, $id_produk);
            }
        } else {
            // Jika kasir TIDAK upload gambar baru (Hanya edit teks/stok)
            // Stok langsung ditimpa angka baru, BUKAN ditambah
            $update_sql = "UPDATE produk2 SET nama_produk = ?, kategori = ?, harga = ?, stok = ? WHERE id = ?";
            $stmt_update = $conn->prepare($update_sql);
            $stmt_update->bind_param("ssiii", $nama, $kategori, $harga, $stok, $id_produk);
        }

        // Eksekusi Update
        if (isset($stmt_update) && $stmt_update->execute()) {
            // Kembali ke halaman A3-Produk.php (Pastikan nama filenya sesuai dengan punyamu)
            header("Location: ../Admin/A3-Produk.php?status=sukses_edit");
        } else {
            echo "Gagal mengupdate data produk.";
        }

    } else {
        
        // --- PROSES TAMBAH PRODUK BARU ---
        $nama_file = $_FILES['gambar']['name'];
        $tmp_name  = $_FILES['gambar']['tmp_name'];
        
        if (!empty($nama_file)) {
            // Beri nama unik pada gambar agar tidak bentrok
            $nama_gambar_baru = time() . "_" . $nama_file;
            $path_simpan = "assets/" . $nama_gambar_baru;

            // Pindahkan file gambar ke folder assets
            if (move_uploaded_file($tmp_name, $path_simpan)) {
                
                // Query Insert (Kolom 'terjual' sudah dihilangkan)
                $sql = "INSERT INTO produk2 (nama_produk, kategori, harga, stok, gambar) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssiis", $nama, $kategori, $harga, $stok, $nama_gambar_baru);

                if ($stmt->execute()) {
                    header("Location: ../Admin/A3-Produk.php?status=produk_baru_ditambah");
                } else {
                    echo "Gagal menyimpan data produk baru.";
                }
            } else {
                echo "Gagal mengupload gambar. Pastikan folder 'assets' sudah ada.";
            }
        } else {
            echo "Gambar produk wajib diupload untuk produk baru!";
        }
    }
}
?>