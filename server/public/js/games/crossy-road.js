(function () {
    if (window._crossyRoadRunning) return;
    window._crossyRoadRunning = true;

    var container = document.getElementById('game-container');
    if (!container) return;

    // --- Constants ---
    var TILE       = 1;
    var WORLD_HALF = 6;
    var HOP_DUR    = 0.18;
    var HOP_H      = 0.5;
    var SWIPE_MIN  = 25;
    var RESTING_Y  = 0.5;
    var LOOK_AHEAD = 20;
    var GROUND_W   = 60;  // ground wider than any screen extent so terrain fills edge-to-edge
    var IDLE_WARN  = 2;  // seconds idle before countdown appears
    var IDLE_LIMIT = 7;  // total seconds idle before death (2s grace + 5s countdown)

    var CAR_COLORS   = [0xe53935, 0x1e88e5, 0xfdd835, 0xfb8c00, 0x8e24aa, 0x00897b];
    var TRUCK_COLORS = [0xf57f17, 0x33691e, 0x880e4f, 0x0d47a1, 0x424242];

    // --- Three.js setup ---
    var scene = new THREE.Scene();
    scene.background = new THREE.Color(0xd4a55a);
    scene.fog = new THREE.Fog(0xd4a55a, 30, 80);

    var camera = new THREE.PerspectiveCamera(60, 1, 0.1, 200);
    camera.position.set(0, 8, 10);
    camera.lookAt(0, 0, -2);

    var renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    renderer.domElement.style.display = 'block';
    renderer.domElement.style.width = '100%';
    renderer.domElement.style.height = '100%';
    renderer.domElement.style.touchAction = 'none';
    container.style.touchAction = 'none';
    container.appendChild(renderer.domElement);

    var ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
    scene.add(ambientLight);

    var dirLight = new THREE.DirectionalLight(0xffffff, 0.8);
    dirLight.position.set(8, 20, 10);
    dirLight.castShadow = true;
    dirLight.shadow.camera.left = -25;
    dirLight.shadow.camera.right = 25;
    dirLight.shadow.camera.top = 25;
    dirLight.shadow.camera.bottom = -25;
    dirLight.shadow.mapSize.set(1024, 1024);
    scene.add(dirLight);

    // --- Overlays ---
    var scoreEl = document.getElementById('crossy-road-score');
    var startEl = document.getElementById('crossy-road-start');
    var timerEl = document.getElementById('crossy-road-timer');

    // --- World state ---
    var lanes = [];
    var maxGeneratedRow  = 0;
    var consecutiveHazard = 0;

    // --- Game state ---
    var state = 'idle';
    var score = 0;
    var lastTime = null;
    var cameraTargetZ = 10;
    var idleTime = 0;

    // --- Player state ---
    var player = {
        mesh: null,
        gridX: 0,
        rowIndex: 0,
        isHopping: false,
        hopProgress: 0,
        startX: 0,
        startZ: 0,
        targetX: 0,
        targetZ: 0,
        alive: true,
        deathAngle: 0,
        onLog: null,
    };

    var pendingMove = null;

    // --- Difficulty: 0.0 (row 0) -> 1.0 (row 60+) ---
    function difficulty(rowIndex) {
        return Math.min(1, rowIndex / 60);
    }

    // --- Build player mesh (beer crate + bottles) ---
    function buildPlayerMesh() {
        var group = new THREE.Group();
        var crateMat = new THREE.MeshToonMaterial({ color: 0x8B5E3C });
        var crateBody = new THREE.Mesh(new THREE.BoxGeometry(0.85, 0.65, 0.85), crateMat);
        crateBody.castShadow = true;
        group.add(crateBody);

        var slatMat = new THREE.MeshToonMaterial({ color: 0x5C3317 });
        var hSlat1 = new THREE.Mesh(new THREE.BoxGeometry(0.87, 0.05, 0.87), slatMat);
        hSlat1.position.y = 0.1;
        group.add(hSlat1);
        var hSlat2 = new THREE.Mesh(new THREE.BoxGeometry(0.87, 0.05, 0.87), slatMat);
        hSlat2.position.y = -0.1;
        group.add(hSlat2);
        var vSlat = new THREE.Mesh(new THREE.BoxGeometry(0.05, 0.67, 0.87), slatMat);
        group.add(vSlat);

        var bottlePositions = [
            [-0.22, -0.22], [-0.22, 0], [-0.22, 0.22],
            [ 0.22, -0.22], [ 0.22, 0], [ 0.22, 0.22],
        ];
        var bottleMat = new THREE.MeshToonMaterial({ color: 0x388E3C });
        var capMat    = new THREE.MeshToonMaterial({ color: 0xFFC107 });
        for (var bi = 0; bi < bottlePositions.length; bi++) {
            var bx = bottlePositions[bi][0];
            var bz = bottlePositions[bi][1];
            var bottle = new THREE.Mesh(new THREE.BoxGeometry(0.14, 0.32, 0.14), bottleMat);
            bottle.position.set(bx, 0.49, bz);
            bottle.castShadow = true;
            group.add(bottle);
            var cap = new THREE.Mesh(new THREE.BoxGeometry(0.10, 0.06, 0.10), capMat);
            cap.position.set(bx, 0.66, bz);
            group.add(cap);
        }
        return group;
    }

    // --- Build car mesh ---
    function buildCarMesh(width, color) {
        var group = new THREE.Group();
        var bodyMat = new THREE.MeshLambertMaterial({ color: color });
        var darkMat = new THREE.MeshLambertMaterial({ color: 0x222222 });
        var winMat  = new THREE.MeshLambertMaterial({ color: 0x90caf9, transparent: true, opacity: 0.7 });

        var body = new THREE.Mesh(new THREE.BoxGeometry(width, 0.45, 0.82), bodyMat);
        body.position.y = 0.225;
        body.castShadow = true;
        group.add(body);

        var cabin = new THREE.Mesh(new THREE.BoxGeometry(width * 0.55, 0.35, 0.75), winMat);
        cabin.position.set(-width * 0.08, 0.625, 0);
        group.add(cabin);

        var wheelGeo = new THREE.BoxGeometry(0.18, 0.18, 0.18);
        var wx = width / 2 - 0.12;
        [[-wx, -0.42], [-wx, 0.42], [wx, -0.42], [wx, 0.42]].forEach(function (p) {
            var w = new THREE.Mesh(wheelGeo, darkMat);
            w.position.set(p[0], 0.09, p[1]);
            group.add(w);
        });
        return group;
    }

    // --- Build truck mesh (cab + long trailer) ---
    function buildTruckMesh(color) {
        var group = new THREE.Group();
        var cabMat     = new THREE.MeshLambertMaterial({ color: color });
        var trailerMat = new THREE.MeshLambertMaterial({ color: 0x888888 });
        var darkMat    = new THREE.MeshLambertMaterial({ color: 0x222222 });
        var winMat     = new THREE.MeshLambertMaterial({ color: 0x90caf9, transparent: true, opacity: 0.7 });

        var cabW     = 0.9;
        var trailerW = 2.2;

        var trailer = new THREE.Mesh(new THREE.BoxGeometry(trailerW, 0.7, 0.88), trailerMat);
        trailer.position.set(-(cabW / 2 + 0.05), 0.35, 0);
        trailer.castShadow = true;
        group.add(trailer);

        var cab = new THREE.Mesh(new THREE.BoxGeometry(cabW, 0.55, 0.85), cabMat);
        cab.position.set(trailerW / 2 - 0.05, 0.275, 0);
        cab.castShadow = true;
        group.add(cab);

        var win = new THREE.Mesh(new THREE.BoxGeometry(0.15, 0.32, 0.70), winMat);
        win.position.set(trailerW / 2 + cabW / 2 - 0.2, 0.55, 0);
        group.add(win);

        var wheelGeo = new THREE.BoxGeometry(0.2, 0.22, 0.2);
        [
            [trailerW / 2 + 0.1, -0.45], [trailerW / 2 + 0.1, 0.45],
            [-0.3, -0.45], [-0.3, 0.45],
            [-(trailerW / 2) + 0.15, -0.45], [-(trailerW / 2) + 0.15, 0.45],
        ].forEach(function (p) {
            var w = new THREE.Mesh(wheelGeo, darkMat);
            w.position.set(p[0], 0.11, p[1]);
            group.add(w);
        });

        group.userData.width = cabW + trailerW + 0.1;
        return group;
    }

    // --- Build train mesh ---
    function buildTrainMesh() {
        var group = new THREE.Group();
        var bodyMat   = new THREE.MeshLambertMaterial({ color: 0x37474f });
        var stripeMat = new THREE.MeshLambertMaterial({ color: 0xef5350 });
        var winMat    = new THREE.MeshLambertMaterial({ color: 0x90caf9, transparent: true, opacity: 0.6 });
        var darkMat   = new THREE.MeshLambertMaterial({ color: 0x111111 });
        var TRAIN_W   = 9;

        var body = new THREE.Mesh(new THREE.BoxGeometry(TRAIN_W, 0.8, 0.9), bodyMat);
        body.position.y = 0.4;
        body.castShadow = true;
        group.add(body);

        var stripe = new THREE.Mesh(new THREE.BoxGeometry(TRAIN_W, 0.12, 0.92), stripeMat);
        stripe.position.y = 0.62;
        group.add(stripe);

        for (var wi = -3; wi <= 3; wi++) {
            var pane = new THREE.Mesh(new THREE.BoxGeometry(0.01, 0.24, 0.28), winMat);
            pane.position.set(wi * 1.1, 0.72, 0.46);
            group.add(pane);
        }

        var wheelGeo = new THREE.BoxGeometry(0.25, 0.25, 0.25);
        for (var ri = -3; ri <= 3; ri++) {
            [0.48, -0.48].forEach(function (zp) {
                var wm = new THREE.Mesh(wheelGeo, darkMat);
                wm.position.set(ri * 1.2, 0.0, zp);
                group.add(wm);
            });
        }

        group.userData.width = TRAIN_W;
        return group;
    }

    // --- Build log mesh (with lily-pad leaves on top) ---
    function buildLogMesh() {
        var logW    = 2.2 + Math.random() * 1.0;  // wider: 2.2-3.2
        var logMat  = new THREE.MeshLambertMaterial({ color: 0x5D4037 });
        var leafMat = new THREE.MeshLambertMaterial({ color: 0x558B2F });
        var group   = new THREE.Group();

        var body = new THREE.Mesh(new THREE.BoxGeometry(logW, 0.18, 0.8), logMat);
        body.position.y = 0.09;
        body.castShadow = true;
        group.add(body);

        var leaf = new THREE.Mesh(new THREE.BoxGeometry(logW - 0.1, 0.05, 0.86), leafMat);
        leaf.position.y = 0.20;
        group.add(leaf);

        group.userData.width = logW;
        return group;
    }

    // --- Build lane ground mesh ---
    function buildLaneMesh(type) {
        var group = new THREE.Group();
        var groundColor;
        if      (type === 'road')   groundColor = 0x616161;
        else if (type === 'rail')   groundColor = 0x78604a;
        else if (type === 'river')  groundColor = 0x1565C0;
        else                        groundColor = Math.random() < 0.5 ? 0xA0856A : 0x9C7B62;

        var ground = new THREE.Mesh(
            new THREE.BoxGeometry(GROUND_W, 0.2, TILE),
            new THREE.MeshLambertMaterial({ color: groundColor })
        );
        ground.position.y = -0.1;
        ground.receiveShadow = true;
        group.add(ground);

        var EDGE = Math.floor(GROUND_W / 2);
        if (type === 'road') {
            var dashMat = new THREE.MeshLambertMaterial({ color: 0xeeeeee });
            for (var di = -EDGE; di <= EDGE; di += 2) {
                var dash = new THREE.Mesh(new THREE.BoxGeometry(0.6, 0.01, 0.1), dashMat);
                dash.position.set(di, 0.01, 0);
                group.add(dash);
            }
        } else if (type === 'rail') {
            var railMat = new THREE.MeshLambertMaterial({ color: 0x9e9e9e });
            var tieMat  = new THREE.MeshLambertMaterial({ color: 0x4e342e });
            for (var ti = -EDGE; ti <= EDGE; ti++) {
                var tie = new THREE.Mesh(new THREE.BoxGeometry(0.15, 0.07, 0.95), tieMat);
                tie.position.set(ti, 0.02, 0);
                group.add(tie);
            }
            var rail1 = new THREE.Mesh(new THREE.BoxGeometry(GROUND_W, 0.06, 0.08), railMat);
            rail1.position.set(0, 0.06, 0.25);
            group.add(rail1);
            var rail2 = new THREE.Mesh(new THREE.BoxGeometry(GROUND_W, 0.06, 0.08), railMat);
            rail2.position.set(0, 0.06, -0.25);
            group.add(rail2);
        } else if (type === 'river') {
            var shimMat = new THREE.MeshLambertMaterial({ color: 0x1976D2, transparent: true, opacity: 0.85 });
            for (var si2 = -EDGE; si2 <= EDGE; si2++) {
                var shim = new THREE.Mesh(new THREE.BoxGeometry(0.8, 0.01, 0.85), shimMat);
                shim.position.set(si2, 0.01, 0);
                group.add(shim);
            }
        } else {
            // Safe lane: cobblestones only within the walkable zone
            var stoneMat = new THREE.MeshLambertMaterial({ color: 0xBFAA93 });
            for (var si = -WORLD_HALF; si <= WORLD_HALF; si++) {
                var stone = new THREE.Mesh(new THREE.BoxGeometry(0.7, 0.04, 0.7), stoneMat);
                stone.position.set(si, 0.01, 0);
                group.add(stone);
            }
        }
        return group;
    }

    // --- Generate a lane ---
    function generateLane(rowIndex) {
        var diff     = difficulty(rowIndex);
        var maxCons  = diff < 0.4 ? 2 : 3;
        var type;

        if (rowIndex < 3) {
            type = 'safe';
        } else if (consecutiveHazard >= maxCons) {
            type = 'safe';
            consecutiveHazard = 0;
        } else {
            var pSafe  = Math.max(0.18, 0.55 - diff * 0.37);
            var pRoad  = 0.25 + diff * 0.25;
            var pRiver = rowIndex >= 10 ? (0.10 + diff * 0.10) : 0;
            var pRail  = rowIndex >= 20 ? 0.07 : 0;
            var total  = pSafe + pRoad + pRiver + pRail;
            pSafe /= total; pRoad /= total; pRiver /= total; // pRail fills remainder
            var r = Math.random();
            if      (r < pSafe)                       type = 'safe';
            else if (r < pSafe + pRoad)               type = 'road';
            else if (r < pSafe + pRoad + pRiver)      type = 'river';
            else                                      type = 'rail';
        }

        if (type !== 'safe') consecutiveHazard++;
        else consecutiveHazard = 0;

        var lane = {
            type: type,
            rowIndex: rowIndex,
            z: -rowIndex * TILE,
            mesh: buildLaneMesh(type),
            vehicles: [],
            logs: [],
        };

        lane.mesh.position.z = lane.z;
        scene.add(lane.mesh);

        if (type === 'road')  spawnRoadVehicles(lane, diff);
        if (type === 'rail')  spawnTrain(lane, diff);
        if (type === 'river') spawnLogs(lane, diff);

        lanes.push(lane);
        if (rowIndex > maxGeneratedRow) maxGeneratedRow = rowIndex;
    }

    // --- Spawn cars and trucks on road ---
    function spawnRoadVehicles(lane, diff) {
        var dir    = Math.random() < 0.5 ? 1 : -1;
        var minSpd = 1.0 + diff * 2.5;
        var maxSpd = 2.0 + diff * 4.5;
        var speed  = dir * (minSpd + Math.random() * (maxSpd - minSpd));
        // Spread vehicles across the full wrap range (32 units) so density matches
        // what it was when the wrap bound was WORLD_HALF+2 (8).
        var ROAD_RANGE = (WORLD_HALF + 10) * 2;  // 32, same as wrap bound * 2
        var count    = 2 + Math.floor(Math.random() * (2 + Math.floor(diff * 4)));
        var spacing  = ROAD_RANGE / count;
        var useTruck = Math.random() < 0.30;
        var color    = CAR_COLORS[Math.floor(Math.random() * CAR_COLORS.length)];

        for (var i = 0; i < count; i++) {
            var x = -ROAD_RANGE / 2 + spacing * i + Math.random() * spacing * 0.5;
            var mesh, vWidth;
            if (useTruck) {
                mesh   = buildTruckMesh(TRUCK_COLORS[Math.floor(Math.random() * TRUCK_COLORS.length)]);
                vWidth = mesh.userData.width;
            } else {
                vWidth = 1.4 + Math.random() * 0.8;
                mesh   = buildCarMesh(vWidth, color);
            }
            if (dir < 0) mesh.rotation.y = Math.PI;
            mesh.position.set(x, 0, 0);
            lane.mesh.add(mesh);
            lane.vehicles.push({ mesh: mesh, x: x, speed: speed, width: vWidth });
        }
    }

    // --- Spawn a single train on rail ---
    function spawnTrain(lane, diff) {
        var dir   = Math.random() < 0.5 ? 1 : -1;
        var speed = dir * (8 + diff * 7);
        var mesh  = buildTrainMesh();
        var x     = dir > 0 ? -(WORLD_HALF + 14) : (WORLD_HALF + 14);  // spawn off-screen
        if (dir < 0) mesh.rotation.y = Math.PI;
        mesh.position.set(x, 0, 0);
        lane.mesh.add(mesh);
        lane.vehicles.push({ mesh: mesh, x: x, speed: speed, width: mesh.userData.width });
    }

    // --- Spawn logs (with leaves) on river ---
    function spawnLogs(lane, diff) {
        var dir   = Math.random() < 0.5 ? 1 : -1;
        var speed = dir * (0.5 + diff * 1.0);  // slower: 0.5-1.5 (was 0.8-2.3)
        // Spread across full wrap range like road vehicles so density stays even
        var ROAD_RANGE = (WORLD_HALF + 10) * 2;  // 32
        var count   = 4 + Math.floor(Math.random() * (1 + Math.floor(diff * 2)));  // 4-6
        var spacing = ROAD_RANGE / count;

        for (var i = 0; i < count; i++) {
            var mesh = buildLogMesh();
            var logW = mesh.userData.width;
            var x    = -ROAD_RANGE / 2 + spacing * i + Math.random() * spacing * 0.4;
            mesh.position.set(x, 0, 0);
            lane.mesh.add(mesh);
            lane.logs.push({ mesh: mesh, x: x, speed: speed, width: logW });
        }
    }

    // --- Update vehicles (cars, trucks, trains) ---
    function updateVehicles(dt) {
        // At 16:9 landscape the horizontal frustum at the player's lane reaches ~13 units,
        // so wrap bounds must be well beyond that to avoid visible popping.
        var ROAD_BOUND  = WORLD_HALF + 10;  // 16 -- cars/trucks comfortably off-screen
        var TRAIN_BOUND = WORLD_HALF + 18;  // 24 -- trains need extra space for their length
        for (var li = 0; li < lanes.length; li++) {
            var lane = lanes[li];
            var bound = lane.type === 'rail' ? TRAIN_BOUND : ROAD_BOUND;
            for (var vi = 0; vi < lane.vehicles.length; vi++) {
                var v = lane.vehicles[vi];
                v.x += v.speed * dt;
                var hw = v.width / 2;
                if (v.x >  bound + hw) v.x = -bound - hw;
                if (v.x < -bound - hw) v.x =  bound + hw;
                v.mesh.position.x = v.x;
            }
        }
    }

    // --- Update logs and carry player on river ---
    function updateLogs(dt) {
        player.onLog = null;
        var WRAP = WORLD_HALF + 10;  // 16 -- same off-screen margin as road vehicles

        for (var li = 0; li < lanes.length; li++) {
            var lane = lanes[li];
            if (lane.type !== 'river') continue;

            for (var logi = 0; logi < lane.logs.length; logi++) {
                var log = lane.logs[logi];
                log.x += log.speed * dt;
                var hw = log.width / 2;
                if (log.x >  WRAP + hw) log.x = -WRAP - hw;
                if (log.x < -WRAP - hw) log.x =  WRAP + hw;
                log.mesh.position.x = log.x;

                // Detect if player is standing on this log
                if (!player.isHopping && player.alive && player.onLog === null) {
                    var pz = -player.rowIndex * TILE;
                    if (Math.abs(lane.z - pz) < 0.6) {
                        var px = player.mesh.position.x;
                        if (px > log.x - hw - 0.2 && px < log.x + hw + 0.2) {
                            player.onLog = log;
                        }
                    }
                }
            }
        }

        // Carry player along with the log
        if (player.onLog && !player.isHopping) {
            var newX = player.mesh.position.x + player.onLog.speed * dt;
            newX = Math.max(-WORLD_HALF * TILE, Math.min(WORLD_HALF * TILE, newX));
            player.mesh.position.x = newX;
            player.gridX = Math.round(newX / TILE);
        }
    }

    // --- Update player hop animation ---
    function updatePlayer(dt) {
        if (!player.alive) {
            player.deathAngle = Math.min(Math.PI / 2, player.deathAngle + dt * 4);
            player.mesh.rotation.z = player.deathAngle;
            return;
        }

        if (!player.isHopping) {
            if (pendingMove) {
                var pm = pendingMove;
                pendingMove = null;
                startHop(pm.dx, pm.dz);
            }
            return;
        }

        player.hopProgress = Math.min(1, player.hopProgress + dt / HOP_DUR);
        var t = player.hopProgress;
        var ease = t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;

        player.mesh.position.x = player.startX + (player.targetX - player.startX) * ease;
        player.mesh.position.z = player.startZ + (player.targetZ - player.startZ) * ease;
        player.mesh.position.y = RESTING_Y + Math.sin(Math.PI * t) * HOP_H;

        var stretch = 1 + Math.sin(Math.PI * t) * 0.30;
        var squash  = 1 - Math.sin(Math.PI * t) * 0.12;
        player.mesh.scale.set(squash, stretch, squash);

        if (player.hopProgress >= 1) {
            player.mesh.position.y = RESTING_Y;
            player.mesh.scale.set(1, 1, 1);
            player.isHopping = false;
        }
    }

    // --- Start a hop ---
    function startHop(dx, dz) {
        var actualX  = player.mesh.position.x;
        var newGridX = Math.max(-WORLD_HALF, Math.min(WORLD_HALF, Math.round(actualX / TILE) + dx));
        var newRow   = player.rowIndex + dz;
        if (newRow < 0) return;

        // Reset idle timer only on forward movement - left/right hops don't count
        if (dz > 0) {
            idleTime = 0;
            if (timerEl) timerEl.style.display = 'none';
        }

        player.startX = actualX;
        player.startZ = player.mesh.position.z;
        player.gridX    = newGridX;
        player.rowIndex = newRow;
        player.targetX  = newGridX * TILE;
        player.targetZ  = -newRow * TILE;
        player.isHopping   = true;
        player.hopProgress = 0;

        if (newRow > score) {
            score = newRow;
            if (scoreEl) scoreEl.textContent = score;
        }

        if      (dz > 0) player.mesh.rotation.y = 0;
        else if (dz < 0) player.mesh.rotation.y = Math.PI;
        else if (dx > 0) player.mesh.rotation.y = -Math.PI / 2;
        else if (dx < 0) player.mesh.rotation.y =  Math.PI / 2;

        while (maxGeneratedRow < newRow + LOOK_AHEAD) {
            generateLane(maxGeneratedRow + 1);
        }
    }

    // --- Update camera (only advances forward, never retreats) ---
    function updateCamera(dt) {
        var desiredZ = -player.rowIndex * TILE + 10;
        if (desiredZ < cameraTargetZ) cameraTargetZ = desiredZ;

        camera.position.z += (cameraTargetZ - camera.position.z) * Math.min(1, dt * 7);
        camera.position.y  = 8;
        var targetX = player.gridX * TILE * 0.25;
        camera.position.x += (targetX - camera.position.x) * Math.min(1, dt * 5);
        camera.lookAt(camera.position.x * 0.5, 0, camera.position.z - 12);

        dirLight.position.set(camera.position.x + 8, 20, camera.position.z + 10);
        dirLight.target.position.set(camera.position.x, 0, camera.position.z - 12);
        dirLight.target.updateMatrixWorld();
    }

    // --- Idle timer: show countdown after IDLE_WARN seconds, die at IDLE_LIMIT ---
    function updateIdleTimer(dt) {
        if (state !== 'playing' || !player.alive) return;

        idleTime += dt;

        if (idleTime >= IDLE_LIMIT) {
            die();
            return;
        }

        if (idleTime >= IDLE_WARN && timerEl) {
            var remaining = Math.ceil(IDLE_LIMIT - idleTime);
            timerEl.style.display = '';
            timerEl.textContent   = remaining;
            timerEl.style.color   = remaining <= 3 ? '#ff5555' : 'white';
        }
    }

    // --- Collision detection ---
    function checkCollisions() {
        // Use actual mesh x for collision so log drift is accounted for
        var px  = player.mesh.position.x;
        var px1 = px - 0.32;
        var px2 = px + 0.32;
        var pz  = -player.rowIndex * TILE;

        // Cars, trucks, trains
        for (var li = 0; li < lanes.length; li++) {
            var lane = lanes[li];
            if (lane.type !== 'road' && lane.type !== 'rail') continue;
            if (Math.abs(lane.z - pz) > 1.5) continue;
            for (var vi = 0; vi < lane.vehicles.length; vi++) {
                var v = lane.vehicles[vi];
                if (px1 < v.x + v.width / 2 - 0.08 &&
                    px2 > v.x - v.width / 2 + 0.08 &&
                    pz  > lane.z - 0.44 &&
                    pz  < lane.z + 0.44) {
                    die();
                    return;
                }
            }
        }

        // River: must be on a log (only checked while on the ground)
        if (!player.isHopping) {
            for (var rli = 0; rli < lanes.length; rli++) {
                var rlane = lanes[rli];
                if (rlane.type !== 'river') continue;
                if (Math.abs(rlane.z - pz) > 0.6) continue;
                if (!player.onLog) {
                    die();
                    return;
                }
            }
        }
    }

    // --- Die ---
    function die() {
        if (!player.alive) return;
        player.alive = false;
        state = 'dead';
        if (timerEl) timerEl.style.display = 'none';
        var finalScore = score;
        setTimeout(function () {
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('gameOver', [finalScore]);
            }
        }, 700);
    }

    // --- Reset game ---
    function resetGame() {
        for (var i = 0; i < lanes.length; i++) scene.remove(lanes[i].mesh);
        lanes = [];
        maxGeneratedRow   = 0;
        consecutiveHazard = 0;

        state  = 'idle';
        score  = 0;
        pendingMove   = null;
        cameraTargetZ = 10;
        lastTime  = null;
        idleTime  = 0;

        if (scoreEl) { scoreEl.textContent = '0'; scoreEl.style.display = 'none'; }
        if (startEl) startEl.style.display = '';
        if (timerEl) timerEl.style.display = 'none';

        player.gridX       = 0;
        player.rowIndex    = 0;
        player.isHopping   = false;
        player.hopProgress = 0;
        player.startX = player.startZ  = 0;
        player.targetX = player.targetZ = 0;
        player.alive      = true;
        player.deathAngle = 0;
        player.onLog      = null;
        player.mesh.position.set(0, RESTING_Y, 0);
        player.mesh.rotation.set(0, 0, 0);
        player.mesh.scale.set(1, 1, 1);

        camera.position.set(0, 8, 10);
        camera.lookAt(0, 0, -2);

        for (var r = 0; r <= 18; r++) generateLane(r);
    }

    // --- Input ---
    function tryMove(dx, dz) {
        if (!player.alive || state === 'dead') return;

        if (state === 'idle') {
            state = 'playing';
            if (startEl) startEl.style.display = 'none';
            if (scoreEl) scoreEl.style.display = '';
        }

        if (player.isHopping) {
            pendingMove = { dx: dx, dz: dz };
        } else {
            startHop(dx, dz);
        }
    }

    document.addEventListener('keydown', function (e) {
        var tag = document.activeElement ? document.activeElement.tagName : '';
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
        if      (e.code === 'ArrowUp'    || e.code === 'KeyW') { e.preventDefault(); tryMove( 0,  1); }
        else if (e.code === 'ArrowDown'  || e.code === 'KeyS') { e.preventDefault(); tryMove( 0, -1); }
        else if (e.code === 'ArrowLeft'  || e.code === 'KeyA') { e.preventDefault(); tryMove(-1,  0); }
        else if (e.code === 'ArrowRight' || e.code === 'KeyD') { e.preventDefault(); tryMove( 1,  0); }
    });

    var touchStartX = 0;
    var touchStartY = 0;

    renderer.domElement.addEventListener('touchstart', function (e) {
        e.preventDefault();
        touchStartX = e.touches[0].clientX;
        touchStartY = e.touches[0].clientY;
    }, { passive: false });

    renderer.domElement.addEventListener('touchmove', function (e) {
        e.preventDefault();
    }, { passive: false });

    renderer.domElement.addEventListener('touchend', function (e) {
        e.preventDefault();
        var dx = e.changedTouches[0].clientX - touchStartX;
        var dy = e.changedTouches[0].clientY - touchStartY;
        if (Math.abs(dx) < SWIPE_MIN && Math.abs(dy) < SWIPE_MIN) {
            tryMove(0, 1);  // tap = move forward
            return;
        }
        if (Math.abs(dx) > Math.abs(dy)) {
            tryMove(dx > 0 ? 1 : -1, 0);
        } else {
            tryMove(0, dy < 0 ? 1 : -1);
        }
    }, { passive: false });

    // --- Resize ---
    function onResize() {
        if (!container) return;
        var w = container.clientWidth;
        var h = container.clientHeight;
        if (w === 0 || h === 0) return;
        renderer.setSize(w, h);
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
    }

    window.addEventListener('resize', onResize);
    document.addEventListener('livewire:load', onResize);
    document.addEventListener('livewire:navigated', onResize);

    // --- Game loop ---
    function loop(timestamp) {
        var dt = lastTime !== null ? Math.min((timestamp - lastTime) / 1000, 0.1) : 0;
        lastTime = timestamp;

        updateVehicles(dt);
        updatePlayer(dt);  // hop may complete here (sets isHopping = false)
        updateLogs(dt);    // sees correct isHopping state; sets player.onLog; carries player
        updateCamera(dt);
        updateIdleTimer(dt);

        if (state === 'playing') checkCollisions();

        // Remove lanes that have scrolled behind the camera
        var cutoffZ = camera.position.z + 6;
        for (var i = lanes.length - 1; i >= 0; i--) {
            if (lanes[i].z > cutoffZ) {
                scene.remove(lanes[i].mesh);
                lanes.splice(i, 1);
            }
        }

        renderer.render(scene, camera);
        requestAnimationFrame(loop);
    }

    // --- Init ---
    player.mesh = buildPlayerMesh();
    player.mesh.position.set(0, RESTING_Y, 0);
    scene.add(player.mesh);

    for (var r = 0; r <= 18; r++) generateLane(r);

    onResize();
    requestAnimationFrame(loop);

    window.addEventListener('game-restart', resetGame);
})();
