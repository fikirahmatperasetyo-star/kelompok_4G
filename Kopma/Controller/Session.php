<?php
class Session {

    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function get($key) {
        return $_SESSION[$key] ?? null;
    }

    public function checkLogin() {
        if (!isset($_SESSION['user'])) {
            // Menggunakan JavaScript redirect agar aman dipanggil dari folder mana saja (Admin/Kasir)
            echo "<script>
                    alert('Silakan login terlebih dahulu!');
                    window.location.href = '/Kopma/Kopma/Admin/A1-log-in.php'; 
                  </script>";
            exit;
        }
    }

    public function checkRole($role) {
        // Pastikan login dulu sebelum cek role
        $this->checkLogin(); 

        if ($_SESSION['user']['role'] !== $role) {
            // Jika Kasir mencoba masuk Admin (atau sebaliknya), tendang kembali ke halamannya
            $role_sekarang = $_SESSION['user']['role'];
            echo "<script>
                    alert('Akses Ditolak! Halaman ini hanya untuk $role. Anda login sebagai $role_sekarang.');
                    window.history.back(); // Kembalikan ke halaman sebelumnya
                  </script>";
            exit;
        }
    }
    
    // Opsional: Fungsi untuk logout yang bersih
    public function destroy() {
        session_unset();
        session_destroy();
    }
}
?>