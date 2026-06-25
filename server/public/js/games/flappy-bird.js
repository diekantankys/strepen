(function () {
    if (window._flappyBirdRunning) return;
    window._flappyBirdRunning = true;

    const canvas = document.getElementById('flappy-bird-canvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const textStart = canvas.dataset.textStart || 'Press SPACE or tap to start';

    let W = 0;
    let H = 0;
    let GROUND_Y = H * 0.87;

    let GRAVITY, FLAP_VY, PIPE_W, PIPE_GAP, PIPE_SPEED, PIPE_INTERVAL;

    let state = 'idle';
    let score = 0;
    let frameCount = 0;
    let groundOffset = 0;
    let bird, pipes, deathTimer;

    function updateScales() {
        GROUND_Y = H * 0.87;

        GRAVITY = H * 0.00078;
        FLAP_VY = H * -0.0194;
        PIPE_W = W * 0.081;
        PIPE_GAP = H * 0.333;
        PIPE_SPEED = W * 0.005;
        PIPE_INTERVAL = 90;
    }

    function reset() {
        state = 'idle';
        score = 0;
        frameCount = 0;
        groundOffset = 0;
        bird = { x: W * 0.15, y: H / 2, vy: 0 };
        pipes = [];
        clearTimeout(deathTimer);
    }

    function flap() {
        if (state === 'dead') return;
        if (state === 'idle') state = 'playing';
        bird.vy = FLAP_VY;
    }

    document.addEventListener('keydown', function (e) {
        if (e.code === 'Space' || e.code === 'ArrowUp') {
            e.preventDefault();
            flap();
        }
    });
    canvas.addEventListener('click', flap);
    canvas.addEventListener('touchstart', function (e) {
        e.preventDefault();
        flap();
    }, { passive: false });
    canvas.addEventListener('touchmove', function (e) {
        e.preventDefault();
    }, { passive: false });

    function spawnPipe() {
        const minTop = H * 0.13;
        const maxTop = GROUND_Y - PIPE_GAP - (H * 0.13);
        const topH = Math.floor(Math.random() * (maxTop - minTop) + minTop);
        pipes.push({ x: W + PIPE_W, topH: topH, passed: false });
    }

    function rectsOverlap(ax, ay, aw, ah, bx, by, bw, bh) {
        return ax < bx + bw && ax + aw > bx && ay < by + bh && ay + ah > by;
    }

    function hitsPipe(pipe) {
        const size = H * 0.066;
        const bx = bird.x - size / 2, by = bird.y - size / 2;
        const safeW = PIPE_W - (W * 0.012);

        return rectsOverlap(bx, by, size, size, pipe.x + (W * 0.006), 0, safeW, pipe.topH)
            || rectsOverlap(bx, by, size, size, pipe.x + (W * 0.006), pipe.topH + PIPE_GAP, safeW, H);
    }

    function die() {
        state = 'dead';
        const finalScore = score;
        deathTimer = setTimeout(function () {
            Livewire.emit('gameOver', finalScore);
        }, 700);
    }

    function update() {
        if (state === 'playing') {
            bird.vy += GRAVITY;
            bird.y += bird.vy;

            const radius = H * 0.033;
            if (bird.y + radius >= GROUND_Y) { bird.y = GROUND_Y - radius; die(); return; }
            if (bird.y - radius <= 0) { bird.y = radius; bird.vy = 0; }

            for (let i = pipes.length - 1; i >= 0; i--) {
                const p = pipes[i];
                p.x -= PIPE_SPEED;
                if (!p.passed && bird.x > p.x + PIPE_W / 2) { p.passed = true; score++; }
                if (hitsPipe(p)) { die(); return; }
                if (p.x + PIPE_W < 0) pipes.splice(i, 1);
            }

            frameCount++;
            if (frameCount % PIPE_INTERVAL === 0) spawnPipe();
            groundOffset = (groundOffset + PIPE_SPEED) % (W * 0.037);

        } else if (state === 'idle') {
            bird.y = H / 2 + Math.sin(Date.now() / 350) * (H * 0.033);
        }
    }

    function drawPipe(pipe) {
        const CAP_H = H * 0.05, CAP_EXTRA = W * 0.01;
        ctx.fillStyle = '#5ea832';
        ctx.fillRect(pipe.x, 0, PIPE_W, pipe.topH - CAP_H);
        ctx.fillStyle = '#4c8c27';
        ctx.fillRect(pipe.x - CAP_EXTRA, pipe.topH - CAP_H, PIPE_W + CAP_EXTRA * 2, CAP_H);
        ctx.fillStyle = '#4c8c27';
        ctx.fillRect(pipe.x - CAP_EXTRA, pipe.topH + PIPE_GAP, PIPE_W + CAP_EXTRA * 2, CAP_H);
        ctx.fillStyle = '#5ea832';
        ctx.fillRect(pipe.x, pipe.topH + PIPE_GAP + CAP_H, PIPE_W, H);
    }

    function drawBird() {
        const angle = state === 'dead'
            ? Math.PI / 3
            : Math.max(-Math.PI / 6, Math.min(Math.PI / 2.5, bird.vy * (0.09 * (360 / H))));

        const rBase = H * 0.038;

        ctx.save();
        ctx.translate(bird.x, bird.y);
        ctx.rotate(angle);

        ctx.fillStyle = '#f09000';
        ctx.beginPath();
        ctx.moveTo(rBase * 0.85, -rBase * 0.14);
        ctx.lineTo(rBase * 1.5, -rBase * 0.35);
        ctx.lineTo(rBase * 1.5, rBase * 0.14);
        ctx.closePath();
        ctx.fill();
        ctx.strokeStyle = '#c07000';
        ctx.lineWidth = rBase * 0.07;
        ctx.stroke();

        ctx.fillStyle = '#e0579a';
        ctx.beginPath();
        ctx.ellipse(-rBase * 0.35, rBase * 0.28, rBase * 0.71, rBase * 0.35, -0.4, 0, Math.PI * 2);
        ctx.fill();

        ctx.fillStyle = '#ff69b4';
        ctx.beginPath();
        ctx.arc(0, 0, rBase, 0, Math.PI * 2);
        ctx.fill();
        ctx.strokeStyle = '#cc3a82';
        ctx.lineWidth = rBase * 0.1;
        ctx.stroke();

        ctx.fillStyle = 'white';
        ctx.beginPath();
        ctx.arc(rBase * 0.5, -rBase * 0.35, rBase * 0.42, 0, Math.PI * 2);
        ctx.fill();
        ctx.fillStyle = '#111';
        ctx.beginPath();
        ctx.arc(rBase * 0.64, -rBase * 0.35, rBase * 0.21, 0, Math.PI * 2);
        ctx.fill();
        ctx.fillStyle = 'white';
        ctx.beginPath();
        ctx.arc(rBase * 0.71, -rBase * 0.46, rBase * 0.08, 0, Math.PI * 2);
        ctx.fill();

        ctx.restore();
    }

    function draw() {
        const sky = ctx.createLinearGradient(0, 0, 0, GROUND_Y);
        sky.addColorStop(0, '#5bbcd6');
        sky.addColorStop(1, '#80d0e0');
        ctx.fillStyle = sky;
        ctx.fillRect(0, 0, W, GROUND_Y);

        for (const p of pipes) drawPipe(p);

        ctx.fillStyle = '#ded895';
        ctx.fillRect(0, GROUND_Y, W, H - GROUND_Y);
        ctx.fillStyle = '#8fcc55';
        ctx.fillRect(0, GROUND_Y, W, H * 0.06);

        ctx.strokeStyle = 'rgba(0,0,0,0.08)';
        ctx.lineWidth = 1;
        const step = W * 0.037;
        for (let i = -1; i < W / step + 2; i++) {
            const x = (i * step - groundOffset + W * 2) % (W + step);
            ctx.beginPath();
            ctx.moveTo(x - step / 2, GROUND_Y + (H * 0.06));
            ctx.lineTo(x + step / 2, GROUND_Y);
            ctx.stroke();
        }

        drawBird();

        if (state === 'playing' || state === 'dead') {
            ctx.font = `bold ${Math.floor(H * 0.12)}px system-ui, sans-serif`;
            ctx.textAlign = 'center';
            ctx.lineWidth = Math.max(2, H * 0.011);
            ctx.strokeStyle = 'rgba(0,0,0,0.4)';
            ctx.strokeText(score, W / 2, H * 0.14);
            ctx.fillStyle = 'white';
            ctx.fillText(score, W / 2, H * 0.14);
        }

        if (state === 'idle') {
            const fontSize = Math.max(12, Math.floor(H * 0.05));
            ctx.font = `bold ${fontSize}px system-ui, sans-serif`;
            const textW = ctx.measureText(textStart).width;
            const padX = W * 0.04, padY = H * 0.025;
            const boxW = textW + padX * 2;
            const boxH = fontSize + padY * 2;
            const boxX = W / 2 - boxW / 2;
            const boxY = H / 2 + (H * 0.04);
            ctx.fillStyle = 'rgba(0,0,0,0.45)';
            roundRect(boxX, boxY, boxW, boxH, 10);
            ctx.fillStyle = 'white';
            ctx.textAlign = 'center';
            ctx.fillText(textStart, W / 2, boxY + padY + fontSize * 0.85);
        }
    }

    function roundRect(x, y, w, h, r) {
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.lineTo(x + w - r, y);
        ctx.quadraticCurveTo(x + w, y, x + w, y + r);
        ctx.lineTo(x + w, y + h - r);
        ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
        ctx.lineTo(x + r, y + h);
        ctx.quadraticCurveTo(x, y + h, x, y + h - r);
        ctx.lineTo(x, y + r);
        ctx.quadraticCurveTo(x, y, x + r, y);
        ctx.closePath();
        ctx.fill();
    }

    function resizeCanvas() {
        const container = document.getElementById('game-container');
        if (!container) return;

        const targetWidth = container.clientWidth;
        const targetHeight = container.clientHeight;

        const portrait = targetHeight > targetWidth;
        const aspectW = portrait ? 9 : 16;
        const aspectH = portrait ? 16 : 9;

        let finalW = targetWidth;
        let finalH = targetWidth * (aspectH / aspectW);

        if (finalH > targetHeight) {
            finalH = targetHeight;
            finalW = targetHeight * (aspectW / aspectH);
        }

        W = Math.floor(finalW);
        H = Math.floor(finalH);

        const dpr = window.devicePixelRatio || 1;
        canvas.width = W * dpr;
        canvas.height = H * dpr;
        canvas.style.width = W + 'px';
        canvas.style.height = H + 'px';
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

        updateScales();
        if (state === 'idle') reset();
    }

    updateScales();
    reset();
    resizeCanvas();

    window.addEventListener('resize', resizeCanvas);
    window.addEventListener('load', resizeCanvas);

    document.addEventListener('livewire:load', function () {
        resizeCanvas();
    });
    document.addEventListener('livewire:navigated', function () {
        resizeCanvas();
    });

    function loop() {
        update();
        draw();
        requestAnimationFrame(loop);
    }

    requestAnimationFrame(loop);

    window.addEventListener('game-restart', function () {
        reset();
    });
})();
