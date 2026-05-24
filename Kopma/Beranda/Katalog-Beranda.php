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
    <link rel="icon" href="../WebPictures/LOGO_KOPMA.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../Style/Katalog-Beranda.css">
    
    <style>
        /* Floating Cart Button */
        .floating-cart-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #2d5a3a 0%, #3c8a55 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(45, 90, 58, 0.4);
            cursor: pointer;
            z-index: 1000;
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
        }
        
        .floating-cart-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(45, 90, 58, 0.6);
        }
        
        .floating-cart-btn i {
            font-size: 24px;
        }
        
        .floating-cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff4444;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            border: 2px solid white;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* Flying Animation */
        @keyframes flyToCart {
            0% {
                transform: translate(0, 0) scale(1);
                opacity: 1;
            }
            50% {
                transform: translate(var(--tx), var(--ty)) scale(0.5);
                opacity: 0.8;
            }
            100% {
                transform: translate(var(--tx), var(--ty)) scale(0.2);
                opacity: 0;
            }
        }
        
        .flying-item {
            position: fixed;
            pointer-events: none;
            z-index: 9999;
            animation: flyToCart 0.8s ease-in-out;
        }
        
        .flying-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }
        
        /* Success Toast Notification */
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 10000;
            animation: slideInRight 0.3s ease-out;
            max-width: 300px;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .toast-notification.hide {
            animation: slideOutRight 0.3s ease-in forwards;
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
        
        .toast-icon {
            width: 40px;
            height: 40px;
            background: #4caf50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
        
        .toast-content {
            flex: 1;
        }
        
        .toast-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 3px;
        }
        
        .toast-message {
            font-size: 13px;
            color: #666;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .floating-cart-btn {
                bottom: 20px;
                right: 20px;
                width: 55px;
                height: 55px;
            }
            
            .toast-notification {
                right: 10px;
                left: 10px;
                max-width: none;
            }
        }
    </style>
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
                <img src="../WebPictures/LOGO_KOPMA.png" alt="Kopma Berdikari">
            </div>
        </section>

        <section class="categories">
            <div class="category-card cat-0" onclick="filterKategori('semua')">
                <h3>Semua</h3>
                <p>Seluruh produk Kopma Mart</p>
                <img src="../WebPictures/SEMUA.png" alt="Semua" class="category-img">
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

    <!-- Floating Cart Button -->
    <a href="ValidasiPemesanan.php" class="floating-cart-btn" id="floatingCartBtn">
        <i class="fas fa-shopping-cart"></i>
        <span class="floating-cart-badge" id="floatingCartBadge" style="display: <?php echo ($total_notifikasi > 0) ? 'flex' : 'none'; ?>;">
            <?php echo $total_notifikasi; ?>
        </span>
    </a>

    <script>
        function filterKategori(kat) {
            document.querySelectorAll('.product-card').forEach(p => {
                p.style.display = (kat === 'semua' || p.getAttribute('data-kategori') === kat) ? 'flex' : 'none';
            });
        }

        // Fungsi untuk animasi flying to cart
        function flyToCart(button) {
            const productCard = button.closest('.product-card');
            const productImg = productCard.querySelector('.product-img-wrapper img');
            const floatingBtn = document.getElementById('floatingCartBtn');
            
            // Clone gambar produk
            const flyingImg = productImg.cloneNode(true);
            const flyingDiv = document.createElement('div');
            flyingDiv.className = 'flying-item';
            flyingDiv.appendChild(flyingImg);
            
            // Posisi awal (dari produk)
            const startRect = productImg.getBoundingClientRect();
            flyingDiv.style.left = startRect.left + 'px';
            flyingDiv.style.top = startRect.top + 'px';
            
            // Posisi tujuan (ke floating button)
            const endRect = floatingBtn.getBoundingClientRect();
            const deltaX = endRect.left - startRect.left;
            const deltaY = endRect.top - startRect.top;
            
            flyingDiv.style.setProperty('--tx', deltaX + 'px');
            flyingDiv.style.setProperty('--ty', deltaY + 'px');
            
            document.body.appendChild(flyingDiv);
            
            // Hapus setelah animasi selesai
            setTimeout(() => {
                flyingDiv.remove();
            }, 800);
        }

        // Fungsi untuk menampilkan toast notification
        function showToast(title, message) {
            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="fas fa-check"></i>
                </div>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Auto hide setelah 3 detik
            setTimeout(() => {
                toast.classList.add('hide');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        document.querySelectorAll('.form-tambah').forEach(f => {
            f.addEventListener('submit', function(e) {
                e.preventDefault();
                const fd = new FormData(this);
                fd.append('ajax_tambah', '1');
                const button = this.querySelector('button[type="submit"]');
                const productName = fd.get('nama_produk');
                
                fetch('Katalog-Beranda.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.status === 'success') {
                        // Update badge di header
                        const b = document.getElementById('cart-badge');
                        b.innerText = d.total_cart;
                        b.style.display = 'inline-block';
                        
                        // Update floating button badge
                        const fb = document.getElementById('floatingCartBadge');
                        fb.innerText = d.total_cart;
                        fb.style.display = 'flex';
                        
                        // Animasi flying to cart
                        flyToCart(button);
                        
                        // Tampilkan toast notification
                        showToast('Berhasil!', `${productName} ditambahkan ke keranjang`);
                    } else {
                        alert(d.pesan);
                    }
                });
            });
        });
    </script>
</body>
</html>