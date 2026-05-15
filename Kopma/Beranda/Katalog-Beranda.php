<?php
session_start();

// --- 1. KONEKSI KE DATABASE ---
require_once '../Controller/Database.php'; 
$db = new Database();
$conn = $db->getConnection();

// Mengambil semua data produk dari database
$query_produk = mysqli_query($conn, "SELECT * FROM produk2 ORDER BY id DESC");
// ------------------------------

if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

// --- LOGIKA TAMBAH BARANG MENGGUNAKAN AJAX ---
if (isset($_POST['ajax_tambah'])) {
    $nama_produk = $_POST['nama_produk'];
    $harga_produk = (int)$_POST['harga_produk'];
    $stok_produk = (int)$_POST['stok_produk']; // Tangkap data stok

    // Cek jumlah barang ini yang sudah ada di keranjang saat ini
    $jumlah_di_keranjang = isset($_SESSION['keranjang'][$nama_produk]) ? $_SESSION['keranjang'][$nama_produk]['qty'] : 0;

    // Cek apakah jumlah di keranjang masih di bawah stok asli
    if ($jumlah_di_keranjang < $stok_produk) {
        if (isset($_SESSION['keranjang'][$nama_produk])) {
            $_SESSION['keranjang'][$nama_produk]['qty'] += 1;
        } else {
            $_SESSION['keranjang'][$nama_produk] = [
                'harga' => $harga_produk,
                'qty' => 1
            ];
        }
        $status = 'success';
        $pesan = 'Berhasil ditambahkan.';
    } else {
        // Jika sudah melebihi stok, tolak penambahan
        $status = 'error';
        $pesan = 'Maaf, stok produk ini hanya tersisa ' . $stok_produk . '!';
    }

    // Hitung ulang total notifikasi keranjang
    $total_notifikasi = 0;
    foreach ($_SESSION['keranjang'] as $item) {
        $total_notifikasi += $item['qty'];
    }

    // Kirim respons balik ke Javascript
    echo json_encode(['status' => $status, 'pesan' => $pesan, 'total_cart' => $total_notifikasi]);
    exit; 
}

$total_notifikasi = 0;
foreach ($_SESSION['keranjang'] as $item) {
    $total_notifikasi += $item['qty'];
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kopma Mart</title>
    <link rel="icon" href="../WebPictures/LOGO KOPMA.png" type="image/png">
    <link rel="stylesheet" href="../Style/Katalog-Beranda.css">
    
</head>
<body>

    <div class="container">
        <section class="hero">
            <div class="hero-content">
                <div class="subtitle">KOPMA BERDIKARI</div>
                <h1>Belanja <span class="italic">Mudah,</span><br>Harga Ramah.</h1>
                <p class="desc">Semua kebutuhan harian mahasiswa - dari snack, minuman dan alat tulis hingga produk lokal, langsung dengan harga terjangkau</p>
                <div class="hero-buttons">
                    <button class="btn-belanja" onclick="filterKategori('semua')">Lihat Semua Produk &rarr;</button>

                    <a href="ValidasiPemesanan.php" class="link-pesanan" style="border: 1px solid white; padding: 10px 20px; border-radius: 25px;">
                        Lihat Pesanan Saya 
                        <span class="badge" id="cart-badge" style="display: <?php echo ($total_notifikasi > 0) ? 'inline-block' : 'none'; ?>;">
                            <?php echo $total_notifikasi; ?>
                        </span>
                    </a>

                    <a href="../Admin/A1-log-in.php" class="link-pesanan" style="border: 1px solid white; padding: 10px 20px; border-radius: 25px;">
                        Log-In
                    </a>
                </div>
            </div>
            <div class="hero-logo">
                <img src="../WebPictures/LOGO KOPMA.png" alt="Kopma Berdikari">
            </div>
        </section>

        <section class="categories">
            <div class="category-card cat-0" onclick="filterKategori('semua')">
                <h3>Semua</h3>
                <p>Seluruh produk Kopma Mart</p>
                <img src="../WebPictures/Semua.png" alt="Semua" class="category-img">
            </div>
            
            <div class="category-card cat-1" onclick="filterKategori('makanan')">
                <h3>Makanan</h3>
                <p>Rasa kecil yang dapat mewarnai hidupmu.</p>
                <img src="../WebPictures/Snack.png" alt="Makanan" class="category-img">
            </div>
            
            <div class="category-card cat-2" onclick="filterKategori('minuman')">
                <h3>Minuman</h3>
                <p>Minuman segar untuk menemani galau.</p>
                <img src="../WebPictures/Minuman.png" alt="Minuman" class="category-img">
            </div>
            
            <div class="category-card cat-3" onclick="filterKategori('atk')">
                <h3>ATK</h3>
                <p>Dari tinta & kertas, lahirlah gagasan yang tak terbatas.</p>
                <img src="../WebPictures/ATK.png" alt="ATK" class="category-img">
            </div>
            
            <div class="category-card cat-4" onclick="filterKategori('dapur')">
                <h3>Bahan Dapur</h3>
                <p>Bumbuilah hidupmu, seperti membumbui makananmu.</p>
                <img src="../WebPictures/BahanDapur.png" alt="Bahan Dapur" class="category-img">
            </div>
        </section>

        <section class="products" id="productContainer">
            <?php while($row = mysqli_fetch_assoc($query_produk)) { 
                $kategori_js = strtolower($row['kategori']);
                if ($kategori_js == 'atk') { $kategori_js = 'atk'; }
            ?>
            <div class="product-card" data-kategori="<?php echo $kategori_js; ?>">
                <div class="product-stok">Stok: <?php echo $row['stok']; ?></div>
                <div class="product-img-wrapper">
                    <img src="../Admin/assets/<?php echo $row['gambar']; ?>" alt="<?php echo $row['nama_produk']; ?>">
                </div>
                <div class="product-title"><?php echo $row['nama_produk']; ?></div>
                <div class="product-price">Rp. <?php echo number_format($row['harga'], 0, ',', '.'); ?></div>
                <form class="form-tambah">
                    <input type="hidden" name="nama_produk" value="<?php echo htmlspecialchars($row['nama_produk']); ?>">
                    <input type="hidden" name="harga_produk" value="<?php echo $row['harga']; ?>">
                    <input type="hidden" name="stok_produk" value="<?php echo $row['stok']; ?>">
                    <?php if ($row['stok'] > 0) { ?>
                    <button type="submit" class="btn-tambah">TAMBAH</button>
                    <?php } else { ?>
                    <button type="button" class="btn-habis" disabled>HABIS</button>
                    <?php } ?>
                </form>
            </div>
            <?php } ?>
        </section>
    </div>

    <script>
        function filterKategori(kat) {
            document.querySelectorAll('.product-card').forEach(p => {
                p.style.display = (kat === 'semua' || p.getAttribute('data-kategori') === kat) ? 'flex' : 'none';
            });
        }

        document.querySelectorAll('.form-tambah').forEach(f => {
            f.addEventListener('submit', function(e) {
                e.preventDefault(); 
                const fd = new FormData(this);
                fd.append('ajax_tambah', '1'); 
                
                fetch('Katalog-Beranda.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.status === 'success') {
                        const b = document.getElementById('cart-badge');
                        b.innerText = d.total_cart;
                        b.style.display = 'inline-block';
                        } else {
                            alert(d.pesan);
                        }
                });
            });
        });
    </script>
</body>
</html>