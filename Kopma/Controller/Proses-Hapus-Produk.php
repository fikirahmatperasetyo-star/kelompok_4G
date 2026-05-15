<?php
require_once 'Session.php';
require_once 'Database.php';

$session = new Session();
$session->checkLogin();

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $db = new Database();
    $conn = $db->getConnection();

    // Opsional: Hapus gambar dari folder assets jika ada
    $q_gambar = mysqli_query($conn, "SELECT gambar FROM produk2 WHERE id = '$id'");
    if ($row = mysqli_fetch_assoc($q_gambar)) {
        $file_gambar = "assets/" . $row['gambar'];
        if (file_exists($file_gambar) && !empty($row['gambar'])) {
            unlink($file_gambar);
        }
    }

    // Hapus data dari database
    $query = "DELETE FROM produk2 WHERE id = '$id'";
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Produk berhasil dihapus!'); window.location.href='../Admin/A3-Produk.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus produk!'); window.location.href='../Admin/A3-Produk.php';</script>";
    }
} else {
    header("Location: ../Admin/A3-Produk.php");
}
?>