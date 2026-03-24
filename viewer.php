<?php
// ============================================
// Visualizador de Arquivo STL
// ============================================
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$fileId = (int)($_GET['file'] ?? 0);

if (!$fileId) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$file = getFileById($fileId);

if (!$file) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// Verifica permissão no projeto
if (!hasPermission((int)$file['project_id'], getCurrentUserId())) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$project  = getProjectById((int)$file['project_id']);
$filePath = UPLOAD_DIR . $file['stored_name'];

if (!file_exists($filePath)) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// URL segura do arquivo via endpoint de serviço
$fileUrl  = BASE_URL . '/api/serve_stl.php?file=' . $fileId;
$fileSize = formatBytes((int)$file['file_size']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($file['original_name']) ?> — STL Viewer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
          rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body { margin: 0; overflow: hidden; background: #0d1117; }
    </style>
</head>
<body>

<div class="viewer-page">

    <!-- Toolbar -->
    <div class="viewer-toolbar">
        <div class="project-info">
            <a href="javascript:history.back()"
               style="color:#8b949e;display:flex;align-items:center;gap:4px;
                      font-size:0.8rem;text-decoration:none;margin-right:4px;"
               title="Voltar">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
            </a>

            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="#2563eb" stroke-width="2.2" style="flex-shrink:0;">
                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                <path d="M2 17l10 5 10-5"/>
                <path d="M2 12l10 5 10-5"/>
            </svg>

            <span class="project-name">
                <?= htmlspecialchars($project['name']) ?>
            </span>

            <span style="color:#30363d;">›</span>

            <span class="file-name">
                <?= htmlspecialchars($file['original_name']) ?>
            </span>

            <span style="background:#21262d;border:1px solid #30363d;
                         color:#8b949e;padding:2px 8px;border-radius:4px;
                         font-size:0.72rem;margin-left:4px;">
                <?= $fileSize ?>
            </span>
        </div>

        <div class="toolbar-actions">

            <!-- Tipo de renderização -->
            <button class="viewer-btn active" id="btn-solid" onclick="setRenderMode('solid')"
                    title="Sólido">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2L2 7l10 5 10-5-10-5z" opacity="0.9"/>
                    <path d="M2 17l10 5 10-5" opacity="0.7"/>
                    <path d="M2 12l10 5 10-5" opacity="0.5"/>
                </svg>
                Sólido
            </button>

            <button class="viewer-btn" id="btn-wireframe"
                    onclick="setRenderMode('wireframe')" title="Wireframe">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                    <path d="M2 17l10 5 10-5"/>
                    <path d="M2 12l10 5 10-5"/>
                </svg>
                Wireframe
            </button>

            <button class="viewer-btn" id="btn-both"
                    onclick="setRenderMode('both')" title="Sólido + Wireframe">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"/>
                    <rect x="14" y="3" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/>
                </svg>
                Ambos
            </button>

            <div style="width:1px;height:20px;background:#30363d;margin:0 4px;"></div>

            <!-- Cor do modelo -->
            <div style="position:relative;" title="Cor do modelo">
                <input type="color" id="model-color" value="#4488ff"
                       onchange="setModelColor(this.value)"
                       style="width:30px;height:28px;padding:2px;
                              border:1px solid #30363d;border-radius:4px;
                              background:#21262d;cursor:pointer;">
            </div>

            <!-- Fundo -->
            <div style="position:relative;" title="Cor do fundo">
                <input type="color" id="bg-color" value="#0d1117"
                       onchange="setBgColor(this.value)"
                       style="width:30px;height:28px;padding:2px;
                              border:1px solid #30363d;border-radius:4px;
                              background:#21262d;cursor:pointer;">
            </div>

            <div style="width:1px;height:20px;background:#30363d;margin:0 4px;"></div>

            <!-- Eixos -->
            <button class="viewer-btn active" id="btn-axes"
                    onclick="toggleAxes()" title="Mostrar/Ocultar eixos">
                Eixos
            </button>

            <!-- Grid -->
            <button class="viewer-btn active" id="btn-grid"
                    onclick="toggleGrid()" title="Mostrar/Ocultar grid">
                Grid
            </button>

            <!-- Reset câmera -->
            <button class="viewer-btn" onclick="resetCamera()" title="Centralizar modelo">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.2">
                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                    <path d="M3 3v5h5"/>
                </svg>
                Reset
            </button>

            <!-- Tela cheia -->
            <button class="viewer-btn" onclick="toggleFullscreen()"
                    title="Tela cheia" id="btn-fullscreen">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.2">
                    <polyline points="15 3 21 3 21 9"/>
                    <polyline points="9 21 3 21 3 15"/>
                    <line x1="21" y1="3" x2="14" y2="10"/>
                    <line x1="3" y1="21" x2="10" y2="14"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Canvas Container -->
    <div class="viewer-container" id="viewer-container">
        <canvas id="stl-canvas"></canvas>

        <!-- Loading -->
        <div class="viewer-loading" id="viewer-loading">
            <div class="spinner"></div>
            <p style="font-size:0.875rem;">Carregando modelo STL...</p>
            <p id="loading-progress"
               style="font-size:0.78rem;color:#30363d;margin-top:-8px;"></p>
        </div>

        <!-- Info Panel -->
        <div class="viewer-info-panel" id="info-panel">
            <div><strong>Arquivo:</strong>
                <?= htmlspecialchars($file['original_name']) ?></div>
            <div><strong>Tamanho:</strong> <?= $fileSize ?></div>
            <div><strong>Triângulos:</strong>
                <span id="triangle-count">—</span></div>
            <div><strong>Dimensões:</strong>
                <span id="model-dimensions">—</span></div>
        </div>

        <!-- Controles hint -->
        <div class="viewer-controls-hint">
            <span><kbd>Botão esq.</kbd> Rotacionar</span>
            <span><kbd>Botão dir.</kbd> Mover</span>
            <span><kbd>Scroll</kbd> Zoom</span>
            <span><kbd>R</kbd> Reset câmera</span>
            <span><kbd>W</kbd> Wireframe</span>
            <span><kbd>F</kbd> Tela cheia</span>
        </div>
    </div>

</div>

<!-- Three.js via CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

<!-- STLLoader via CDN -->
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/STLLoader.js">
</script>

<!-- OrbitControls via CDN -->
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js">
</script>

<script>
// ============================================
// STL Viewer — Three.js
// ============================================

const STL_URL = '<?= $fileUrl ?>';

// ---- Cena, câmera e renderer ----
const container = document.getElementById('viewer-container');
const canvas    = document.getElementById('stl-canvas');

const scene    = new THREE.Scene();
scene.background = new THREE.Color(0x0d1117);

const camera = new THREE.PerspectiveCamera(
    45,
    container.clientWidth / container.clientHeight,
    0.01,
    10000
);
camera.position.set(0, 0, 5);

const renderer = new THREE.WebGLRenderer({
    canvas,
    antialias: true,
    alpha: false
});
renderer.setPixelRatio(window.devicePixelRatio);
renderer.setSize(container.clientWidth, container.clientHeight);
renderer.shadowMap.enabled = true;
renderer.shadowMap.type    = THREE.PCFSoftShadowMap;
renderer.outputEncoding    = THREE.sRGBEncoding;
renderer.toneMapping       = THREE.ACESFilmicToneMapping;
renderer.toneMappingExposure = 1.2;

// ---- Orbit Controls ----
const controls = new THREE.OrbitControls(camera, canvas);
controls.enableDamping    = true;
controls.dampingFactor    = 0.08;
controls.screenSpacePanning = true;
controls.minDistance      = 0.1;
controls.maxDistance      = 5000;
controls.rotateSpeed      = 0.8;
controls.zoomSpeed        = 1.2;
controls.panSpeed         = 0.8;

// ---- Iluminação ----
function setupLights() {
    // Luz ambiente
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.55);
    scene.add(ambientLight);

    // Luz direcional principal (simula sol)
    const dirLight1 = new THREE.DirectionalLight(0xffffff, 0.9);
    dirLight1.position.set(5, 10, 7);
    dirLight1.castShadow = true;
    dirLight1.shadow.mapSize.width  = 2048;
    dirLight1.shadow.mapSize.height = 2048;
    scene.add(dirLight1);

    // Luz direcional de preenchimento (fill light)
    const dirLight2 = new THREE.DirectionalLight(0xd0e8ff, 0.45);
    dirLight2.position.set(-5, 2, -4);
    scene.add(dirLight2);

    // Luz de fundo (rim light)
    const dirLight3 = new THREE.DirectionalLight(0xfff5e0, 0.3);
    dirLight3.position.set(0, -5, -5);
    scene.add(dirLight3);

    // Ponto de luz sutil
    const pointLight = new THREE.PointLight(0x4488ff, 0.4, 100);
    pointLight.position.set(-3, 3, 3);
    scene.add(pointLight);
}

setupLights();

// ---- Grid ----
let gridHelper = null;

function createGrid(size) {
    if (gridHelper) scene.remove(gridHelper);
    gridHelper = new THREE.GridHelper(size, 20, 0x30363d, 0x21262d);
    gridHelper.material.opacity    = 0.7;
    gridHelper.material.transparent = true;
    scene.add(gridHelper);
}

createGrid(10);

// ---- Eixos ----
let axesHelper = null;

function createAxes(size) {
    if (axesHelper) scene.remove(axesHelper);
    axesHelper = new THREE.AxesHelper(size);
    scene.add(axesHelper);
}

createAxes(1);

// ---- Materiais ----
let currentColor = 0x4488ff;

const solidMaterial = new THREE.MeshPhongMaterial({
    color:     currentColor,
    specular:  0x222244,
    shininess: 60,
    side:      THREE.DoubleSide,
});

const wireMaterial = new THREE.MeshBasicMaterial({
    color:     0xffffff,
    wireframe: true,
    opacity:   0.15,
    transparent: true,
});

// ---- Estado global do modelo ----
let solidMesh     = null;
let wireMesh      = null;
let renderMode    = 'solid';   // 'solid' | 'wireframe' | 'both'
let showAxes      = true;
let showGrid      = true;
let modelBox      = null;      // BoundingBox do modelo

// ---- Carrega STL ----
const loader = new THREE.STLLoader();

loader.load(
    STL_URL,

    // onLoad
    function(geometry) {
        geometry.computeVertexNormals();
        geometry.computeBoundingBox();

        const box    = geometry.boundingBox;
        const center = new THREE.Vector3();
        box.getCenter(center);

        const size   = new THREE.Vector3();
        box.getSize(size);
        const maxDim = Math.max(size.x, size.y, size.z);
        const scale  = 4.0 / maxDim; // normaliza para caber na tela

        // Centraliza geometria na origem
        geometry.translate(-center.x, -center.y, -center.z);

        // Cria malha sólida
        solidMesh = new THREE.Mesh(geometry, solidMaterial);
        solidMesh.scale.set(scale, scale, scale);
        solidMesh.castShadow    = true;
        solidMesh.receiveShadow = true;
        scene.add(solidMesh);

        // Cria malha wireframe (mesma geometria)
        wireMesh = new THREE.Mesh(geometry, wireMaterial);
        wireMesh.scale.set(scale, scale, scale);
        wireMesh.visible = false;
        scene.add(wireMesh);

        // Recalcula bounding box escalada
        modelBox = new THREE.Box3().setFromObject(solidMesh);
        const modelSize   = new THREE.Vector3();
        modelBox.getSize(modelSize);
        const modelCenter = new THREE.Vector3();
        modelBox.getCenter(modelCenter);

        // Posiciona câmera para enquadrar o modelo
        fitCameraToModel(modelCenter, modelSize);

        // Ajusta grid e eixos ao tamanho do modelo
        const gridSize = Math.max(modelSize.x, modelSize.z) * 4;
        createGrid(gridSize);
        createAxes(modelSize.x * 0.6);

        // Posiciona grid no fundo do modelo
        if (gridHelper) {
            gridHelper.position.y = modelBox.min.y - 0.01;
        }

        // Informações do modelo
        const triCount = geometry.index
            ? geometry.index.count / 3
            : geometry.attributes.position.count / 3;

        document.getElementById('triangle-count').textContent =
            triCount.toLocaleString('pt-BR');

        const dimText =
            `${(size.x).toFixed(1)} × ` +
            `${(size.y).toFixed(1)} × ` +
            `${(size.z).toFixed(1)} mm`;
        document.getElementById('model-dimensions').textContent = dimText;

        // Oculta loading
        document.getElementById('viewer-loading').classList.add('hidden');
    },

    // onProgress
    function(xhr) {
        if (xhr.total > 0) {
            const pct = Math.round((xhr.loaded / xhr.total) * 100);
            const el = document.getElementById('loading-progress');
            if (el) el.textContent = pct + '%';
        }
    },

    // onError
    function(err) {
        console.error('Erro ao carregar STL:', err);
        const loading = document.getElementById('viewer-loading');
        loading.innerHTML = `
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none"
                 stroke="#dc2626" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <p style="color:#dc2626;font-size:0.9rem;">
                Erro ao carregar o arquivo STL.
            </p>
            <p style="font-size:0.78rem;">
                Verifique se o arquivo está correto.
            </p>`;
    }
);

// ---- Fit camera ao modelo ----
function fitCameraToModel(center, size) {
    const maxDim  = Math.max(size.x, size.y, size.z);
    const fovRad  = THREE.MathUtils.degToRad(camera.fov);
    let   dist    = (maxDim / 2) / Math.tan(fovRad / 2);
    dist *= 1.7; // margem

    camera.position.set(
        center.x + dist * 0.6,
        center.y + dist * 0.5,
        center.z + dist
    );
    camera.near = dist * 0.001;
    camera.far  = dist * 100;
    camera.updateProjectionMatrix();

    controls.target.copy(center);
    controls.minDistance = dist * 0.02;
    controls.maxDistance = dist * 20;
    controls.update();
}

// ---- Modo de renderização ----
function setRenderMode(mode) {
    renderMode = mode;

    document.getElementById('btn-solid').classList.remove('active');
    document.getElementById('btn-wireframe').classList.remove('active');
    document.getElementById('btn-both').classList.remove('active');
    document.getElementById('btn-' + mode).classList.add('active');

    if (!solidMesh || !wireMesh) return;

    switch (mode) {
        case 'solid':
            solidMesh.visible      = true;
            solidMesh.material     = solidMaterial;
            wireMesh.visible       = false;
            break;
        case 'wireframe':
            solidMesh.visible      = true;
            solidMesh.material     = new THREE.MeshBasicMaterial({
                color:     currentColor,
                wireframe: true
            });
            wireMesh.visible       = false;
            break;
        case 'both':
            solidMesh.visible      = true;
            solidMesh.material     = solidMaterial;
            wireMesh.visible       = true;
            break;
    }
}

// ---- Cor do modelo ----
function setModelColor(hexStr) {
    currentColor = parseInt(hexStr.replace('#', ''), 16);
    solidMaterial.color.setHex(currentColor);
    solidMaterial.needsUpdate = true;
    if (renderMode === 'wireframe' && solidMesh) {
        solidMesh.material.color.setHex(currentColor);
    }
}

// ---- Cor do fundo ----
function setBgColor(hexStr) {
    scene.background = new THREE.Color(hexStr);
}

// ---- Toggle Eixos ----
function toggleAxes() {
    showAxes = !showAxes;
    if (axesHelper) axesHelper.visible = showAxes;
    document.getElementById('btn-axes')
            .classList.toggle('active', showAxes);
}

// ---- Toggle Grid ----
function toggleGrid() {
    showGrid = !showGrid;
    if (gridHelper) gridHelper.visible = showGrid;
    document.getElementById('btn-grid')
            .classList.toggle('active', showGrid);
}

// ---- Reset câmera ----
function resetCamera() {
    if (!solidMesh || !modelBox) return;

    const size   = new THREE.Vector3();
    const center = new THREE.Vector3();
    modelBox.getSize(size);
    modelBox.getCenter(center);
    fitCameraToModel(center, size);
}

// ---- Tela cheia ----
function toggleFullscreen() {
    if (!document.fullscreenElement) {
        container.requestFullscreen().catch(err => {
            console.warn('Fullscreen error:', err);
        });
    } else {
        document.exitFullscreen();
    }
}

document.addEventListener('fullscreenchange', function() {
    const btn = document.getElementById('btn-fullscreen');
    if (document.fullscreenElement) {
        btn.classList.add('active');
    } else {
        btn.classList.remove('active');
        onResize();
    }
});

// ---- Atalhos de teclado ----
document.addEventListener('keydown', function(e) {
    // Ignora se o foco estiver em input
    if (e.target.tagName === 'INPUT') return;

    switch (e.key.toLowerCase()) {
        case 'r': resetCamera();              break;
        case 'w': setRenderMode('wireframe'); break;
        case 's': setRenderMode('solid');     break;
        case 'b': setRenderMode('both');      break;
        case 'g': toggleGrid();               break;
        case 'a': toggleAxes();               break;
        case 'f': toggleFullscreen();         break;
    }
});

// ---- Resize handler ----
function onResize() {
    const w = container.clientWidth;
    const h = container.clientHeight;
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
    renderer.setSize(w, h);
}

window.addEventListener('resize', onResize);

// ---- Loop de animação ----
let animFrameId = null;

function animate() {
    animFrameId = requestAnimationFrame(animate);
    controls.update();
    renderer.render(scene, camera);
}

animate();

// ---- Limpeza ao sair ----
window.addEventListener('beforeunload', function() {
    cancelAnimationFrame(animFrameId);
    renderer.dispose();
    controls.dispose();
});
</script>

</body>
</html>
