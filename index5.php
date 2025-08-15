<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>RPM Avatar Viewer</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .viewer-container {
            flex: 1;
            width: 100%;
            height: 550px;

        }

        canvas {
            display: block;
            width: 100%;
            height: 100%;
        }

        iframe {
            width: 1080px;
            height: 800px;
            border: none;
        }
    </style>
</head>

<body>
    <h2>Ready Player Me + Three.js</h2>
    <button onclick="displayIframe()">Open Avatar Creator</button>
    <button onclick="captureAvatar()">Save Avatar</button>
    <p id="avatarUrl">Avatar URL: </p>

    <iframe id="frame" allow="camera *; microphone *; clipboard-write" hidden></iframe>

    <div class="viewer-container">
        <canvas id="avatarCanvas"></canvas>
    </div>

    <!-- Three.js ES module setup -->
    <script type="importmap">
  {
    "imports": {
      "three": "https://cdn.jsdelivr.net/npm/three@0.149.0/build/three.module.js",
      "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.149.0/examples/jsm/"
    }
  }
  </script>

    <script type="module">
        import * as THREE from 'three';
        import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
        import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

        const subdomain = 'nexhelp'; // Replace with your RPM subdomain
        const frame = document.getElementById('frame');
        frame.src = `https://${subdomain}.readyplayer.me/avatar?frameApi`;

        window.addEventListener('message', subscribe);
        document.addEventListener('message', subscribe);

        function subscribe(event) {
            const json = parse(event);
            if (json?.source !== 'readyplayerme') return;

            if (json.eventName === 'v1.frame.ready') {
                frame.contentWindow.postMessage(
                    JSON.stringify({
                        target: 'readyplayerme',
                        type: 'subscribe',
                        eventName: 'v1.**'
                    }),
                    '*'
                );
            }

            if (json.eventName === 'v1.avatar.exported') {
                console.log(`Avatar URL: ${json.data.url}`);
                document.getElementById('avatarUrl').innerHTML = `Avatar URL: ${json.data.url}`;
                document.getElementById('frame').hidden = true;
                loadAvatar(json.data.url);
            }

            if (json.eventName === 'v1.user.set') {
                console.log(`User with id ${json.data.id} set: ${JSON.stringify(json)}`);
            }
        }

        function parse(event) {
            try {
                return JSON.parse(event.data);
            } catch {
                return null;
            }
        }

        window.displayIframe = function () {
            document.getElementById('frame').hidden = false;
        }

        // ----------- THREE.JS VIEWER SETUP ------------
        let scene, camera, renderer, controls, clock, model;

        function initViewer() {
            const canvas = document.getElementById('avatarCanvas');

            scene = new THREE.Scene();
            scene.background = null;

            camera = new THREE.PerspectiveCamera(45, canvas.clientWidth / canvas.clientHeight, 0.1, 100);
            camera.position.set(0.5, 1.2, 2);

            renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
            renderer.setSize(canvas.clientWidth, canvas.clientHeight);
            renderer.setPixelRatio(window.devicePixelRatio);

            const ambientLight = new THREE.AmbientLight(0xffffff, 1);
            scene.add(ambientLight);

            const dirLight = new THREE.DirectionalLight(0xffffff, 2);
            dirLight.position.set(5, 10, 5);
            scene.add(dirLight);

            controls = new OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;
            controls.dampingFactor = 0.05;
            controls.target.set(0, 1, 0);
            controls.enablePan = false;

            // Zoom limits
            controls.minDistance = 1.5;  // zoom-in limit (smaller = closer)
            controls.maxDistance = 2;  // zoom-out limit (larger = farther)

            // Rotation limits (vertical)
            controls.minPolarAngle = Math.PI / 3; // 60° from top
            controls.maxPolarAngle = Math.PI / 1.8; // ~100° from top (prevents seeing bottom)
            clock = new THREE.Clock();

            animate();
        }

        function loadAvatar(url) {
            const loader = new GLTFLoader();
            loader.load(url, (gltf) => {
                if (model) scene.remove(model);

                model = gltf.scene;
                model.scale.set(1, 1, 1);

                // Center model
                const box = new THREE.Box3().setFromObject(model);
                const center = box.getCenter(new THREE.Vector3());
                model.position.sub(center);

                // Move it upward so bottom is visible
                const size = box.getSize(new THREE.Vector3());
                model.position.y += size.y / 3;

                scene.add(model);

                scene.add(model);
            }, undefined, (error) => {
                console.error('Error loading model:', error);
            });
        }

        function animate() {
            requestAnimationFrame(animate);
            controls.update();
            renderer.render(scene, camera);
        }

        initViewer();
        loadAvatar("https://models.readyplayer.me/689f0aa64dd25e5878f77d06.glb");

        function captureFaceSnapshot(scene, mainCamera, renderer, headSuffix, distance = 0.8, fov = 20) {
            // Find head mesh
            let headName = null;
            scene.traverse((child) => {
                if (child.isMesh && child.name.endsWith(headSuffix)) {
                    headName = child.name;
                }
            });
            const head = scene.getObjectByName(headName);
            if (!head) {
                console.error(`Head mesh "${headSuffix}" not found`);
                return null;
            }

            // Get head bounding box center
            const box = new THREE.Box3().setFromObject(head);
            const center = new THREE.Vector3();
            box.getCenter(center);

            // Create a temporary photo camera
            const photoCamera = new THREE.PerspectiveCamera(fov, renderer.domElement.width / renderer.domElement.height, 0.1, 1000);
            photoCamera.position.set(center.x, center.y, center.z + distance);
            photoCamera.lookAt(center);
            photoCamera.updateProjectionMatrix();

            // Render scene from photo camera
            renderer.render(scene, photoCamera);

            // Return PNG image as base64
            return renderer.domElement.toDataURL("image/png");
        }

        window.captureAvatar = function () {
            const snapshot = captureFaceSnapshot(scene, camera, renderer, "_Head", 2.5, 10);
            if (!snapshot) {
                console.error("Failed to capture avatar snapshot");
                return;
            }
            fetch("api2.php", {
                method: "POST",
                body: JSON.stringify({ image: snapshot }),
                headers: { "Content-Type": "application/json" }
            });
        };


    </script>
</body>

</html>