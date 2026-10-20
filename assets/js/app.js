// Interaksi kecil untuk seluruh halaman KosKu
(function () {
    document.documentElement.classList.add('js');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // 1. Elemen [data-reveal] muncul saat masuk layar; [data-observe] hanya diberi kelas is-visible
    const revealEls = document.querySelectorAll('[data-reveal], [data-observe]');
    if ('IntersectionObserver' in window && !reduceMotion) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                const el = entry.target;
                el.style.transitionDelay = (el.dataset.revealDelay || 0) + 'ms';
                el.classList.add('is-visible');
                io.unobserve(el);
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
        revealEls.forEach((el) => io.observe(el));
    } else {
        revealEls.forEach((el) => el.classList.add('is-visible'));
    }

    // 2. Angka [data-count] menghitung naik
    document.querySelectorAll('[data-count]').forEach((el) => {
        const target = parseInt(el.dataset.count, 10) || 0;
        if (reduceMotion || target === 0) return;
        const start = performance.now();
        const dur = 900;
        const tick = (now) => {
            const p = Math.min((now - start) / dur, 1);
            el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3)));
            if (p < 1) requestAnimationFrame(tick);
        };
        el.textContent = '0';
        requestAnimationFrame(tick);
    });

    // 3. Navbar publik diberi bayangan setelah scroll + garis progres baca di atas
    const nav = document.getElementById('siteNav');
    const progress = document.getElementById('scrollProgress');
    if (nav || progress) {
        const onScroll = () => {
            if (nav) nav.classList.toggle('scrolled', window.scrollY > 8);
            if (progress) {
                const max = document.documentElement.scrollHeight - window.innerHeight;
                progress.style.transform = 'scaleX(' + (max > 0 ? window.scrollY / max : 0) + ')';
            }
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    // 3b. Kartu kamar sedikit miring mengikuti kursor (hanya perangkat dengan mouse)
    if (!reduceMotion && window.matchMedia('(hover: hover)').matches) {
        document.querySelectorAll('[data-tilt]').forEach((card) => {
            card.addEventListener('mousemove', (e) => {
                const r = card.getBoundingClientRect();
                const x = (e.clientX - r.left) / r.width - 0.5;
                const y = (e.clientY - r.top) / r.height - 0.5;
                card.style.transform = `perspective(900px) rotateX(${(-y * 5).toFixed(2)}deg) rotateY(${(x * 5).toFixed(2)}deg) translateY(-4px)`;
            });
            card.addEventListener('mouseleave', () => { card.style.transform = ''; });
        });
    }

    // 4. Galeri foto kamar
    document.querySelectorAll('[data-gallery]').forEach((thumb) => {
        thumb.addEventListener('click', () => {
            const main = document.querySelector(thumb.dataset.gallery);
            if (!main || main.src === thumb.src) return;
            main.classList.add('swap');
            setTimeout(() => { main.src = thumb.src; main.classList.remove('swap'); }, 180);
            document.querySelectorAll('[data-gallery]').forEach((t) => t.classList.toggle('active', t === thumb));
        });
    });

    // 5. Tampilkan / sembunyikan password
    document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.querySelector(btn.dataset.togglePassword);
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    });

    // 6. Konfirmasi (<form data-confirm="...">) + status loading pada tombol kirim
    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', (ev) => {
            if (form.dataset.confirm && !confirm(form.dataset.confirm)) {
                ev.preventDefault();
                return;
            }
            if (form.method.toLowerCase() !== 'post') return;
            const btn = ev.submitter || form.querySelector('button:not([type=button])');
            // Tidak di-disable supaya value tombol (name/value) tetap ikut terkirim
            if (btn) btn.classList.add('is-loading');
        });
    });

    // 7. Tooltip Bootstrap
    if (window.bootstrap) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => new bootstrap.Tooltip(el));
    }

    // 8. Notifikasi sukses hilang sendiri setelah beberapa detik
    document.querySelectorAll('.alert-success').forEach((el) => {
        setTimeout(() => window.bootstrap && bootstrap.Alert.getOrCreateInstance(el).close(), 5000);
    });
})();
