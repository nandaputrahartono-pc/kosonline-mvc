<?php ob_start(); ?>
    <section class="contact-section">
        <h2>Hubungi Kami</h2>
        <p>Kirim pesan atau pertanyaan seputar kost, kami akan merespon lewat Email.</p>

        <form class="contact-form" method="POST">
            <div class="form-group">
                <label>Nama</label>
                <input type="text" name="nama" placeholder="Nama lengkap" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="Email aktif" required>
            </div>

            <div class="form-group">
                <label>Pesan</label>
                <textarea name="pesan" rows="5" placeholder="Tulis pertanyaanmu..." required></textarea>
            </div>

            <button type="submit" name="kirim_pesan" class="btn-submit" style="width:100%; padding:12px; background:#1e3a8a; color:white; border:none; cursor:pointer;">Kirim Pesan</button>
        </form>
    </section>
<?php
$content = ob_get_clean();
$title = 'Hubungi Kami - KosOnline';
require base_path('app/Views/layouts/public.php');
