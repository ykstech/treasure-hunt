<?php
session_start();
$pdo = new PDO("mysql:host=localhost:3307;dbname=treasure_game;charset=utf8", "root", "");

$message = "";
$showWinPopup = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_number = intval($_POST['team_number']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM treasure_teams WHERE team_number = ?");
    $stmt->execute([$team_number]);
    $team = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($team) {
        if ($team['attempts_left'] > 0) {
            if ($password === $team['password']) {
                $_SESSION['showWinPopup'] = true;
                $pdo->prepare("UPDATE treasure_teams SET attempts_left = 0 WHERE team_number = ?")->execute([$team_number]);
            } else {
                // $pdo->prepare("UPDATE treasure_teams SET attempts_left = attempts_left - 1 WHERE team_number = ?")->execute([$team_number]);
                $_SESSION['message'] = "❌ Wrong password! Attempts left: " . ($team['attempts_left'] - 1);
            }
        } else {
            $_SESSION['message'] = "🚫 No attempts left for Team $team_number!";
        }
    } else {
        $_SESSION['message'] = "⚠ Team not found!";
    }

    // Redirect to avoid POST resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Retrieve and clear session messages
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
if (isset($_SESSION['showWinPopup'])) {
    $showWinPopup = true;
    unset($_SESSION['showWinPopup']);
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Treasure Hunt — Running Light Border</title>

    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&display=swap" rel="stylesheet">

    <style>
        .border-wrap {
            position: relative;
            width: 700px;
            max-width: 94vw;
            margin: 60px auto;
            min-height: 360px;
            overflow: hidden;
            display: flex;
            justify-content: center;
            padding-left: 80px;
        }

        .animated-border-box,
        .animated-border-box-glow {
            max-height: 400px;
            max-width: 520px;
            height: 100%;
            width: 100%;
            position: absolute;
            overflow: hidden;
            z-index: 0;
            border-radius: 10px;
        }

        .animated-border-box-glow {
            overflow: hidden;
            filter: blur(18px);
            z-index: -3;
        }

        .animated-border-box:before,
        .animated-border-box-glow:before {
            content: '';
            z-index: -2;
            text-align: center;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(0deg);
            position: absolute;
            width: 3000px;
            height: 3000px;
            background-repeat: no-repeat;
            background-position: 0 0;
            background-image: conic-gradient(rgba(0, 0, 0, 0), #FFD700, rgba(0, 0, 0, 0) 25%);
            animation: rotate 4s linear infinite;
        }

        .animated-border-box:after {
            content: '';
            position: absolute;
            z-index: -1;
            left: 5px;
            top: 5px;
            width: calc(100% - 10px);
            height: calc(100% - 10px);
            background: #0f1720;
            border-radius: 7px;
        }

        @keyframes rotate {
            100% {
                transform: translate(-50%, -50%) rotate(1turn);
            }
        }

        .box-inner {
            position: relative;
            z-index: 1;
            padding: 2.25rem;
            color: #f3d27a;
            /* golden text */
            font-family: 'Cinzel', serif;
            height: 100%;
            box-sizing: border-box;
            overflow: hidden;
        }

        .box-inner h1 {
            font-size: 1.45rem;
            margin: 0 0 1rem 0;
            text-align: center;
            letter-spacing: 2px;
            color: #ffd76b;
        }

        .box-inner label {
            display: block;
            margin-top: 0.75rem;
            font-weight: 600;
            color: #e9c86b;
        }

        .box-inner input {
            width: 420px;
            padding: .65rem .75rem;
            margin-top: .35rem;
            border-radius: 6px;
            border: 1px solid rgba(255, 215, 0, 0.12);
            background: #1a2330;
            color: rgba(230, 230, 230, 1);
            font-size: 1rem;
        }

        .btn {
            display: block;
            margin-top: 1rem;
            width: 100%;
            padding: .7rem;
            border-radius: 8px;
            background: #f0b500;
            border: none;
            font-weight: 700;
            cursor: pointer;
        }

        @media (max-width:560px) {
            .border-wrap {
                width: 92vw;
                min-height: 300px;
            }

            .box-inner {
                padding: 1.25rem;
            }
        }

        body {
            background: url('images/treasure-bg.jpg') no-repeat center center;
            background-size: cover;
            background-attachment: fixed;
            margin: 0;
            min-height: 100vh;
        }

        .container {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            /* needed so absolute positioning is relative to this */
        }

        .chest-box {
            flex: 1;
            width: 300px;
            height: 300px;
        }

        .wizard-box {
            position: absolute;
            left: -18px;
            /* shift so wizard overlaps form */
            top: 50%;
            transform: translateY(-50%);
            /* vertically center */
            width: 350px;
            height: 600px;
            z-index: 10;

            /* so clicks go through to form */
        }

        canvas {
            display: block;
            width: 100%;
            height: 100%;
        }

        .speech-bubble {
            position: absolute;
            top: 10%;
            /* above wizard's head */
            left: 16%;
            /* shift to right of wizard */
            background: #fff5d6;
            color: #4b2e05;
            padding: 12px 16px;
            border-radius: 12px;
            border: 2px solid #b98b3e;
            max-width: 220px;
            font-family: 'Georgia', serif;
            font-size: 16px;
            box-shadow: 2px 2px 6px rgba(0, 0, 0, 0.3);
            opacity: 0;
            pointer-events: none;
            /* don’t block clicks */
            transition: opacity 0.4s ease, transform 0.4s ease;
            transform: translateY(-10px);
        }

        .speech-bubble::after {
            content: "";
            position: absolute;
            top: 50%;
            /* center vertically */
            left: -10px;
            /* stick out from left side */
            transform: translateY(-50%);
            /* adjust for true centering */

            /* triangle pointing left */
            border-width: 15px 20px 15px 0;
            border-style: solid;
            border-color: transparent #fff5d6 transparent transparent;
        }



        /* Show animation */
        .speech-bubble.show {
            opacity: 1;
            transform: translateY(0);
        }

        .key-box {
            position: absolute;
            right: 20%;
            top: 20%;
            transform: translateY(-50%);
            padding: 10px;
            z-index: 5;
            background-color: #0f1720;
            display: flex;
            align-items: center;
            border-radius: 10px;
            border: 1px solid #b98b3e;
        }
    </style>

</head>

<body style="">

    <div class="container">
        <div class="border-wrap">
            <div class="animated-border-box-glow"></div>
            <div class="animated-border-box">
                <div class="box-inner">
                    <h1> TREASURE HUNT <br>FINAL ROUND</h1>
                    <label>Team Number
                        <input name="team_number" id="team_number" type="number" min="1" max="8" required
                            placeholder="Enter team number" required>
                    </label>
                    <label>Password
                        <input name="password" id="password" type="number" maxlength="6"
                            placeholder="Enter 6-digit code" required>
                    </label>
                    <button type="submit" class="btn" id="checkPasswordBtn">Unlock Treasure</button>
                </div>
            </div>
        </div>
        <div class="chest-box">
            <canvas id="chestCanvas"></canvas>
        </div>
        <div class="key-box" id="keyBox">
            <img src="images/key.png" height="50px" id="key1" />
            <img src="images/key.png" height="50px" id="key2" />
        </div>
    </div>
    <div class="wizard-box">
        <canvas id="wizardCanvas"></canvas>
    </div>
    <div id="wizardBubble" class="speech-bubble"></div>

    <!-- Confetti JS -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

    <script type="importmap">
        {
        "imports": {
            "three": "https://cdn.jsdelivr.net/npm/three@v0.149.0/build/three.module.js",
            "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.149.0/examples/jsm/"
        }
        }
    </script>
    <script type="module">
        import * as THREE from 'three';
        import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
        import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';
        import { AnimationUtils } from 'three';

        let scene, camera, renderer, mixer, clock, controls, model;

        function initChest() {
            const canvas = document.getElementById('chestCanvas');

            scene = new THREE.Scene();
            scene.background = null; // transparent

            camera = new THREE.PerspectiveCamera(45, canvas.clientWidth / canvas.clientHeight, 0.1, 100);
            camera.position.set(0.5, 1.2, 2);

            renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
            renderer.setSize(canvas.clientWidth, canvas.clientHeight);
            renderer.setPixelRatio(window.devicePixelRatio);

            // Lighting setup
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.4);
            scene.add(ambientLight);

            // Key light - positioned directly in front of the chest, slightly above eye level
            const keyLight = new THREE.DirectionalLight(0xffffff, 2);
            keyLight.position.set(0, 2, 3); // in front & above
            keyLight.target.position.set(0, 0.5, 0); // aim at chest center
            scene.add(keyLight);
            scene.add(keyLight.target);

            // Fill light - softer, opposite side to reduce harsh shadows
            const fillLight = new THREE.DirectionalLight(0xffffff, 0.5);
            fillLight.position.set(-2, 1, -2);
            scene.add(fillLight);

            // Back light - faint rim highlight to separate chest from background
            const rimLight = new THREE.DirectionalLight(0xffffff, 0.3);
            rimLight.position.set(0, 2, -3);
            scene.add(rimLight);

            // Orbit controls
            controls = new OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;
            controls.dampingFactor = 0.05;
            controls.enablePan = false;
            controls.minDistance = 5;
            controls.maxDistance = 5;
            controls.minPolarAngle = Math.PI / 4; // looking from above
            controls.maxPolarAngle = Math.PI / 2; // straight at model
            controls.target.set(0, 1, 0);

            clock = new THREE.Clock();
            let chestLoaded = false;
            // Load model
            const loader = new GLTFLoader();
            loader.load('models/treasure_chest.glb', (gltf) => {
                model = gltf.scene;
                model.scale.set(1, 1, 1);
                camera.lookAt(model.position);

                // Center model
                const box = new THREE.Box3().setFromObject(model);
                const center = box.getCenter(new THREE.Vector3());
                model.position.sub(center);
                // Move it upward so bottom is visible
                const size = box.getSize(new THREE.Vector3());
                model.position.y += size.y / 3;

                // Rotate so it faces sideways
                model.rotation.y = -(Math.PI / 2) - Math.PI / 15;
                scene.add(model);

                mixer = new THREE.AnimationMixer(model);
                gltf.animations.forEach((clip) => {
                    if (clip.name.toLowerCase().includes("open_chest")) {
                        window.openChestAction = mixer.clipAction(clip);
                    }
                });

                animate();
                chestLoaded = true;
            });
        }

        let chestSequenceActive = false;
        const chestStart = 6.8; // seconds in clip to start from
        const chestEnd = 9.0; // seconds in clip to stop at
        let chestStartPos = null;
        let chestEndPos = null;

        // Call this to start the whole cinematic (camera + trimmed animation)
        function playChestAnimation() {
            if (!window.openChestAction || !model) return;

            // prepare camera positions
            chestStartPos = camera.position.clone();
            chestEndPos = new THREE.Vector3(0, 1.5, 0.8); // change to desired final camera position

            // disable user interaction during cinematic
            controls.enabled = false;

            // prepare animation
            window.openChestAction.reset();
            window.openChestAction.time = chestStart; // jump to start
            window.openChestAction.paused = false;
            window.openChestAction.play();

            // mark active so animate() will handle camera interpolation
            chestSequenceActive = true;

            // ensure clock delta is fresh (avoid huge first-delta)
            clock.getDelta();
        }

        // Integrate into your main animate() loop — update mixer first, then camera based on action.time
        function animate() {
            requestAnimationFrame(animate);

            const delta = clock.getDelta();

            // update mixer (advances action.time)
            if (mixer) mixer.update(delta);

            // If sequence is active, sync camera to the action time
            if (chestSequenceActive && window.openChestAction) {
                const cur = window.openChestAction.time;
                const t = THREE.MathUtils.clamp((cur - chestStart) / (chestEnd - chestStart), 0, 1);

                // interpolate camera from start -> end using absolute t based on clip time
                camera.position.lerpVectors(chestStartPos, chestEndPos, t);
                camera.lookAt(model.position);

                // finish sequence when clip reaches end
                if (cur >= chestEnd - 1e-6) {
                    // lock final states
                    window.openChestAction.time = chestEnd;
                    window.openChestAction.paused = true;
                    camera.position.copy(chestEndPos);
                    camera.lookAt(model.position);

                    chestSequenceActive = false;
                    controls.enabled = true; // re-enable user controls
                }
            }

            // update controls (if enabled)
            controls.update();

            // render
            renderer.render(scene, camera);
        }

        initChest();

        function tryPlayChestAnimation() {
            if (window.openChestAction) {
                // console.log("Playing chest animation");
                playChestAnimation();
            } else {
                // console.log("Chest not ready yet, waiting...");
                const wait = setInterval(() => {
                    if (window.openChestAction) {
                        // console.log("Now playing chest animation");
                        playChestAnimation();
                        clearInterval(wait);
                    }
                }, 100);
            }
        }

        let wizardScene, wizardCamera, wizardRenderer, wizardControls, wizardModel, wizardClock;

        let wizardTargetRotation = null; // store desired rotation
        let wizardRotationSpeed = 1.0;   // radians per second

        function initWizard() {
            const canvas = document.getElementById('wizardCanvas');

            wizardScene = new THREE.Scene();
            wizardScene.background = null; // transparent

            wizardCamera = new THREE.PerspectiveCamera(45, canvas.clientWidth / canvas.clientHeight, 0.1, 100);
            wizardCamera.position.set(0.5, 1.2, 2);

            wizardRenderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
            wizardRenderer.setSize(canvas.clientWidth, canvas.clientHeight);
            wizardRenderer.setPixelRatio(window.devicePixelRatio);

            // Lighting for wizard (soft front light)
            const ambient = new THREE.AmbientLight(0xffffff, 0.6);
            wizardScene.add(ambient);

            const frontLight = new THREE.DirectionalLight(0xffffff, 1.2);
            frontLight.position.set(0, 2, 3);
            wizardScene.add(frontLight);

            const rim = new THREE.DirectionalLight(0xffffff, 0.3);
            rim.position.set(0, 2, -2);
            wizardScene.add(rim);

            // Controls (optional, can be removed if static)
            wizardControls = new OrbitControls(wizardCamera, wizardRenderer.domElement);
            wizardControls.enableDamping = true;
            wizardControls.dampingFactor = 0.05;
            wizardControls.enablePan = false;
            wizardControls.minDistance = 9;
            wizardControls.maxDistance = 9;
            wizardControls.minPolarAngle = Math.PI / 4; // looking from above
            wizardControls.maxPolarAngle = Math.PI / 2; // straight at model
            wizardControls.target.set(0, 1, 0);

            wizardClock = new THREE.Clock();
            // Load wizard model
            const loader = new GLTFLoader();
            loader.load('models/wizard.glb', (gltf) => {
                wizardModel = gltf.scene;
                wizardModel.scale.set(1.5, 1.5, 1.5);

                // Center & slightly lift model
                const box = new THREE.Box3().setFromObject(wizardModel);
                const center = box.getCenter(new THREE.Vector3());
                wizardModel.position.sub(center);
                wizardModel.position.y -= box.min.y / 4; // base on floor
                wizardModel.rotation.y = (Math.PI / 1);

                wizardScene.add(wizardModel);
                animateWizard();
            });
        }

        function animateWizard() {
            requestAnimationFrame(animateWizard);

            const delta = wizardClock.getDelta();


            // Smoothly rotate wizard toward target
            if (wizardTargetRotation !== null && wizardModel) {
                let current = wizardModel.rotation.y;
                let target = wizardTargetRotation;

                // Shortest rotation direction
                let diff = target - current;
                diff = ((diff + Math.PI) % (Math.PI * 2)) - Math.PI;

                // If close enough, snap to target
                if (Math.abs(diff) < 0.01) {
                    wizardModel.rotation.y = target;
                    wizardTargetRotation = null; // done rotating
                } else {
                    wizardModel.rotation.y += Math.sign(diff) * wizardRotationSpeed * delta;
                }
            }

            wizardControls.update();
            wizardRenderer.render(wizardScene, wizardCamera);
        }
        initWizard();

        function faceWizardFront() {
            if (!wizardModel) return;
            // Forward-facing rotation — adjust if your model's "front" isn't 0 radians
            wizardTargetRotation = 0;
        }
        function tryPlayWizardAnimation() {
            if (wizardModel) {
                // console.log("Playing wizard animation");
                faceWizardFront();
            } else {
                // console.log("Wizard not ready yet, waiting...");
                const wait = setInterval(() => {
                    if (window.openChestAction) {
                        // console.log("Now playing Wizard animation");
                        faceWizardFront();
                        clearInterval(wait);
                    }
                }, 100);
            }
        }
       

        document.addEventListener('click', () => {
            showWizardMessage("Ahh… traveler… you have reached the final place… the treasure lies close… yet beware… you hold but two chances to unlock its secret key. Choose… wisely…", 15, true, 'wizard.mp3');
        tryPlayWizardAnimation();
        }, { once: true });

        function showWizardMessage(text, seconds = 2000, playVoice = false, audioFile) {
            let wizardAudio = new Audio(`audio/${audioFile}`);
            const bubble = document.getElementById('wizardBubble');
            const key1 = document.getElementById('key1');
            const key2 = document.getElementById('key2');
            const keyBox = document.getElementById('keyBox');
            // Set text
            bubble.innerHTML = text;

            // Show bubble
            bubble.classList.add('show');

            // Play voice if requested
            if (playVoice) {
                wizardAudio.currentTime = 0;
                wizardAudio.play().catch(err => console.warn("Audio blocked:", err));
            }

            // Remove keys based on audioFile
            if (audioFile === "one-left.mp3") {
                if (key1) key1.remove();
            } else if (audioFile === "fail.mp3") {
                if (key1) key1.remove();
                if (key2) key2.remove();
                if (keyBox) keyBox.remove();
            }else if (audioFile === "win.mp3") {
                if (key1) key1.remove();
                if (key2) key2.remove();
                if (keyBox) keyBox.remove();
            }

            // Hide after 2 seconds
            setTimeout(() => {
                bubble.classList.remove('show');
            }, seconds * 1000);
        }

        function fireConfetti() {
            let soundFiles = ["audio/a1.mp3", "audio/a2.mp3", "audio/a3.mp3"];

            // Play sounds once at the start
            soundFiles.forEach(file => {
                let audio = new Audio(file);
                audio.play();
            });

            // Array of confetti origins to alternate
            const origins = [
                { x: 0, y: 1 },
                { x: 1, y: 1 },
                { x: 0, y: 1 },
                { x: 1, y: 1 },
                { x: 0, y: 1 },
                { x: 1, y: 1 },
            ];

            origins.forEach((origin, index) => {
                setTimeout(() => {
                    confetti({
                        particleCount: 80,
                        spread: 60,
                        origin: origin,
                    });
                }, index * 2000); // 2000ms = 2 seconds gap
            });
        }


        document.getElementById("checkPasswordBtn").addEventListener("click", function () {
            const teamNumber = document.getElementById("team_number").value;
            const password = document.getElementById("password").value;

            fetch("api.php", {
                method: "POST",
                body: new URLSearchParams({
                    team_number: teamNumber,
                    password: password
                }),
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                }
            })
                .then(res => res.json())
                .then(data => {
                    // console.log(data.message);
                    showWizardMessage(data.message, data.seconds, true, data.audio);
                    if (data.showWinPopup) {
                        tryPlayChestAnimation(); // Run animation immediately without reload
                        fireConfetti();
                    }
                })
                .catch(err => console.error("Error:", err));
        });
    </script>
</body>

</html>