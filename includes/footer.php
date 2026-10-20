<?php if ($layout === 'auth'): ?>
        </div>
    </main>
</div>

<?php elseif ($layout === 'site'): ?>
<footer class="site-footer pt-5 pb-4">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5">
                <a href="<?= url() ?>" class="logo mb-3"><span class="logo-mark"><?= logo_svg() ?></span><?= e(APP_NAME) ?></a>
                <p class="mt-3 mb-0" style="max-width:24rem">Kos bulanan di <?= e(KOTA) ?> dengan pengelolaan transparan — booking, tagihan, dan laporan kerusakan tercatat rapi.</p>
            </div>
            <div class="col-6 col-lg-3">
                <h6 class="mb-3">Kontak</h6>
                <ul class="list-unstyled mb-0 d-grid gap-2">
                    <li><a href="https://wa.me/<?= WA_NUMBER ?>" target="_blank" rel="noopener"><i class="bi bi-whatsapp me-2"></i>+<?= e(WA_NUMBER) ?></a></li>
                    <li><a href="mailto:<?= e(EMAIL_KONTAK) ?>"><i class="bi bi-envelope me-2"></i><?= e(EMAIL_KONTAK) ?></a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-4">
                <h6 class="mb-3">Alamat</h6>
                <p class="mb-1"><?= e(ALAMAT) ?></p>
                <p class="mb-0 small"><?= e(JAM_KUNJUNGAN) ?></p>
            </div>
        </div>
        <div class="border-top border-secondary border-opacity-25 mt-4 pt-3 small d-flex flex-wrap justify-content-between gap-2">
            <span>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Hak cipta dilindungi.</span>
            <a href="<?= url('login.php') ?>">Masuk pengelola</a>
        </div>
    </div>
</footer>

<?php else: ?>
    </main>
    <footer class="app-footer">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?> &middot; Sistem Informasi Kos</footer>
</div>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('assets/js/app.js') ?>"></script>
</body>
</html>
