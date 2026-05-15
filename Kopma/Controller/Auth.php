<?php
class Auth {
    private $conn;
    public $error = "";

    public function __construct($db) {
        $this->conn = $db;

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login($email, $password) {
        $email = trim($email);
        $password = trim($password);

        if (empty($email) || empty($password)) {
            $this->error = "Email dan password wajib diisi";
            return false;
        }

        // 1. UBAH 'name' menjadi 'nama' agar sesuai dengan tabel database yang baru
        $stmt = $this->conn->prepare(
            "SELECT id, nama, email, password, role FROM users WHERE email=?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // 2. 🔥 UBAH KEMBALI KE PENGECEKAN HASH
            // password_verify akan mengecek apakah password yang diketik cocok dengan password acak di database
            if (password_verify($password, $user['password'])) {

                session_regenerate_id(true);

                // Simpan data ke session
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'name' => $user['nama'], // Kita tetap simpan sebagai 'name' di session agar sidebar lamamu tidak error
                    'role' => $user['role']
                ];

                return true;
            }
        }

        $this->error = "Email atau password salah";
        return false;
    }
}
?>