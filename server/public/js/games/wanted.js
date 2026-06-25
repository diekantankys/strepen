(function () {
    function initWantedGame() {
        const canvas = document.getElementById("wanted-canvas");
        if (!canvas || canvas._wantedRunning) return;
        canvas._wantedRunning = true;

        const ctx = canvas.getContext("2d");
        const facesData = JSON.parse(canvas.dataset.faces || "[]");
        const textStart =
            canvas.dataset.textStart || "Press SPACE or tap to start";
        const textNoFaces = canvas.dataset.textNoFaces || "No faces available";
        const textWanted = canvas.dataset.textWanted || "WANTED";
        const textScore = canvas.dataset.textScore || "Score";
        const textLives = canvas.dataset.textLives || "Lives";
        const textTime = canvas.dataset.textTime || "Time";

        const MAX_LIVES = 3;
        const START_REVEAL_SECS = 3.2;
        const MIN_REVEAL_SECS = 0.85;
        const REVEAL_STEP_SECS = 0.18;
        const GAME_TIME = 90;
        const MAX_GAME_TIME = 130;
        const CORRECT_TIME_BONUS = 3;
        const WRONG_FRAMES = 25;
        const MIN_FLOATERS = 5;
        const MAX_FLOATERS = 56;
        const LEVEL_SIZE = 4;

        let W = 0,
            H = 0;
        let dpr = 1;
        let state = "idle"; // idle | revealing | playing | ended
        let score = 0;
        let lives = MAX_LIVES;
        let timeLeft = GAME_TIME;
        let targetIndex = 0;
        let revealTimer = START_REVEAL_SECS;
        let roundStyle = "bounce";
        let spawnOrder = 0;
        let floaters = [];
        let drawOrder = [];
        let images = [];
        let animFrame = null;
        let lastTime = null;
        let stageCache = null;

        // ── resize ────────────────────────────────────────────────────────────────
        function resize() {
            if (!canvas.isConnected || !canvas.parentElement) return;
            W = canvas.parentElement.clientWidth;
            H = canvas.parentElement.clientHeight;
            dpr = Math.max(1, Math.min(window.devicePixelRatio || 1, 3));
            canvas.width = Math.round(W * dpr);
            canvas.height = Math.round(H * dpr);
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            stageCache = null;
        }

        // ── preload ───────────────────────────────────────────────────────────────
        function preloadImages(cb) {
            if (!facesData.length) {
                cb();
                return;
            }
            let n = 0;
            images = facesData.map(function (f) {
                const img = new Image();
                img.crossOrigin = "anonymous";
                img.onload = img.onerror = function () {
                    if (++n === facesData.length) cb();
                };
                img.src = f.url;
                return img;
            });
        }

        // ── difficulty helpers ────────────────────────────────────────────────────
        function faceR() {
            const shrink = Math.min(0.032, currentLevel() * 0.0045);
            return Math.floor(Math.min(W, H) * (0.09 - shrink));
        }

        function currentLevel() {
            return Math.floor(score / LEVEL_SIZE);
        }

        function currentRevealSecs() {
            return Math.max(
                MIN_REVEAL_SECS,
                START_REVEAL_SECS - score * REVEAL_STEP_SECS,
            );
        }

        function currentFloatCount() {
            const elapsed = Math.max(0, GAME_TIME - timeLeft);
            return Math.min(
                MIN_FLOATERS +
                    score * 2 +
                    currentLevel() * 4 +
                    Math.floor(elapsed / 12),
                MAX_FLOATERS,
            );
        }

        function currentSpeed() {
            const elapsedRatio = Math.min(
                1,
                (GAME_TIME - timeLeft) / GAME_TIME,
            );
            return 58 + currentLevel() * 12 + elapsedRatio * 44;
        }

        function availableRoundStyles() {
            const styles = ["drift", "wave"];
            const level = currentLevel();
            if (level >= 1) styles.push("zigzag", "snake");
            if (level >= 2) styles.push("bounce", "scatter");
            if (level >= 3) styles.push("horizontal", "vertical", "orbit");
            if (level >= 4) styles.push("diagonal", "rain", "boomerang");
            if (level >= 5) styles.push("swirl", "spiral");
            if (level >= 6) styles.push("dash", "jitter");
            if (level >= 7) styles.push("vortex", "chaos");
            if (level >= 9) styles.push("madness");
            return styles;
        }

        function chooseRoundStyle() {
            const styles = availableRoundStyles();
            return styles[Math.floor(Math.random() * styles.length)];
        }

        function randomMode(i) {
            const lateExtras =
                currentLevel() >= 6
                    ? [
                          "wave",
                          "zigzag",
                          "orbit",
                          "dash",
                          "snake",
                          "jitter",
                          "spiral",
                      ]
                    : [];
            const styleModes = {
                drift: ["drift", "drift", "wave"],
                horizontal: ["horizontal", "horizontal", "wave"],
                vertical: ["vertical", "vertical", "zigzag"],
                diagonal: ["diagonal", "diagonal", "wave", "dash"],
                bounce: ["bounce", "bounce", "wave"],
                snake: ["snake", "snake", "horizontal", "wave"],
                rain: ["rain", "rain", "vertical", "diagonal"],
                boomerang: ["boomerang", "boomerang", "dash", "wave"],
                spiral: ["spiral", "spiral", "orbit", "swirl"],
                jitter: ["jitter", "jitter", "dash", "zigzag"],
                vortex: ["vortex", "vortex", "spiral", "orbit", "swirl"],
                scatter: ["bounce", "zigzag", "wave"],
                wave: ["wave", "wave", "drift"],
                zigzag: ["zigzag", "zigzag", "dash", "wave"],
                orbit: ["orbit", "orbit", "wave", "bounce"],
                swirl: ["swirl", "orbit", "wave", "zigzag"],
                dash: ["dash", "dash", "zigzag", "bounce"],
                chaos: [
                    "bounce",
                    "wave",
                    "zigzag",
                    "orbit",
                    "swirl",
                    "dash",
                ].concat(lateExtras),
                madness: [
                    "horizontal",
                    "vertical",
                    "diagonal",
                    "snake",
                    "rain",
                    "boomerang",
                    "spiral",
                    "jitter",
                    "vortex",
                    "dash",
                ].concat(lateExtras),
            };
            const modes = styleModes[roundStyle] || styleModes.bounce;
            if (i === 0 && roundStyle !== "chaos" && roundStyle !== "madness")
                return roundStyle === "scatter" ? "zigzag" : roundStyle;
            return modes[Math.floor(Math.random() * modes.length)];
        }

        // ── spawn floaters ────────────────────────────────────────────────────────
        function spawnFloaters() {
            const r = faceR();
            const count = currentFloatCount();
            const speed = currentSpeed();
            roundStyle = chooseRoundStyle();
            spawnOrder = 0;
            floaters = [];

            floaters.push(makeFloater(targetIndex, r, speed, 0));

            for (let i = 1; i < count; i++) {
                let idx;
                do {
                    idx = Math.floor(Math.random() * facesData.length);
                } while (idx === targetIndex && facesData.length > 1);
                floaters.push(makeFloater(idx, r, speed, i));
            }

            for (let i = floaters.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [floaters[i], floaters[j]] = [floaters[j], floaters[i]];
            }

            floaters.forEach(function (f, i) {
                f.z = i + Math.random() * 0.85;
            });
            drawOrder = floaters.slice().sort(function (a, b) {
                return a.z - b.z;
            });
        }

        function makeFloater(idx, r, speed, i) {
            const angle = Math.random() * Math.PI * 2;
            const s = speed * (0.64 + Math.random() * 0.58);
            const mode = randomMode(i);
            const orbitR = r * (1.25 + Math.random() * 0.75);
            const start = startingMotion(mode, r, s);
            const order = spawnOrder++;
            return {
                idx: idx,
                x: start.x,
                y: start.y,
                cx: start.x,
                cy: start.y,
                vx: start.vx || Math.cos(angle) * s,
                vy: start.vy || Math.sin(angle) * s,
                r: r,
                z: order + Math.random() * 0.85,
                mode: mode,
                speedBase: s,
                drift:
                    (Math.random() < 0.5 ? -1 : 1) *
                    (0.28 + Math.random() * 0.9),
                age: Math.random() * 10,
                phase: Math.random() * Math.PI * 2,
                pulse: 0.92 + Math.random() * 0.18,
                waveAmp: r * (0.38 + Math.random() * 0.42),
                waveFreq: 2.0 + Math.random() * 2.0,
                homeX: start.x,
                homeY: start.y,
                snakeAmp: r * (0.75 + Math.random() * 0.95),
                snakeFreq: 2.4 + Math.random() * 2.6,
                jitterAmp: r * (0.55 + Math.random() * 0.9),
                spiralRadius: r * (1.15 + Math.random() * 2.35),
                spiralGrow:
                    (Math.random() < 0.5 ? -1 : 1) *
                    r *
                    (0.2 + Math.random() * 0.25),
                vortexPull: 0.28 + Math.random() * 0.55,
                orbitAngle: Math.random() * Math.PI * 2,
                orbitRadius: orbitR,
                orbitSpeed:
                    (Math.random() < 0.5 ? -1 : 1) *
                    (1.35 + Math.random() * 1.55),
                turnTimer: 0.34 + Math.random() * 0.72,
                dashTimer: 0.32 + Math.random() * 0.78,
                wrongFlash: 0,
            };
        }

        function startingMotion(mode, r, speed) {
            const margin = r * (1.5 + Math.random() * 2.4);
            let x = r + Math.random() * Math.max(1, W - r * 2);
            let y = r + Math.random() * Math.max(1, H - r * 2);
            let vx = 0;
            let vy = 0;

            if (mode === "horizontal") {
                const dir = Math.random() < 0.5 ? -1 : 1;
                x = dir > 0 ? -margin : W + margin;
                y = r + Math.random() * Math.max(1, H - r * 2);
                vx = dir * speed;
                vy = (Math.random() - 0.5) * speed * 0.2;
            } else if (mode === "vertical") {
                const dir = Math.random() < 0.5 ? -1 : 1;
                x = r + Math.random() * Math.max(1, W - r * 2);
                y = dir > 0 ? -margin : H + margin;
                vx = (Math.random() - 0.5) * speed * 0.2;
                vy = dir * speed;
            } else if (mode === "diagonal" || mode === "rain") {
                const dirX = Math.random() < 0.5 ? -1 : 1;
                const dirY = mode === "rain" ? 1 : Math.random() < 0.5 ? -1 : 1;
                x = dirX > 0 ? -margin : W + margin;
                y = mode === "rain" ? -margin : dirY > 0 ? -margin : H + margin;
                vx =
                    dirX *
                    speed *
                    (mode === "rain" ? 0.25 : 0.82 + Math.random() * 0.34);
                vy =
                    dirY *
                    speed *
                    (mode === "rain"
                        ? 1.18 + Math.random() * 0.45
                        : 0.82 + Math.random() * 0.34);
            } else if (mode === "snake") {
                const dir = Math.random() < 0.5 ? -1 : 1;
                x = dir > 0 ? -margin : W + margin;
                y = r + Math.random() * Math.max(1, H - r * 2);
                vx = dir * speed * (0.9 + Math.random() * 0.35);
                vy = 0;
            } else if (mode === "boomerang") {
                const dir = Math.random() < 0.5 ? -1 : 1;
                x = dir > 0 ? -margin : W + margin;
                y = r + Math.random() * Math.max(1, H - r * 2);
                vx = dir * speed * 1.18;
                vy = (Math.random() - 0.5) * speed * 0.35;
            } else if (mode === "spiral" || mode === "vortex") {
                const a = Math.random() * Math.PI * 2;
                x = W / 2 + Math.cos(a) * Math.min(W, H) * 0.12;
                y = H / 2 + Math.sin(a) * Math.min(W, H) * 0.12;
                vx = Math.cos(a + Math.PI / 2) * speed * 0.5;
                vy = Math.sin(a + Math.PI / 2) * speed * 0.5;
            } else if (mode === "jitter") {
                x = r + Math.random() * Math.max(1, W - r * 2);
                y = r + Math.random() * Math.max(1, H - r * 2);
                vx = (Math.random() - 0.5) * speed * 1.25;
                vy = (Math.random() - 0.5) * speed * 1.25;
            }

            return { x: x, y: y, vx: vx, vy: vy };
        }

        // ── game flow ─────────────────────────────────────────────────────────────
        function startGame() {
            score = 0;
            lives = MAX_LIVES;
            timeLeft = GAME_TIME;
            targetIndex = Math.floor(Math.random() * facesData.length);
            beginReveal();
        }

        function beginReveal() {
            revealTimer = currentRevealSecs();
            state = "revealing";
        }

        function beginPlaying() {
            spawnFloaters();
            state = "playing";
        }

        function endGame() {
            if (state === "ended") return;
            state = "ended";
            Livewire.dispatch("gameOver", { score: score });
        }

        // ── input ─────────────────────────────────────────────────────────────────
        function handleTap(cx, cy) {
            if (state === "idle") {
                if (facesData.length >= 2) startGame();
                return;
            }
            if (state !== "playing") return;

            let hit = null;
            for (let i = 0; i < floaters.length; i++) {
                const f = floaters[i];
                const dx = cx - f.x,
                    dy = cy - f.y;
                if (dx * dx + dy * dy <= (f.r + 4) * (f.r + 4)) {
                    if (!hit || f.z > hit.z) hit = f;
                }
            }

            if (!hit) return;

            if (hit.idx === targetIndex) {
                score++;
                timeLeft = Math.min(
                    MAX_GAME_TIME,
                    timeLeft + CORRECT_TIME_BONUS,
                );
                const prev = targetIndex;
                if (facesData.length > 1) {
                    do {
                        targetIndex = Math.floor(
                            Math.random() * facesData.length,
                        );
                    } while (targetIndex === prev);
                }
                beginReveal();
            } else {
                hit.wrongFlash = WRONG_FRAMES;
                lives--;
                if (lives <= 0) endGame();
            }
        }

        canvas.addEventListener("click", function (e) {
            const rect = canvas.getBoundingClientRect();
            handleTap(
                (e.clientX - rect.left) * (W / rect.width),
                (e.clientY - rect.top) * (H / rect.height),
            );
        });

        canvas.addEventListener(
            "touchstart",
            function (e) {
                e.preventDefault();
                const rect = canvas.getBoundingClientRect();
                const t = e.changedTouches[0];
                handleTap(
                    (t.clientX - rect.left) * (W / rect.width),
                    (t.clientY - rect.top) * (H / rect.height),
                );
            },
            { passive: false },
        );

        document.addEventListener("keydown", function (e) {
            if (!canvas.isConnected) return;
            if (
                (e.code === "Space" || e.code === "Enter") &&
                state === "idle"
            ) {
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
                ctx.fillStyle = "#888";
                ctx.fill();
            }
            ctx.restore();
        }

        function drawDarkStage() {
            if (!stageCache) {
                stageCache = document.createElement("canvas");
                stageCache.width = Math.round(W * dpr);
                stageCache.height = Math.round(H * dpr);
                const stageCtx = stageCache.getContext("2d");
                stageCtx.setTransform(dpr, 0, 0, dpr, 0, 0);
                drawDarkStageTo(stageCtx);
            }
            ctx.drawImage(stageCache, 0, 0, W, H);
        }

        function drawDarkStageTo(stageCtx) {
            const bg = stageCtx.createLinearGradient(0, 0, W, H);
            bg.addColorStop(0, "#05060d");
            bg.addColorStop(0.45, "#0b1020");
            bg.addColorStop(1, "#150812");
            stageCtx.fillStyle = bg;
            stageCtx.fillRect(0, 0, W, H);

            stageCtx.save();
            stageCtx.globalAlpha = 0.16;
            stageCtx.strokeStyle = "#2d3750";
            stageCtx.lineWidth = 1;
            const gap = Math.max(28, Math.floor(Math.min(W, H) * 0.07));
            for (let x = Math.floor(-W / gap) * gap; x < W * 2; x += gap) {
                stageCtx.beginPath();
                stageCtx.moveTo(x, 0);
                stageCtx.lineTo(x + W * 0.55, H);
                stageCtx.stroke();
            }
            for (let y = 0; y < H; y += gap) {
                stageCtx.beginPath();
                stageCtx.moveTo(0, y);
                stageCtx.lineTo(W, y + H * 0.12);
                stageCtx.stroke();
            }
            stageCtx.restore();

            const vignette = stageCtx.createRadialGradient(
                W / 2,
                H / 2,
                Math.min(W, H) * 0.18,
                W / 2,
                H / 2,
                Math.max(W, H) * 0.68,
            );
            vignette.addColorStop(0, "rgba(0,0,0,0)");
            vignette.addColorStop(0.65, "rgba(0,0,0,0.22)");
            vignette.addColorStop(1, "rgba(0,0,0,0.72)");
            stageCtx.fillStyle = vignette;
            stageCtx.fillRect(0, 0, W, H);
        }

        function roundedRect(x, y, w, h, r) {
            const rr = Math.min(r, w / 2, h / 2);
            ctx.beginPath();
            ctx.moveTo(x + rr, y);
            ctx.lineTo(x + w - rr, y);
            ctx.quadraticCurveTo(x + w, y, x + w, y + rr);
            ctx.lineTo(x + w, y + h - rr);
            ctx.quadraticCurveTo(x + w, y + h, x + w - rr, y + h);
            ctx.lineTo(x + rr, y + h);
            ctx.quadraticCurveTo(x, y + h, x, y + h - rr);
            ctx.lineTo(x, y + rr);
            ctx.quadraticCurveTo(x, y, x + rr, y);
        }

        function drawBubble(f, rr) {
            ctx.save();
            ctx.shadowColor = "rgba(20,28,42,0.4)";
            ctx.shadowBlur = 10;
            ctx.fillStyle = "rgba(40,112,154,0.16)";
            ctx.beginPath();
            ctx.arc(f.x, f.y, rr + 5, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();

            ctx.strokeStyle = "rgba(128,202,240,0.48)";
            ctx.lineWidth = 2.5;
            ctx.beginPath();
            ctx.arc(f.x, f.y, rr + 3, 0, Math.PI * 2);
            ctx.stroke();

            drawFace(images[f.idx], f.x, f.y, rr);

            ctx.save();
            ctx.globalAlpha = 0.55;
            ctx.strokeStyle = "rgba(255,255,255,0.62)";
            ctx.lineWidth = Math.max(1.5, rr * 0.05);
            ctx.beginPath();
            ctx.arc(
                f.x - rr * 0.18,
                f.y - rr * 0.22,
                rr * 0.55,
                Math.PI * 1.05,
                Math.PI * 1.55,
            );
            ctx.stroke();
            ctx.restore();
        }

        function drawHud() {
            const fs = Math.max(13, Math.floor(Math.min(W, H) * 0.038));
            ctx.font = `bold ${fs}px sans-serif`;
            ctx.textBaseline = "middle";

            const pad = Math.max(8, Math.floor(fs * 0.55));
            const y = 12;
            const h = fs + pad * 1.45;

            ctx.save();
            ctx.shadowColor = "rgba(0,0,0,0.45)";
            ctx.shadowBlur = 12;
            ctx.fillStyle = "rgba(4,6,13,0.72)";
            roundedRect(10, y, Math.max(92, fs * 5.4), h, 8);
            ctx.fill();
            roundedRect(
                W / 2 - Math.max(72, fs * 4.6) / 2,
                y,
                Math.max(72, fs * 4.6),
                h,
                8,
            );
            ctx.fill();
            roundedRect(
                W - Math.max(88, fs * 4.7) - 10,
                y,
                Math.max(88, fs * 4.7),
                h,
                8,
            );
            ctx.fill();
            ctx.restore();

            // score — left
            ctx.fillStyle = "#ffffff";
            ctx.textAlign = "left";
            ctx.fillText(textScore + ": " + score, 18 + pad * 0.35, y + h / 2);

            // timer — center, colour shifts red as time runs out
            const ratio = Math.min(1, timeLeft / GAME_TIME);
            const r = Math.round(255);
            const g = Math.round(ratio * 220);
            ctx.fillStyle = `rgb(${r},${g},0)`;
            ctx.textAlign = "center";
            ctx.fillText(
                textTime + ": " + Math.ceil(timeLeft),
                W / 2,
                y + h / 2,
            );

            // lives — right (hearts)
            ctx.textAlign = "right";
            let hearts = "";
            for (let i = 0; i < MAX_LIVES; i++)
                hearts += (i < lives ? "♥" : "♡") + " ";
            ctx.fillStyle = "#ff4b55";
            ctx.fillText(hearts.trim(), W - 18 - pad * 0.35, y + h / 2);
            ctx.textBaseline = "alphabetic";
        }

        // ── draw states ───────────────────────────────────────────────────────────
        function drawIdle() {
            drawDarkStage();
            const fs = Math.max(14, Math.floor(Math.min(W, H) * 0.05));
            ctx.font = `bold ${fs}px sans-serif`;
            ctx.textAlign = "center";
            ctx.shadowColor = "rgba(255,211,90,0.35)";
            ctx.shadowBlur = 16;
            if (facesData.length < 2) {
                ctx.fillStyle = "#ffffff";
                ctx.fillText(textNoFaces, W / 2, H / 2);
            } else {
                ctx.fillStyle = "#ffd35a";
                ctx.fillText(textStart, W / 2, H / 2);
            }
            ctx.shadowColor = "transparent";
        }

        function drawReveal() {
            drawDarkStage();

            const r = Math.floor(Math.min(W, H) * 0.26);
            const cx = W / 2,
                cy = H / 2;

            const posterW = Math.min(W * 0.72, r * 3.2);
            const posterH = r * 2.95;
            const posterX = cx - posterW / 2;
            const posterY = cy - r - Math.max(46, r * 0.42);

            ctx.save();
            ctx.shadowColor = "rgba(0,0,0,0.45)";
            ctx.shadowBlur = 22;
            ctx.shadowOffsetY = 10;
            ctx.fillStyle = "#17121b";
            ctx.fillRect(posterX, posterY, posterW, posterH);
            ctx.restore();

            const posterGlow = ctx.createLinearGradient(
                posterX,
                posterY,
                posterX + posterW,
                posterY + posterH,
            );
            posterGlow.addColorStop(0, "rgba(255,210,91,0.18)");
            posterGlow.addColorStop(0.5, "rgba(142,32,45,0.16)");
            posterGlow.addColorStop(1, "rgba(54,145,185,0.14)");
            ctx.fillStyle = posterGlow;
            ctx.fillRect(posterX + 8, posterY + 8, posterW - 16, posterH - 16);

            ctx.strokeStyle = "#ba343b";
            ctx.lineWidth = Math.max(3, Math.floor(r * 0.035));
            ctx.strokeRect(
                posterX + 8,
                posterY + 8,
                posterW - 16,
                posterH - 16,
            );

            ctx.strokeStyle = "#ffd35a";
            ctx.lineWidth = 6;
            ctx.beginPath();
            ctx.arc(cx, cy, r + 5, 0, Math.PI * 2);
            ctx.stroke();

            drawFace(images[targetIndex], cx, cy, r);

            const titleFs = Math.max(22, Math.floor(Math.min(W, H) * 0.075));
            const titleY = posterY + titleFs + 18;
            ctx.font = `900 ${titleFs}px Georgia, serif`;
            ctx.textAlign = "center";
            ctx.textBaseline = "middle";
            ctx.shadowColor = "rgba(255,30,45,0.45)";
            ctx.shadowBlur = 12;
            ctx.shadowOffsetY = 3;
            ctx.lineWidth = Math.max(3, Math.floor(titleFs * 0.12));
            ctx.strokeStyle = "#09070b";
            ctx.strokeText(textWanted, cx, titleY);
            const titleFill = ctx.createLinearGradient(
                0,
                titleY - titleFs / 2,
                0,
                titleY + titleFs / 2,
            );
            titleFill.addColorStop(0, "#ffef9c");
            titleFill.addColorStop(0.45, "#e93c2f");
            titleFill.addColorStop(1, "#911b1c");
            ctx.fillStyle = titleFill;
            ctx.fillText(textWanted, cx, titleY);
            ctx.shadowColor = "transparent";
            ctx.textBaseline = "alphabetic";

            const nameFs = Math.max(12, Math.floor(Math.min(W, H) * 0.038));
            ctx.font = `bold ${nameFs}px sans-serif`;
            ctx.fillStyle = "#f6d984";
            ctx.textAlign = "center";
            ctx.fillText(facesData[targetIndex].name, cx, cy + r + nameFs + 10);

            const countFs = Math.max(20, Math.floor(Math.min(W, H) * 0.07));
            ctx.font = `bold ${countFs}px sans-serif`;
            ctx.fillStyle = "rgba(255,255,255,0.55)";
            ctx.textAlign = "center";
            ctx.fillText(
                Math.ceil(revealTimer),
                cx,
                cy + r + nameFs + countFs + 18,
            );

            drawHud();
        }

        function drawPlaying() {
            drawDarkStage();

            drawOrder.forEach(function (f) {
                const wobble =
                    1 + Math.sin(f.age * 5.5 + f.phase) * 0.05 * f.pulse;
                const rr = f.r * wobble;
                drawBubble(f, rr);
            });

            drawHud();
        }

        function drawEnded() {
            drawDarkStage();
            drawHud();
        }

        function bounceInBounds(f, useCenter) {
            const propX = useCenter ? "cx" : "x";
            const propY = useCenter ? "cy" : "y";
            const edgeR = useCenter ? f.r + f.orbitRadius : f.r;

            if (f[propX] - edgeR < 0) {
                f[propX] = edgeR;
                f.vx = Math.abs(f.vx);
            }
            if (f[propX] + edgeR > W) {
                f[propX] = W - edgeR;
                f.vx = -Math.abs(f.vx);
            }
            if (f[propY] - edgeR < 0) {
                f[propY] = edgeR;
                f.vy = Math.abs(f.vy);
            }
            if (f[propY] + edgeR > H) {
                f[propY] = H - edgeR;
                f.vy = -Math.abs(f.vy);
            }
        }

        function wrapOffscreen(f) {
            const margin = f.r * 2.4;
            if (f.vx > 0 && f.x - f.r > W + margin) {
                f.x = -margin;
                f.y = Math.max(
                    -margin,
                    Math.min(
                        H + margin,
                        f.y + (Math.random() - 0.5) * f.r * 3.5,
                    ),
                );
            } else if (f.vx < 0 && f.x + f.r < -margin) {
                f.x = W + margin;
                f.y = Math.max(
                    -margin,
                    Math.min(
                        H + margin,
                        f.y + (Math.random() - 0.5) * f.r * 3.5,
                    ),
                );
            }

            if (f.vy > 0 && f.y - f.r > H + margin) {
                f.y = -margin;
                f.x = Math.max(
                    -margin,
                    Math.min(
                        W + margin,
                        f.x + (Math.random() - 0.5) * f.r * 3.5,
                    ),
                );
            } else if (f.vy < 0 && f.y + f.r < -margin) {
                f.y = H + margin;
                f.x = Math.max(
                    -margin,
                    Math.min(
                        W + margin,
                        f.x + (Math.random() - 0.5) * f.r * 3.5,
                    ),
                );
            }
        }

        function isTravelMode(f) {
            return (
                f.mode === "horizontal" ||
                f.mode === "vertical" ||
                f.mode === "diagonal" ||
                f.mode === "snake" ||
                f.mode === "rain"
            );
        }

        function rotateVelocity(f, amount) {
            const cos = Math.cos(amount);
            const sin = Math.sin(amount);
            const vx = f.vx;
            const vy = f.vy;
            f.vx = vx * cos - vy * sin;
            f.vy = vx * sin + vy * cos;
        }

        function limitVelocity(f) {
            const max = currentSpeed() * 2.25;
            const len = Math.hypot(f.vx, f.vy);
            if (len <= max) return;
            f.vx = (f.vx / len) * max;
            f.vy = (f.vy / len) * max;
        }

        function updateFloater(f, dt) {
            f.age += dt;
            if (f.wrongFlash > 0) f.wrongFlash--;

            if (f.mode === "scatter") {
                f.turnTimer -= dt;
                if (f.turnTimer <= 0) {
                    rotateVelocity(f, (Math.random() - 0.5) * 1.05);
                    f.turnTimer = 0.26 + Math.random() * 0.5;
                }
            }

            if (f.mode === "orbit") {
                f.cx += f.vx * dt;
                f.cy += f.vy * dt;
                bounceInBounds(f, true);
                f.orbitAngle += f.orbitSpeed * dt;
                f.x = f.cx + Math.cos(f.orbitAngle) * f.orbitRadius;
                f.y = f.cy + Math.sin(f.orbitAngle) * f.orbitRadius;
                return;
            }

            if (f.mode === "swirl") {
                rotateVelocity(f, f.drift * dt);
                f.x += Math.cos(f.age * 4.3 + f.phase) * f.waveAmp * 0.7 * dt;
                f.y += Math.sin(f.age * 3.7 + f.phase) * f.waveAmp * 0.7 * dt;
            }

            if (f.mode === "zigzag") {
                f.turnTimer -= dt;
                if (f.turnTimer <= 0) {
                    rotateVelocity(
                        f,
                        (Math.random() < 0.5 ? -1 : 1) *
                            (0.42 + Math.random() * 0.34),
                    );
                    f.turnTimer = 0.4 + Math.random() * 0.65;
                }
            }

            if (f.mode === "dash") {
                f.dashTimer -= dt;
                if (f.dashTimer <= 0) {
                    const boost = 1.18 + Math.random() * 0.34;
                    f.vx *= boost;
                    f.vy *= boost;
                    limitVelocity(f);
                    f.dashTimer = 0.72 + Math.random() * 1.2;
                } else {
                    f.vx *= 0.992;
                    f.vy *= 0.992;
                }
            }

            if (f.mode === "boomerang") {
                f.turnTimer -= dt;
                if (f.turnTimer <= 0) {
                    f.vx *= -1.03;
                    f.vy += (Math.random() - 0.5) * f.speedBase * 0.28;
                    limitVelocity(f);
                    f.turnTimer = 0.82 + Math.random() * 0.9;
                }
            }

            if (f.mode === "jitter") {
                f.vx += (Math.random() - 0.5) * f.jitterAmp * 11 * dt;
                f.vy += (Math.random() - 0.5) * f.jitterAmp * 11 * dt;
                rotateVelocity(f, Math.sin(f.age * 16 + f.phase) * 1.15 * dt);
                limitVelocity(f);
            }

            if (f.mode === "spiral") {
                f.orbitAngle += f.orbitSpeed * dt;
                f.spiralRadius += f.spiralGrow * dt;
                if (
                    f.spiralRadius > Math.min(W, H) * 0.48 ||
                    f.spiralRadius < f.r * 0.9
                ) {
                    f.spiralGrow *= -1;
                }
                f.x =
                    W / 2 +
                    Math.cos(f.orbitAngle + f.phase) * f.spiralRadius +
                    Math.cos(f.age * 2.7) * f.r;
                f.y =
                    H / 2 +
                    Math.sin(f.orbitAngle + f.phase) * f.spiralRadius +
                    Math.sin(f.age * 2.4) * f.r;
                return;
            }

            if (f.mode === "vortex") {
                const dx = W / 2 - f.x;
                const dy = H / 2 - f.y;
                const dist = Math.max(1, Math.hypot(dx, dy));
                f.vx += (dx / dist) * f.speedBase * f.vortexPull * dt;
                f.vy += (dy / dist) * f.speedBase * f.vortexPull * dt;
                rotateVelocity(f, (2.6 + currentLevel() * 0.18) * dt);
                limitVelocity(f);
                if (dist < f.r * 1.6) {
                    const a = Math.random() * Math.PI * 2;
                    f.x = W / 2 + Math.cos(a) * Math.min(W, H) * 0.46;
                    f.y = H / 2 + Math.sin(a) * Math.min(W, H) * 0.46;
                }
            }

            f.x += f.vx * dt;
            f.y += f.vy * dt;

            if (f.mode === "wave") {
                f.x += Math.cos(f.age * f.waveFreq + f.phase) * f.waveAmp * dt;
                f.y +=
                    Math.sin(f.age * f.waveFreq * 0.8 + f.phase) *
                    f.waveAmp *
                    dt;
            }

            if (f.mode === "drift") {
                rotateVelocity(f, Math.sin(f.age * 1.4 + f.phase) * 0.32 * dt);
                f.x += Math.cos(f.age * 1.6 + f.phase) * f.r * 0.28 * dt;
                f.y += Math.sin(f.age * 1.3 + f.phase) * f.r * 0.28 * dt;
            } else if (f.mode === "horizontal") {
                f.y += Math.sin(f.age * f.waveFreq + f.phase) * f.r * 0.9 * dt;
            } else if (f.mode === "vertical") {
                f.x += Math.cos(f.age * f.waveFreq + f.phase) * f.r * 0.9 * dt;
            } else if (f.mode === "diagonal") {
                f.x += Math.cos(f.age * f.waveFreq + f.phase) * f.r * 0.55 * dt;
                f.y += Math.sin(f.age * f.waveFreq + f.phase) * f.r * 0.55 * dt;
            } else if (f.mode === "snake") {
                f.y =
                    f.homeY +
                    Math.sin(f.age * f.snakeFreq + f.phase) * f.snakeAmp +
                    Math.sin(f.age * f.snakeFreq * 0.47 + f.phase) * f.r * 0.75;
            } else if (f.mode === "rain") {
                f.x += Math.sin(f.age * f.waveFreq + f.phase) * f.r * 1.25 * dt;
            }

            if (roundStyle === "chaos" || roundStyle === "madness") {
                rotateVelocity(f, Math.sin(f.age * 2.4 + f.phase) * 0.55 * dt);
            }

            if (roundStyle === "madness") {
                f.vx += Math.cos(f.age * 5.4 + f.phase) * f.r * 3.4 * dt;
                f.vy += Math.sin(f.age * 5.0 + f.phase) * f.r * 3.4 * dt;
                limitVelocity(f);
            }

            if (isTravelMode(f)) {
                wrapOffscreen(f);
                return;
            }

            bounceInBounds(f, false);
        }

        // ── update ────────────────────────────────────────────────────────────────
        function update(dt) {
            if (state === "idle" || state === "ended") return;

            // global countdown always ticks
            timeLeft -= dt;
            if (timeLeft <= 0) {
                timeLeft = 0;
                endGame();
                return;
            }

            if (state === "revealing") {
                revealTimer -= dt;
                if (revealTimer <= 0) beginPlaying();
                return;
            }

            if (state === "playing") {
                floaters.forEach(function (f) {
                    updateFloater(f, dt);
                });
            }
        }

        // ── loop ──────────────────────────────────────────────────────────────────
        function loop(ts) {
            if (!canvas.isConnected) {
                if (animFrame) cancelAnimationFrame(animFrame);
                return;
            }
            animFrame = requestAnimationFrame(loop);
            const dt =
                lastTime === null ? 0 : Math.min((ts - lastTime) / 1000, 0.1);
            lastTime = ts;
            update(dt);

            if (state === "idle") drawIdle();
            else if (state === "revealing") drawReveal();
            else if (state === "playing") drawPlaying();
            else if (state === "ended") drawEnded();
        }

        function restartGame() {
            state = "idle";
            score = 0;
            lives = MAX_LIVES;
            timeLeft = GAME_TIME;
            floaters = [];
            drawOrder = [];
        }

        // ── restart ───────────────────────────────────────────────────────────────
        if (window.Livewire && Livewire.on) {
            Livewire.on("game-restart", restartGame);
        } else {
            document.addEventListener(
                "livewire:initialized",
                function () {
                    Livewire.on("game-restart", restartGame);
                },
                { once: true },
            );
        }

        // ── boot ──────────────────────────────────────────────────────────────────
        window.addEventListener("resize", resize);
        resize();
        preloadImages(function () {
            if (animFrame) cancelAnimationFrame(animFrame);
            lastTime = null;
            loop(performance.now());
        });
    }

    initWantedGame();
    document.addEventListener("livewire:navigated", initWantedGame);
})();
