<?php
require_once '../Controller/Session.php';
require_once '../Controller/Database.php';

$session = new Session();
$session->checkLogin(); // Proteksi halaman
$session->checkRole('Admin');
$admin_aktif = $session->get('user'); // Ambil data admin yang sedang login

$db = new Database();
$conn = $db->getConnection();

// ==========================================
// LOGIKA TAMBAH AKUN
// ==========================================
if (isset($_POST['tambah_akun'])) {
    $nama     = $_POST['nama'];
    $email    = $_POST['email'];
    $role     = $_POST['role'];
    
    // Enkripsi password agar aman dan tidak bisa dibaca di database
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 

    // Cek apakah email sudah terdaftar
    $cek_email = mysqli_query($conn, "SELECT email FROM users WHERE email = '$email'");
    
    if (mysqli_num_rows($cek_email) > 0) {
        $pesan_error = "Gagal: Email sudah digunakan!";
    } else {
        $sql = "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $nama, $email, $password, $role);
        
        if ($stmt->execute()) {
            $pesan_sukses = "Akun berhasil ditambahkan!";
        } else {
            $pesan_error = "Gagal menambahkan akun.";
        }
    }
}

// ==========================================
// LOGIKA HAPUS AKUN
// ==========================================
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    // Cegah admin menghapus akunnya sendiri yang sedang dipakai
    if ($id_hapus != $admin_aktif['id']) { 
        $sql_hapus = "DELETE FROM users WHERE id = ?";
        $stmt_hapus = $conn->prepare($sql_hapus);
        $stmt_hapus->bind_param("i", $id_hapus);
        $stmt_hapus->execute();
        header("Location: A6-Manajemen-Akun.php"); // Refresh halaman
        exit;
    } else {
        $pesan_error = "Kamu tidak bisa menghapus akun yang sedang kamu gunakan!";
    }
}

// Ambil semua data akun untuk ditampilkan di tabel
$query_users = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");
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
        .form-container { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; color: #555; margin-bottom: 8px; font-size: 13px; }
        .form-group input, .form-group select { width: 100%; padding: 10px 15px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        .form-row { display: flex; gap: 20px; }
        .form-row .form-group { flex: 1; }
        .btn-simpan { background: #2d5a3a; color: white; border: none; padding: 12px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 10px; }
        .btn-simpan:hover { background: #1e3f28; }
        
        .alert-sukses { background: #d4edda; color: #155724; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
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
                
                <a href="A3-Produk.php" style="text-decoration:none; color:inherit;">
                <li><i class="fas fa-book"></i> Produk</li></a>
                
                <a href="A4-Data-Barang.php" style="text-decoration:none; color:inherit;">
                <li><i class="fas fa-chart-line"></i> Laporan</li></a>
                
                <li class="active"><i class="fas fa-users-cog"></i> Manajemen</li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?php echo $admin_aktif['name'] ?? 'Admin'; ?></span>
            </div>
            <a href="../Controller/logout.php" style="text-decoration: none;">
                <button class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</button>
            </a>
        </div>
    </aside>

    <main class="main-content">
        <header>
            <h1>Manajemen Akun</h1>
            <p style="color: #666; margin-top: -20px; font-size: 0.9rem;">Buat dan kelola akses untuk Admin dan Kasir KOPMA MART.</p>
        </header>

        <section style="margin-top: 20px;">
            
            <?php if(isset($pesan_sukses)) echo "<div class='alert-sukses'>$pesan_sukses</div>"; ?>
            <?php if(isset($pesan_error)) echo "<div class='alert-error'>$pesan_error</div>"; ?>

            <div class="form-container">
                <h3 style="margin-top: 0; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Buat Akun Baru</h3>
                <form action="A6-Manajemen-Akun.php" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nama :</label>
                            <input type="text" name="nama" placeholder="Masukkan nama lengkap" required>
                        </div>
                        <div class="form-group">
                            <label>Role / Posisi :</label>
                            <select name="role" required>
                                <option value="Kasir">Kasir</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email :</label>
                            <input type="email" name="email" placeholder="Email" required>
                        </div>
                        <div class="form-group">
                            <label>Password :</label>
                            <input type="password" name="password" placeholder="Password" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="tambah_akun" class="btn-simpan"><i class="fas fa-user-plus"></i> Buat Akun</button>
                </form>
            </div>

            <div class="content-box table-section">
                <div class="box-header" style="margin-bottom: 15px;">
                    <h3>Daftar Akun Terdaftar</h3>
                </div>

                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid #eee;">
                            <th style="padding: 10px;">NO</th>
                            <th>NAMA</th>
                            <th>EMAIL</th>
                            <th>ROLE</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while($row = mysqli_fetch_assoc($query_users)) { 
                        ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 10px;"><?php echo $no++; ?></td>
                            <td style="font-weight: 600;"><?php echo $row['nama']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td>
                                <span style="background: <?php echo ($row['role'] == 'Admin') ? '#e1f5fe' : '#fff3cd'; ?>; color: <?php echo ($row['role'] == 'Admin') ? '#0288d1' : '#856404'; ?>; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold;">
                                    <?php echo $row['role']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="A6-Manajemen-Akun.php?hapus=<?php echo $row['id']; ?>" onclick="return confirm('Yakin ingin menghapus akun <?php echo $row['nama']; ?>?');">
                                    <button style="background: #e74c3c; border: none; padding: 6px 10px; border-radius: 5px; cursor: pointer; color: white;"><i class="fas fa-trash"></i> Hapus</button>
                                </a>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

        </section>
    </main>
</div>

</body>
</html>