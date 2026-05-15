<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

// --- LOGIKA HAPUS BARANG MENGGUNAKAN AJAX ---
if (isset($_POST['ajax_hapus_item'])) {
    $item_dihapus = $_POST['nama_item_hapus'];
    
    // Hapus dari session
    if (isset($_SESSION['keranjang'][$item_dihapus])) {
        unset($_SESSION['keranjang'][$item_dihapus]);
    }
    
    // Kalkulasi ulang untuk dikirim kembali ke JavaScript
    $total_qty = 0;
    $total_harga = 0;
    $is_empty = empty($_SESSION['keranjang']);

    if (!$is_empty) {
        foreach ($_SESSION['keranjang'] as $nama => $item) {
            $total_qty += $item['qty'];
            $total_harga += ($item['harga'] * $item['qty']);
        }
    }
    
    // Format harga
    $harga_format = number_format($total_harga, 0, ',', '.');
    
    // Kirim balasan format JSON
    echo json_encode([
        'status' => 'success', 
        'total_qty' => $total_qty, 
        'total_harga' => $harga_format,
        'is_empty' => $is_empty
    ]);
    exit; 
}
// --------------------------------------------

// --- LOGIKA UPDATE QTY (+ / -) MENGGUNAKAN AJAX ---
if (isset($_POST['ajax_update_qty'])) {
    $item_update = $_POST['nama_item_update'];
    $qty_baru = (int)$_POST['qty_baru'];

    if ($qty_baru > 0 && isset($_SESSION['keranjang'][$item_update])) {
        // Update nilai qty di session
        $_SESSION['keranjang'][$item_update]['qty'] = $qty_baru;
    }

    // Kalkulasi ulang seluruh keranjang
    $total_qty = 0;
    $total_harga = 0;
    $subtotal_item = 0;
    
    foreach ($_SESSION['keranjang'] as $nama => $item) {
        $total_qty += $item['qty'];
        $total_harga += ($item['harga'] * $item['qty']);
        // Simpan subtotal khusus untuk item yang sedang diupdate
        if ($nama === $item_update) {
            $subtotal_item = $item['harga'] * $item['qty'];
        }
    }

    echo json_encode([
        'status' => 'success',
        'total_qty' => $total_qty,
        'total_harga' => number_format($total_harga, 0, ',', '.'),
        'subtotal_item' => number_format($subtotal_item, 0, ',', '.')
    ]);
    exit;
}
// --------------------------------------------

// Cek apakah keranjang kosong saat halaman pertama kali dimuat
$keranjang_kosong = empty($_SESSION['keranjang']);

// Kalkulasi awal
$total_qty = 0;
$total_harga = 0;

if (!$keranjang_kosong) {
    foreach ($_SESSION['keranjang'] as $nama => $item) {
        $total_qty += $item['qty'];
        $total_harga += ($item['harga'] * $item['qty']);
    }
}

$harga_format = number_format($total_harga, 0, ',', '.');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kopma Mart</title>
    <link rel="icon" href="../WebPictures/LOGO KOPMA.png" type="image/png">
    <link rel="stylesheet" href="../Style/ValidasiPemesanan.css">
    
</head>
<body>

    <div class="container">
        <section class="hero">
            <div class="hero-content">
                <div class="subtitle">KOPMA BERDIKARI</div>
                <h1>Belanja <span class="italic">Mudah,</span><br>Harga Ramah.</h1>
                <p class="desc">Semua kebutuhan harian mahasiswa - dari snack, minuman dan alat tulis hingga produk lokal, langsung dengan harga terjangkau</p>
                <div class="hero-buttons">
                    <a href="Katalog-Beranda.php" class="link-pesanan">Kembali Belanja</a>
                </div>
            </div>
            <div class="hero-logo">
                <img src="../WebPictures/LOGO KOPMA.png" alt="Kopma Berdikari">
            </div>
        </section>

        <h2 class="section-title">PESANAN SAYA</h2>

        <div class="empty-cart-message" id="pesan-kosong" style="display: <?php echo $keranjang_kosong ? 'block' : 'none'; ?>;">
            <h3 style="color: #ff0000;">Maaf, Silakan memilih minimal 1 item untuk melakukan pemesanan.</h3>
            <br>
            <a href="Katalog-Beranda.php" style="background: #3c8a55; color: white; padding: 10px 20px; border-radius: 20px; text-decoration: none; font-weight: bold;">Lihat Katalog</a>
        </div>

        <section class="validation-wrapper" id="wrapper-form" style="display: <?php echo $keranjang_kosong ? 'none' : 'flex'; ?>;">
            
            <form class="form-section" action="../Controller/ProsesValidasi.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Nama User :</label>
                    <input type="text" name="nama" placeholder="Masukkan nama Anda" required>
                </div>
                <div class="form-group" style="display: flex; gap: 15px;">
                    <div style="flex: 1;">
                        <label>Tanggal Pengambilan :</label>
                        <input type="date" name="tanggal" id="input-tanggal" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div style="flex: 1;">
                        <label>Jam Pengambilan :</label>
                        <input type="time" name="waktu" id="input-waktu" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Nomer HP :</label>
                    <input type="text" name="hp" placeholder="08XXXXXXXXXX" required>
                </div>
                <div class="form-group">
                    <label>Alamat :</label>
                    <input type="text" name="alamat" placeholder="Alamat lengkap" required>
                </div>
                
                <div class="form-group">
                    <label style="color: #3c8a55; font-size: 16px;">Metode Pembayaran: <strong>QRIS (Hanya Non-Tunai)</strong></label>
                    <input type="hidden" name="metode" value="QRIS">
                    <div id="qris-container" class="qris-section">
                        <p style="font-weight: 700; margin-bottom: 10px; color: #333;">Scan QRIS di bawah ini:</p>
                        <img src="../WebPictures/IH.png" alt="QRIS Kopma Mart"> 
                        <p style="font-size: 13px; margin-bottom: 8px; color: #333; text-align: left;">Upload Bukti Pembayaran (Wajib):</p>
                        <input type="file" name="bukti_pembayaran" id="bukti-pembayaran" accept="image/*" required>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="Katalog-Beranda.php" style="text-decoration:none;"><button type="button" class="btn-batal">Batal</button></a>
                    <button type="submit" class="btn-pesan">Pesan</button>
                </div>
            </form>

            <div class="summary-section">
                <div class="summary-card-top">
                    <?php 
                    if (!$keranjang_kosong) {
                        foreach ($_SESSION['keranjang'] as $nama => $item): 
                            $subtotal_item = number_format($item['harga'] * $item['qty'], 0, ',', '.'); 
                            $row_id = "row-" . md5($nama); 
                    ?>
                        <div class="item-row" id="<?php echo $row_id; ?>" style="display: flex; justify-content: space-between; align-items: center; width: 100%; border-bottom: 1px dashed rgba(255,255,255,0.4); padding-bottom: 10px;">
                            <div style="display: flex; align-items: center;">
                                
                                <form class="form-hapus" style="margin: 0;">
                                    <input type="hidden" name="nama_item_hapus" value="<?php echo htmlspecialchars($nama); ?>">
                                    <input type="hidden" name="row_id" value="<?php echo $row_id; ?>">
                                    <button type="submit" class="btn-hapus-item" title="Hapus Barang">&#10006;</button>
                                </form>

                                <span class="item-name-font"><?php echo $nama; ?></span>
                                
                                <div class="qty-control">
                                    <button type="button" class="btn-qty btn-minus" data-nama="<?php echo htmlspecialchars($nama); ?>" data-row="<?php echo $row_id; ?>">&minus;</button>
                                    
                                    <input type="number" class="input-qty" value="<?php echo $item['qty']; ?>" min="1" readonly>
                                    
                                    <button type="button" class="btn-qty btn-plus" data-nama="<?php echo htmlspecialchars($nama); ?>" data-row="<?php echo $row_id; ?>">&plus;</button>
                                </div>

                            </div>
                            <div class="item-qty-price subtotal-item-text"><?php echo $subtotal_item; ?></div>
                        </div>
                    <?php 
                        endforeach; 
                    }
                    ?>
                </div>

                <div class="summary-card-bottom">
                    <div class="total-info">
                        <h4 id="teks-total-qty"><?php echo $total_qty; ?> item</h4>
                        <p>silahkan ambil ke kopma yaaa</p>
                    </div>
                    <div class="total-price-box">
                        <span id="teks-total-harga"><?php echo $harga_format; ?></span>
                        <img src="../WebPictures/Snack.png" alt="Cart">
                    </div>
                </div>
            </div>
        </section>

    </div>

    <script>
        // ============================================
        // LOGIKA AJAX HAPUS ITEM (TOMBOL SILANG)
        // ============================================
        document.querySelectorAll('.form-hapus').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); 

                const formData = new FormData(this);
                formData.append('ajax_hapus_item', '1'); 
                const rowId = formData.get('row_id'); 

                fetch('ValidasiPemesanan.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const barisItem = document.getElementById(rowId);
                        if (barisItem) {
                            barisItem.classList.add('fade-out');
                            setTimeout(() => {
                                barisItem.remove(); 
                            }, 300);
                        }

                        if (data.is_empty) {
                            setTimeout(() => {
                                document.getElementById('wrapper-form').style.display = 'none';
                                document.getElementById('pesan-kosong').style.display = 'block';
                            }, 300); 
                        } else {
                            document.getElementById('teks-total-qty').innerText = data.total_qty + ' item';
                            document.getElementById('teks-total-harga').innerText = data.total_harga;
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        });

        // ============================================
        // LOGIKA AJAX UPDATE JUMLAH QTY (+ / -)
        // ============================================
        function updateQtyAjax(namaItem, rowId, newQty, inputElement) {
            const formData = new FormData();
            formData.append('ajax_update_qty', '1');
            formData.append('nama_item_update', namaItem);
            formData.append('qty_baru', newQty);

            fetch('ValidasiPemesanan.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    inputElement.value = newQty;
                    
                    const row = document.getElementById(rowId);
                    if (row) {
                        row.querySelector('.subtotal-item-text').innerText = data.subtotal_item;
                    }
                    
                    document.getElementById('teks-total-qty').innerText = data.total_qty + ' item';
                    document.getElementById('teks-total-harga').innerText = data.total_harga;
                }
            })
            .catch(error => console.error('Error:', error));
        }

        document.querySelectorAll('.btn-plus').forEach(btn => {
            btn.addEventListener('click', function() {
                const input = this.parentElement.querySelector('.input-qty');
                let qty = parseInt(input.value) + 1;
                
                const nama = this.getAttribute('data-nama');
                const rowId = this.getAttribute('data-row');
                
                updateQtyAjax(nama, rowId, qty, input);
            });
        });

        document.querySelectorAll('.btn-minus').forEach(btn => {
            btn.addEventListener('click', function() {
                const input = this.parentElement.querySelector('.input-qty');
                let qty = parseInt(input.value) - 1;
                
                if (qty < 1) {
                    qty = 1; 
                    return; 
                }
                
                const nama = this.getAttribute('data-nama');
                const rowId = this.getAttribute('data-row');
                
                updateQtyAjax(nama, rowId, qty, input);
            });
        });

        // ============================================
        // LOGIKA PROTEKSI TANGGAL DAN JAM
        // ============================================
        const inputTanggal = document.getElementById('input-tanggal');
        const inputWaktu = document.getElementById('input-waktu');

        function batasiWaktu() {
            if (!inputTanggal.value) return;

            const hariIni = new Date();
            const tanggalPilih = new Date(inputTanggal.value);

            if (tanggalPilih.toDateString() === hariIni.toDateString()) {
                const jam = String(hariIni.getHours()).padStart(2, '0');
                const menit = String(hariIni.getMinutes()).padStart(2, '0');
                const waktuSekarang = `${jam}:${menit}`;

                inputWaktu.min = waktuSekarang;

                if (inputWaktu.value && inputWaktu.value < waktuSekarang) {
                    inputWaktu.value = '';
                    alert('Maaf, waktu pengambilan tidak boleh kurang dari waktu saat ini.');
                }
            } else {
                inputWaktu.removeAttribute('min');
            }
        }

        inputTanggal.addEventListener('change', batasiWaktu);
        inputWaktu.addEventListener('change', batasiWaktu);
    </script>
</body>
</html>