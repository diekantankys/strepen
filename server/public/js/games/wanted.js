(function () {
    if (window._wantedRunning) return;
    window._wantedRunning = true;

    const canvas = document.getElementById('wanted-canvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const facesData = JSON.parse(canvas.dataset.faces || '[]');
    const textStart   = canvas.dataset.textStart   || 'Press SPACE or tap to start';
    const textNoFaces = canvas.dataset.textNoFaces  || 'No faces available';
    const textWanted  = canvas.dataset.textWanted   || 'WANTED';
    const textScore   = canvas.dataset.textScore    || 'Score';
    const textLives   = canvas.dataset.textLives    || 'Lives';
    const textTime    = canvas.dataset.textTime     || 'Time';

    const MAX_LIVES    = 3;
    const REVEAL_SECS  = 5;
    const GAME_TIME    = 120;
    const WRONG_FRAMES = 25;
    const MIN_FLOATERS = 5;
    const MAX_FLOATERS = 18;

    let W = 0, H = 0;
    let state = 'idle'; // idle | revealing | playing
    let score = 0;
    let lives = MAX_LIVES;
    let timeLeft = GAME_TIME;
    let targetIndex = 0;
    let revealTimer = REVEAL_SECS;
    let floaters = [];
    let images = [];
    let animFrame = null;
    let lastTime = null;

    // ── resize ────────────────────────────────────────────────────────────────
    function resize() {
        W = canvas.parentElement.clientWidth;
        H = canvas.parentElement.clientHeight;
        canvas.width  = W;
        canvas.height = H;
    }

    // ── preload ───────────────────────────────────────────────────────────────
    function preloadImages(cb) {
        if (!facesData.length) { cb(); return; }
        let n = 0;
        images = facesData.map(function (f) {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = img.onerror = function () { if (++n === facesData.length) cb(); };
            img.src = f.url;
            return img;
        });
    }

    // ── difficulty helpers ────────────────────────────────────────────────────
    function faceR() { return Math.floor(Math.min(W, H) * 0.09); }

    function currentFloatCount() {
        const elapsed = GAME_TIME - timeLeft;
        return Math.min(MIN_FLOATERS + Math.floor(elapsed / 10), MAX_FLOATERS);
    }

    function currentSpeed() {
        // 1.0 at start → 2.2 at time 0
        return 1.0 + ((GAME_TIME - timeLeft) / GAME_TIME) * 1.2;
    }

    // ── spawn floaters ────────────────────────────────────────────────────────
    function spawnFloaters() {
        const r     = faceR();
        const count = currentFloatCount();
        const speed = currentSpeed();
        floaters    = [];

        floaters.push(makeFloater(targetIndex, r, speed));

        for (let i = 1; i < count; i++) {
            let idx;
            do { idx = Math.floor(Math.random() * facesData.length); }
            while (idx === targetIndex && facesData.length > 1);
            floaters.push(makeFloater(idx, r, speed));
        }

        for (let i = floaters.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [floaters[i], floaters[j]] = [floaters[j], floaters[i]];
        }
    }

    function makeFloater(idx, r, speed) {
        const angle = Math.random() * Math.PI * 2;
        const s     = speed * (0.7 + Math.random() * 0.6);
        return {
            idx: idx,
            x: r + Math.random() * (W - r * 2),
            y: r + Math.random() * (H - r * 2),
            vx: Math.cos(angle) * s,
            vy: Math.sin(angle) * s,
            r: r,
            wrongFlash: 0,
        };
    }

    // ── game flow ─────────────────────────────────────────────────────────────
    function startGame() {
        score       = 0;
        lives       = MAX_LIVES;
        timeLeft    = GAME_TIME;
        targetIndex = Math.floor(Math.random() * facesData.length);
        beginReveal();
    }

    function beginReveal() {
        revealTimer = REVEAL_SECS;
        state       = 'revealing';
    }

    function beginPlaying() {
        spawnFloaters();
        state = 'playing';
    }

    function endGame() {
        Livewire.dispatch('gameOver', { score: score });
    }

    // ── input ─────────────────────────────────────────────────────────────────
    function handleTap(cx, cy) {
        if (state === 'idle') {
            if (facesData.length >= 2) startGame();
            return;
        }
        if (state !== 'playing') return;

        for (let i = floaters.length - 1; i >= 0; i--) {
            const f  = floaters[i];
            const dx = cx - f.x, dy = cy - f.y;
            if (dx * dx + dy * dy <= (f.r + 4) * (f.r + 4)) {
                if (f.idx === targetIndex) {
                    score++;
                    const prev = targetIndex;
                    if (facesData.length > 1) {
                        do { targetIndex = Math.floor(Math.random() * facesData.length); }
                        while (targetIndex === prev);
                    }
                    beginReveal();
                } else {
                    f.wrongFlash = WRONG_FRAMES;
                    lives--;
                    if (lives <= 0) endGame();
                }
                return;
            }
        }
    }

    canvas.addEventListener('click', function (e) {
        const rect = canvas.getBoundingClientRect();
        handleTap(
            (e.clientX - rect.left) * (W / rect.width),
            (e.clientY - rect.top)  * (H / rect.height)
        );
    });

    canvas.addEventListener('touchstart', function (e) {
        e.preventDefault();
        const rect = canvas.getBoundingClientRect();
        const t    = e.changedTouches[0];
        handleTap(
            (t.clientX - rect.left) * (W / rect.width),
            (t.clientY - rect.top)  * (H / rect.height)
        );
    }, { passive: false });

    document.addEventListener('keydown', function (e) {
        if ((e.code === 'Space' || e.code === 'Enter') && state === 'idle') {
            e.preventDefault();
            if (facesData.length >= 2) startGame();
        }
    });

    // ── draw helpers ──────────────────────────────────────────────────────────
    function drawFace(img, x, y, r) {
        ctx.save();
        ctx.beginPath();
        ctx.arc(x, y, r, 0, Math.PI * 2);
        ctx.clip();
        if (img && img.complete && img.naturalWidth > 0) {
            ctx.drawImage(img, x - r, y - r, r * 2, r * 2);
        } else {
            ctx.fillStyle = '#888';
            ctx.fill();
        }
        ctx.restore();
    }

    function drawHud() {
        const fs = Math.max(13, Math.floor(Math.min(W, H) * 0.038));
        ctx.font = `bold ${fs}px sans-serif`;

        // score — left
        ctx.fillStyle  = '#ffffff';
        ctx.textAlign  = 'left';
        ctx.fillText(textScore + ': ' + score, 12, fs + 10);

        // timer — center, colour shifts red as time runs out
        const ratio    = timeLeft / GAME_TIME;
        const r        = Math.round(255);
        const g        = Math.round(ratio * 220);
        ctx.fillStyle  = `rgb(${r},${g},0)`;
        ctx.textAlign  = 'center';
        ctx.fillText(textTime + ': ' + Math.ceil(timeLeft), W / 2, fs + 10);

        // lives — right (hearts)
        ctx.textAlign  = 'right';
        let hearts = '';
        for (let i = 0; i < MAX_LIVES; i++) hearts += (i < lives ? '♥' : '♡') + ' ';
        ctx.fillStyle  = '#e03030';
        ctx.fillText(hearts.trim(), W - 12, fs + 10);
    }

    // ── draw states ───────────────────────────────────────────────────────────
    function drawIdle() {
        ctx.fillStyle = '#1a1a2e';
        ctx.fillRect(0, 0, W, H);
        const fs = Math.max(14, Math.floor(Math.min(W, H) * 0.05));
        ctx.font = `bold ${fs}px sans-serif`;
        ctx.textAlign = 'center';
        if (facesData.length < 2) {
            ctx.fillStyle = '#ffffff';
            ctx.fillText(textNoFaces, W / 2, H / 2);
        } else {
            ctx.fillStyle = '#f8d030';
            ctx.fillText(textStart, W / 2, H / 2);
        }
    }

    function drawReveal() {
        ctx.fillStyle = '#1a1a2e';
        ctx.fillRect(0, 0, W, H);

        const r  = Math.floor(Math.min(W, H) * 0.26);
        const cx = W / 2, cy = H / 2;

        ctx.strokeStyle = '#f8d030';
        ctx.lineWidth   = 5;
        ctx.beginPath();
        ctx.arc(cx, cy, r + 5, 0, Math.PI * 2);
        ctx.stroke();

        drawFace(images[targetIndex], cx, cy, r);

        const titleFs = Math.max(18, Math.floor(Math.min(W, H) * 0.06));
        ctx.font      = `900 ${titleFs}px serif`;
        ctx.fillStyle = '#e03030';
        ctx.textAlign = 'center';
        ctx.fillText(textWanted, cx, cy - r - 14);

        const nameFs  = Math.max(12, Math.floor(Math.min(W, H) * 0.038));
        ctx.font      = `bold ${nameFs}px sans-serif`;
        ctx.fillStyle = '#f8d030';
        ctx.textAlign = 'center';
        ctx.fillText(facesData[targetIndex].name, cx, cy + r + nameFs + 10);

        const countFs = Math.max(20, Math.floor(Math.min(W, H) * 0.07));
        ctx.font      = `bold ${countFs}px sans-serif`;
        ctx.fillStyle = 'rgba(255,255,255,0.55)';
        ctx.textAlign = 'center';
        ctx.fillText(Math.ceil(revealTimer), cx, cy + r + nameFs + countFs + 18);

        drawHud();
    }

    function drawPlaying() {
        ctx.fillStyle = '#1a1a2e';
        ctx.fillRect(0, 0, W, H);

        floaters.forEach(function (f) {
            const col = f.wrongFlash > 0 ? '#e03030' : '#f0c040';
            ctx.strokeStyle = col;
            ctx.lineWidth   = 3;
            ctx.beginPath();
            ctx.arc(f.x, f.y, f.r + 3, 0, Math.PI * 2);
            ctx.stroke();
            drawFace(images[f.idx], f.x, f.y, f.r);
        });

        drawHud();
    }

    // ── update ────────────────────────────────────────────────────────────────
    function update(dt) {
        if (state === 'idle') return;

        // global countdown always ticks
        timeLeft -= dt;
        if (timeLeft <= 0) {
            timeLeft = 0;
            endGame();
            return;
        }

        if (state === 'revealing') {
            revealTimer -= dt;
            if (revealTimer <= 0) beginPlaying();
            return;
        }

        if (state === 'playing') {
            floaters.forEach(function (f) {
                f.x += f.vx;
                f.y += f.vy;
                if (f.wrongFlash > 0) f.wrongFlash--;

                if (f.x - f.r < 0)  { f.x = f.r;     f.vx =  Math.abs(f.vx); }
                if (f.x + f.r > W)  { f.x = W - f.r; f.vx = -Math.abs(f.vx); }
                if (f.y - f.r < 0)  { f.y = f.r;     f.vy =  Math.abs(f.vy); }
                if (f.y + f.r > H)  { f.y = H - f.r; f.vy = -Math.abs(f.vy); }
            });
        }
    }

    // ── loop ──────────────────────────────────────────────────────────────────
    function loop(ts) {
        animFrame = requestAnimationFrame(loop);
        const dt  = lastTime === null ? 0 : Math.min((ts - lastTime) / 1000, 0.1);
        lastTime  = ts;
        update(dt);

        if      (state === 'idle')      drawIdle();
        else if (state === 'revealing') drawReveal();
        else if (state === 'playing')   drawPlaying();
    }

    // ── restart ───────────────────────────────────────────────────────────────
    document.addEventListener('livewire:initialized', function () {
        Livewire.on('game-restart', function () {
            state    = 'idle';
            score    = 0;
            lives    = MAX_LIVES;
            timeLeft = GAME_TIME;
            floaters = [];
        });
    });

    // ── boot ──────────────────────────────────────────────────────────────────
    window.addEventListener('resize', resize);
    resize();
    preloadImages(function () {
        if (animFrame) cancelAnimationFrame(animFrame);
        lastTime = null;
        loop(performance.now());
    });
})();
