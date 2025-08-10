<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Treasure Hunt — Running Light Border</title>
    <style>
        .chest-box {
            flex: 1;
            width: 300px;
            height: 550px;
            border: 1px solid black;
        }

        canvas {
            display: block;
            width: 100%;
            height: 100%;
        }
    </style>
</head>

<body style="">
    <div class="chest-box">
        <canvas id="chestCanvas"></canvas>
    </div>
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
            const ambientLight = new THREE.AmbientLight(0xffffff, 1);
            scene.add(ambientLight);

            const dirLight = new THREE.DirectionalLight(0xffffff, 2);
            dirLight.position.set(5, 10, 5);
            dirLight.castShadow = true;
            scene.add(dirLight);

            // Orbit controls
            controls = new OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;
            controls.dampingFactor = 0.05;
            controls.enablePan = false;
            controls.minDistance = 9;
            controls.maxDistance = 9;
            controls.minPolarAngle = Math.PI / 4; // looking from above
            controls.maxPolarAngle = Math.PI / 2; // straight at model
            controls.target.set(0, 1, 0);

            clock = new THREE.Clock();
            let chestLoaded = false;
            // Load model
            const loader = new GLTFLoader();
            loader.load('models/wizard.glb', (gltf) => {
                model = gltf.scene;
                model.scale.set(1.5, 1.5, 1.5);
                camera.lookAt(model.position);

                // Center model
                const box = new THREE.Box3().setFromObject(model);
                const center = box.getCenter(new THREE.Vector3());
                model.position.sub(center);
                // Move it upward so bottom is visible
                const size = box.getSize(new THREE.Vector3());
                 model.position.y += size.y / 4;

                // Rotate so it faces sideways
                // model.rotation.y = -(Math.PI / 2) - Math.PI / 15;
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

        function animate() {
            requestAnimationFrame(animate);
            const delta = clock.getDelta();
            if (mixer) mixer.update(delta);
            controls.update();
            renderer.render(scene, camera);
        }

        export function playChestAnimation() {
            if (!window.openChestAction) return;
                window.openChestAction.reset().play();
                window.openChestAction.time = 6.8;
                const startPos = camera.position.clone();
                const endPos = new THREE.Vector3(0, 2, 0.8); // Top-down angle
                const duration = 0.2; // seconds
                let elapsed = 0;
                // console.log("camera start position:", startPos);
                // console.log("camera end position:", endPos);
                controls.enabled = false; // before camera move

                function moveCamera() {
                    elapsed += clock.getDelta();
                    const t = Math.min(elapsed / duration, 1);
                    camera.position.lerpVectors(startPos, endPos, t);
                    camera.lookAt(model.position);
                    // console.log("camera position:", camera.position);
                    // console.log("camera move progress:", t);
                    if (t < 1) {
                        requestAnimationFrame(moveCamera);
                    } else {
                        controls.enabled = true; // re-enable after movement
                    }
                }
                moveCamera();
                
            
        }

        function tryPlayChestAnimation() {
            if (window.openChestAction) {
                console.log("Playing chest animation");
                playChestAnimation();
            } else {
                console.log("Chest not ready yet, waiting...");
                const wait = setInterval(() => {
                    if (window.openChestAction) {
                        console.log("Now playing chest animation");
                        playChestAnimation();
                        clearInterval(wait);
                    }
                }, 100); // check every 100ms
            }
        }

        initChest();
        // tryPlayChestAnimation();
    </script>