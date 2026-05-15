<?php
// Tiga baris ini untuk menampilkan pesan error di layar (berguna saat modifikasi)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../Controller/Database.php';
require_once '../Controller/Auth.php';
require_once '../Controller/Session.php';

// Kita panggil Auth dan Database di urutan paling atas agar session_start() langsung berjalan
$db = new Database();
$conn = $db->getConnection(); 
$auth = new Auth($conn);      
$session = new Session();

// 1. Pengecekan jika user sudah login sebelumnya
$userData = $session->get('user');
if ($userData) {
    $role = $_SESSION['user']['role'] ?? ''; 
    
    // Gunakan strtolower agar "Admin", "ADMIN", atau "admin" tetap cocok
    if (strtolower($role) === 'admin') {
        header("Location: A2-Dashboard.php");
        exit;
    } else if (strtolower($role) === 'kasir') {
        header("Location: ../Kasir/Dashboard-Kasir.php"); 
        exit;
    }
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // 2. Pengecekan setelah tombol login ditekan
    if ($auth->login($email, $password)) {
        $role = $_SESSION['user']['role'] ?? ''; 
        
        if (strtolower($role) === 'admin') {
            header("Location: A2-Dashboard.php");
            exit;
        } else if (strtolower($role) === 'kasir') {
            header("Location: ../Kasir/DashboardKasir.php"); 
            exit;
        }
    } else {
        $error = $auth->error; 
    }
}
?>

<!-- Sisa kode HTML form log in milikmu taruh di bawah sini -->
<!-- Sisa kode HTML di bawahnya tetap sama -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title> Login </title>
    <link rel="stylesheet" href="../Style/log-in.css">
    <link rel="icon" href="../WebPictures/LOGO KOPMA.png" type="image/png">
</head>
<body>

    <div class="header">
        <img src="../WebPictures/LOGO KOPMA.png" alt="Logo" class="logo">
        <h1>KOPMA MART</h1>
        <p>POLITEKNIK NEGERI JEMBER</p>
    </div>

    <div class="login-card">
        <img src="../WebPictures/LOGO KOPMA.png" alt="Logo" class="logo-card">
        <h3>KOPMA BERDIKARI</h3>
        <p class="sub-text">POLITEKNIK NEGERI JEMBER</p>
        <h4>Login</h4>

        <?php if ($error): ?>
            <p style="color: red; font-size: 13px; margin-bottom: 10px;"><?php echo $error; ?></p>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="input-group">
                <span>👤</span>
                <input type="email" name="email" placeholder="Email" required>
            </div>

            <div class="input-group">
                <span>🔒</span>
                <input type="password" name="password" placeholder="Password" required>
            </div>

            <button type="submit">Login</button>
            <a href="../Beranda/Katalog-Beranda.php" class="link-batal">&#8592; Kembali ke Katalog</a>
        </form>
    </div>

</body>
</html>