<?php
require_once '../Controller/Session.php';
require_once '../Controller/Database.php';

$session = new Session();
$session->checkLogin(); // Proteksi halaman
$session->checkRole('Kasir');
$admin = $session->get('user'); // Ambil data admin/kasir untuk sidebar

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
            <p class="menu-title">Kasir</p>
            <ul>
                <a href="DashboardKasir.php" style="text-decoration:none; color:inherit;">
                <li><i class="fas fa-home"></i> Dashboard</li></a>
                
                <a href="Pesanan.php" style="text-decoration:none; color:inherit;">
                <li><i class="fas fa-bell"></i> Pesanan</li></a>
                
                <li class="active"><i class="fas fa-box-open"></i> Produk</li>
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
            <p style="color: #666; margin-top: -20px; font-size: 0.9rem;">Pantau ketersediaan stok barang Kopma Mart.</p>
        </header>

        <section class="content-box table-section" style="margin-top: 20px;">
            <div class="box-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3>Katalog Produk</h3>
            </div>

            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; border-bottom: 2px solid #eee;">
                        <th style="padding: 10px;">PRODUK</th>
                        <th>KATEGORI</th>
                        <th>HARGA</th>
                        <th>STOK</th>
                        <th>STATUS</th>
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
                            <!-- Sesuaikan path gambar jika folder assets ada di tempat lain -->
                            <img src="../Admin/assets/<?php echo $row['gambar']; ?>" width="40" style="border-radius: 5px; background: #f9f9f9; padding: 2px;">
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
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </section>
    </main>
</div>

</body>
</html>