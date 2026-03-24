<?php
// ============================================
// Visualizador de Arquivo STL / 3MF — r155+
// ============================================
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$fileId = (int)($_GET['file'] ?? 0);
if (!$fileId) { header('Location: ' . BASE_URL . '/dashboard.php'); exit; }

$file = getFileById($fileId);
if (!$file) { header('Location: ' . BASE_URL . '/dashboard.php'); exit; }

if (!hasPermission((int)$file['project_id'], getCurrentUserId())) {
    header('Location: ' . BASE_URL . '/dashboard.php'); exit;
}

$project  = getProjectById((int)$file['project_id']);
$filePath = UPLOAD_DIR . $file['stored_name'];
if (!file_exists($filePath)) { header('Location: ' . BASE_URL . '/dashboard.php'); exit; }

$fileExt  = strtolower(pathinfo($file['stored_name'], PATHINFO_EXTENSION));
$is3MF    = $fileExt === '3mf';
$fileUrl  = BASE_URL . '/api/serve_stl.php?file=' . $fileId;
$fileSize = formatBytes((int)$file['file_size']);

$backUrl = BASE_URL . '/dashboard.php';
if (!empty($_SERVER['HTTP_REFERER'])) {
    $referer = $_SERVER['HTTP_REFERER'];
    if (str_starts_with($referer, BASE_URL)) {
        $backUrl = htmlspecialchars($referer);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($file['original_name']) ?> — STL Viewer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body { margin: 0; overflow: hidden; background: #0d1117; }

        /* Painel de cores dos bodies */
        .body-colors-panel {
            position: absolute;
            top: 56px;
            right: 12px;
            background: rgba(13,17,23,0.95);
            border: 1px solid #30363d;
            border-radius: 8px;
            padding: 10px 12px;
            min-width: 160px;
            max-height: calc(100vh - 80px);
            overflow-y: auto;
            z-index: 100;
            backdrop-filter: blur(4px);
            display: none; /* oculto até ter multi-body */
        }

        .body-colors-panel.visible {
            display: block;
        }

        .body-colors-title {
            font-size: 0.72rem;
            font-weight: 600;
            color: #8b949e;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 1px solid #30363d;
        }

        .body-color-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 0;
            font-size: 0.78rem;
            color: #c9d1d9;
        }

        .body-color-row input[type="color"] {
            width: 22px;
            height: 22px;
            padding: 1px;
            border: 1px solid #30363d;
            border-radius: 4px;
            background: #21262d;
            cursor: pointer;
            flex-shrink: 0;
        }

        .body-color-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }
    </style>

    <script type="importmap">
    {
        "imports": {
            "three": "https://cdn.jsdelivr.net/npm/three@0.155.0/build/three.module.js",
            "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.155.0/examples/jsm/"
        }
    }
    </script>
</head>
<body>

<div class="viewer-page">

    <!-- Toolbar -->
    <div class="viewer-toolbar">
        <div class="project-info">
            <a href="<?= $backUrl ?>"
               onclick="handleBack(event)"
               style="color:#8b949e;display:flex;align-items:center;gap:4px;
                      font-size:0.8rem;text-decoration:none;margin-right:4px;"
               title="Voltar">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
            </a>

            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="<?= $is3MF ? '#16a34a' : '#2563eb' ?>"
                 stroke-width="2.2" style="flex-shrink:0;">
                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                <path d="M2 17l10 5 10-5"/>
                <path d="M2 12l10 5 10-5"/>
            </svg>

            <span class="project-name"><?= htmlspecialchars($project['name']) ?></span>
            <span style="color:#30363d;">›</span>
            <span class="file-name"><?= htmlspecialchars($file['original_name']) ?></span>

            <span style="background:#21262d;border:1px solid #30363d;
                         color:#8b949e;padding:2px 8px;border-radius:4px;
                         font-size:0.72rem;margin-left:4px;">
                <?= $fileSize ?>
            </span>

            <span style="background:<?= $is3MF ? '#14532d' : '#1e3a5f' ?>;
                         border:1px solid <?= $is3MF ? '#16a34a' : '#2563eb' ?>;
                         color:<?= $is3MF ? '#4ade80' : '#60a5fa' ?>;
                         padding:2px 8px;border-radius:4px;
                         font-size:0.72rem;margin-left:4px;
                         font-weight:600;letter-spacing:0.05em;">
                <?= strtoupper($fileExt) ?>
            </span>
        </div>

        <div class="toolbar-actions">

            <!-- Renderização -->
            <button class="viewer-btn active" id="btn-solid"
                    onclick="setRenderMode('solid')" title="Sólido">
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

            <!-- Cor única — só STL single-body e 3MF -->
            <div id="single-color-picker"
                 style="position:relative;<?= $is3MF ? 'display:none;' : '' ?>"
                 title="Cor do modelo">
                <input type="color" id="model-color" value="#4488ff"
                       onchange="setModelColor(this.value)"
                       style="width:30px;height:28px;padding:2px;
                              border:1px solid #30363d;border-radius:4px;
                              background:#21262d;cursor:pointer;">
            </div>

            <!-- Botão painel multi-body — aparece só quando detectado -->
            <button class="viewer-btn" id="btn-bodies"
                    onclick="toggleBodyPanel()"
                    title="Cores por body"
                    style="display:none;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2">
                    <circle cx="7"  cy="7"  r="3"/>
                    <circle cx="17" cy="7"  r="3"/>
                    <circle cx="7"  cy="17" r="3"/>
                    <circle cx="17" cy="17" r="3"/>
                </svg>
                Bodies
            </button>

            <!-- Fundo -->
            <div style="position:relative;" title="Cor do fundo">
                <input type="color" id="bg-color" value="#0d1117"
                       onchange="setBgColor(this.value)"
                       style="width:30px;height:28px;padding:2px;
                              border:1px solid #30363d;border-radius:4px;
                              background:#21262d;cursor:pointer;">
            </div>

            <div style="width:1px;height:20px;background:#30363d;margin:0 4px;"></div>

            <button class="viewer-btn active" id="btn-axes"
                    onclick="toggleAxes()" title="Mostrar/Ocultar eixos">Eixos</button>

            <button class="viewer-btn active" id="btn-grid"
                    onclick="toggleGrid()" title="Mostrar/Ocultar grid">Grid</button>

            <button class="viewer-btn" onclick="resetCamera()" title="Centralizar modelo">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.2">
                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                    <path d="M3 3v5h5"/>
                </svg>
                Reset
            </button>

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

    <!-- Canvas -->
    <div class="viewer-container" id="viewer-container">
        <canvas id="stl-canvas"></canvas>

        <!-- Painel de cores por body -->
        <div class="body-colors-panel" id="body-colors-panel">
            <div class="body-colors-title">Bodies</div>
            <div id="body-colors-list"></div>
        </div>

        <!-- Loading -->
        <div class="viewer-loading" id="viewer-loading">
            <div class="spinner"></div>
            <p style="font-size:0.875rem;">
                Carregando modelo <?= strtoupper($fileExt) ?>...
            </p>
            <p id="loading-progress"
               style="font-size:0.78rem;color:#30363d;margin-top:-8px;"></p>
        </div>

        <!-- Info -->
        <div class="viewer-info-panel" id="info-panel">
            <div><strong>Arquivo:</strong> <?= htmlspecialchars($file['original_name']) ?></div>
            <div><strong>Tamanho:</strong> <?= $fileSize ?></div>
            <div><strong>Triângulos:</strong> <span id="triangle-count">—</span></div>
            <div><strong>Dimensões:</strong> <span id="model-dimensions">—</span></div>
            <div id="info-bodies" style="display:none;">
                <strong>Bodies:</strong> <span id="body-count">—</span>
            </div>
            <?php if ($is3MF): ?>
            <div><strong>Meshes:</strong> <span id="mesh-count">—</span></div>
            <?php endif; ?>
        </div>

        <!-- Hints -->
        <div class="viewer-controls-hint">
            <span><kbd>Botão esq.</kbd> Rotacionar</span>
            <span><kbd>Botão dir.</kbd> Mover</span>
            <span><kbd>Scroll</kbd> Zoom</span>
            <span><kbd>R</kbd> Reset</span>
            <span><kbd>W</kbd> Wireframe</span>
            <span><kbd>F</kbd> Tela cheia</span>
        </div>
    </div>

</div>

<script type="module">
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
<?php if ($is3MF): ?>
import { ThreeMFLoader } from 'three/addons/loaders/3MFLoader.js';
<?php else: ?>
import { STLLoader } from 'three/addons/loaders/STLLoader.js';
<?php endif; ?>

// ============================================
// Config PHP → JS
// ============================================
const FILE_URL  = '<?= $fileUrl ?>';
const FILE_TYPE = '<?= $fileExt ?>';
const BACK_URL  = '<?= $backUrl ?>';

// ============================================
// Botão voltar
// ============================================
window.handleBack = function(e) {
    if (window.history.length > 1 && document.referrer &&
        document.referrer.startsWith(window.location.origin)) {
        e.preventDefault();
        window.history.back();
    }
};

// ============================================
// Cena, câmera, renderer
// ============================================
const container = document.getElementById('viewer-container');
const canvas    = document.getElementById('stl-canvas');

const scene = new THREE.Scene();
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
    antialias:             true,
    preserveDrawingBuffer: true
});
renderer.setPixelRatio(window.devicePixelRatio);
renderer.setSize(container.clientWidth, container.clientHeight);
renderer.shadowMap.enabled   = true;
renderer.shadowMap.type      = THREE.PCFSoftShadowMap;
renderer.outputColorSpace    = THREE.SRGBColorSpace;
renderer.toneMapping         = THREE.ACESFilmicToneMapping;
renderer.toneMappingExposure = 1.2;

// ============================================
// OrbitControls
// ============================================
const controls = new OrbitControls(camera, canvas);
controls.enableDamping      = true;
controls.dampingFactor      = 0.08;
controls.screenSpacePanning = true;
controls.minDistance        = 0.1;
controls.maxDistance        = 5000;
controls.rotateSpeed        = 0.8;
controls.zoomSpeed          = 1.2;
controls.panSpeed           = 0.8;

// ============================================
// Iluminação
// ============================================
scene.add(new THREE.AmbientLight(0xffffff, 0.55));

const dirLight1 = new THREE.DirectionalLight(0xffffff, 0.9);
dirLight1.position.set(5, 10, 7);
dirLight1.castShadow            = true;
dirLight1.shadow.mapSize.width  = 2048;
dirLight1.shadow.mapSize.height = 2048;
scene.add(dirLight1);

const dirLight2 = new THREE.DirectionalLight(0xd0e8ff, 0.45);
dirLight2.position.set(-5, 2, -4);
scene.add(dirLight2);

const dirLight3 = new THREE.DirectionalLight(0xfff5e0, 0.3);
dirLight3.position.set(0, -5, -5);
scene.add(dirLight3);

scene.add(new THREE.PointLight(0x4488ff, 0.4, 100));

// ============================================
// Grid e Eixos
// ============================================
let gridHelper = null;
let axesHelper = null;

function createGrid(size) {
    if (gridHelper) scene.remove(gridHelper);
    gridHelper = new THREE.GridHelper(size, 20, 0x30363d, 0x21262d);
    gridHelper.material.opacity     = 0.7;
    gridHelper.material.transparent = true;
    scene.add(gridHelper);
}

function createAxes(size) {
    if (axesHelper) scene.remove(axesHelper);
    axesHelper = new THREE.AxesHelper(size);
    scene.add(axesHelper);
}

createGrid(10);
createAxes(1);

// ============================================
// Paleta de cores padrão para multi-body
// ============================================
const PALETTE = [
    0x4488ff, 0xff4455, 0x44ff88,
    0xffaa00, 0xaa44ff, 0x00ccff,
    0xff88aa, 0x88ff44, 0xff6600,
    0x00ffcc, 0xff00aa, 0xccff00
];

function paletteHex(index) {
    const c = PALETTE[index % PALETTE.length];
    return '#' + c.toString(16).padStart(6, '0');
}

// ============================================
// Materiais (STL single-body)
// ============================================
let currentColor = 0x4488ff;

const solidMaterial = new THREE.MeshPhongMaterial({
    color:     currentColor,
    specular:  0x222244,
    shininess: 60,
    side:      THREE.DoubleSide,
});

const wireMaterial = new THREE.MeshBasicMaterial({
    color:       0xffffff,
    wireframe:   true,
    opacity:     0.15,
    transparent: true,
});

// ============================================
// Estado global
// ============================================
let rootObject       = null;   // Group raiz (single ou multi-body)
let wireMesh         = null;   // só single-body
let bodyMeshes       = [];     // multi-body: array de Mesh
let isMultiBody      = false;
let originalMats3MF  = [];
let renderMode       = 'solid';
let showAxes         = true;
let showGrid         = true;
let modelBox         = null;
let bodyPanelVisible = false;

// ============================================
// Utilitários
// ============================================
function fitCameraToModel(center, size) {
    const maxDim = Math.max(size.x, size.y, size.z);
    const fovRad = THREE.MathUtils.degToRad(camera.fov);
    let   dist   = (maxDim / 2) / Math.tan(fovRad / 2);
    dist *= 1.7;

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

function populateInfo(triCount, size) {
    document.getElementById('triangle-count').textContent =
        Math.round(triCount).toLocaleString('pt-BR');
    document.getElementById('model-dimensions').textContent =
        `${size.x.toFixed(1)} × ${size.y.toFixed(1)} × ${size.z.toFixed(1)} mm`;
}

function adjustHelpers(modelSize, modelBoxRef) {
    const gridSize = Math.max(modelSize.x, modelSize.z) * 4;
    createGrid(gridSize);
    createAxes(modelSize.x * 0.6);
    if (gridHelper) gridHelper.position.y = modelBoxRef.min.y - 0.01;
}

function hideLoading() {
    document.getElementById('viewer-loading').classList.add('hidden');
}

function showLoadingError(msg) {
    document.getElementById('viewer-loading').innerHTML = `
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none"
             stroke="#dc2626" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <p style="color:#dc2626;font-size:0.9rem;">${msg}</p>
        <p style="font-size:0.78rem;">Verifique se o arquivo está correto.</p>`;
}

function onProgress(xhr) {
    if (xhr.total > 0) {
        const pct = Math.round((xhr.loaded / xhr.total) * 100);
        const el  = document.getElementById('loading-progress');
        if (el) el.textContent = pct + '%';
    }
}

// ============================================
// Detecção de bodies por conectividade (BFS)
// ============================================
function detectBodies(geometry) {
    const position  = geometry.attributes.position;
    const faceCount = position.count / 3;

    // Mapa: chave de vértice → lista de faces
    const vertexToFaces = new Map();
    for (let f = 0; f < faceCount; f++) {
        for (let v = 0; v < 3; v++) {
            const idx = f * 3 + v;
            const key = `${position.getX(idx).toFixed(5)},`
                      + `${position.getY(idx).toFixed(5)},`
                      + `${position.getZ(idx).toFixed(5)}`;
            if (!vertexToFaces.has(key)) vertexToFaces.set(key, []);
            vertexToFaces.get(key).push(f);
        }
    }

    // BFS para encontrar ilhas de faces conectadas
    const visited = new Uint8Array(faceCount);
    const groups  = [];

    for (let start = 0; start < faceCount; start++) {
        if (visited[start]) continue;

        const group = [];
        const queue = [start];
        visited[start] = 1;

        while (queue.length > 0) {
            const face = queue.shift();
            group.push(face);

            for (let v = 0; v < 3; v++) {
                const idx = face * 3 + v;
                const key = `${position.getX(idx).toFixed(5)},`
                          + `${position.getY(idx).toFixed(5)},`
                          + `${position.getZ(idx).toFixed(5)}`;

                for (const neighbor of (vertexToFaces.get(key) ?? [])) {
                    if (!visited[neighbor]) {
                        visited[neighbor] = 1;
                        queue.push(neighbor);
                    }
                }
            }
        }

        groups.push(group);
    }

    return groups;
}

// ============================================
// Constrói meshes individuais por body
// ============================================
function buildBodyMeshes(geometry, groups) {
    const position = geometry.attributes.position;
    const normal   = geometry.attributes.normal;
    const meshes   = [];

    groups.forEach((faces, idx) => {
        const vertCount = faces.length * 3;
        const positions = new Float32Array(vertCount * 3);
        const normals   = new Float32Array(vertCount * 3);

        faces.forEach((faceIdx, i) => {
            for (let v = 0; v < 3; v++) {
                const src = faceIdx * 3 + v;
                const dst = i * 3 + v;

                positions[dst * 3]     = position.getX(src);
                positions[dst * 3 + 1] = position.getY(src);
                positions[dst * 3 + 2] = position.getZ(src);

                if (normal) {
                    normals[dst * 3]     = normal.getX(src);
                    normals[dst * 3 + 1] = normal.getY(src);
                    normals[dst * 3 + 2] = normal.getZ(src);
                }
            }
        });

        const geo = new THREE.BufferGeometry();
        geo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        geo.setAttribute('normal',   new THREE.BufferAttribute(normals,   3));
        if (!normal) geo.computeVertexNormals();

        const colorHex = PALETTE[idx % PALETTE.length];
        const mat      = new THREE.MeshPhongMaterial({
            color:     colorHex,
            specular:  0x222244,
            shininess: 60,
            side:      THREE.DoubleSide,
        });

        const mesh = new THREE.Mesh(geo, mat);
        mesh.castShadow    = true;
        mesh.receiveShadow = true;
        mesh.userData.bodyIndex = idx;
        mesh.userData.colorHex  = colorHex;
        meshes.push(mesh);
    });

    return meshes;
}

// ============================================
// Painel de cores dos bodies
// ============================================
function buildBodyColorPanel(meshes) {
    const list = document.getElementById('body-colors-list');
    list.innerHTML = '';

    meshes.forEach((mesh, idx) => {
        const hex = paletteHex(idx);
        const row = document.createElement('div');
        row.className = 'body-color-row';
        row.innerHTML = `
            <div class="body-color-dot" id="dot-${idx}"
                 style="background:${hex};"></div>
            <input type="color" value="${hex}"
                   data-idx="${idx}"
                   title="Body ${idx + 1}">
            <span>Body ${idx + 1}</span>`;
        list.appendChild(row);

        row.querySelector('input').addEventListener('input', e => {
            const i   = parseInt(e.target.dataset.idx);
            const val = e.target.value;
            setBodyColor(i, val);
            document.getElementById(`dot-${i}`).style.background = val;
        });
    });

    // Mostra botão e atualiza contador
    document.getElementById('btn-bodies').style.display = '';
    const infoEl = document.getElementById('info-bodies');
    infoEl.style.display = '';
    document.getElementById('body-count').textContent = meshes.length;
}

window.toggleBodyPanel = function() {
    bodyPanelVisible = !bodyPanelVisible;
    document.getElementById('body-colors-panel')
            .classList.toggle('visible', bodyPanelVisible);
    document.getElementById('btn-bodies')
            .classList.toggle('active', bodyPanelVisible);
};

window.setBodyColor = function(idx, hexStr) {
    if (!bodyMeshes[idx]) return;
    bodyMeshes[idx].material.color.set(hexStr);
    bodyMeshes[idx].userData.colorHex =
        parseInt(hexStr.replace('#', ''), 16);
};

// ============================================
// Loader STL
// ============================================
function loadSTL() {
    const loader = new STLLoader();
    loader.load(
        FILE_URL,
        function(geometry) {
            // Corrige rotação Z-up → Y-up na própria geometria
            geometry.applyMatrix4(
                new THREE.Matrix4().makeRotationX(-Math.PI / 2)
            );

            geometry.computeVertexNormals();
            geometry.computeBoundingBox();

            const box    = geometry.boundingBox;
            const center = new THREE.Vector3();
            box.getCenter(center);
            const size   = new THREE.Vector3();
            box.getSize(size);
            const maxDim = Math.max(size.x, size.y, size.z);
            const scale  = 4.0 / maxDim;

            geometry.translate(-center.x, -center.y, -center.z);

            // ---- Detecta bodies ----
            const groups = detectBodies(geometry);
            console.log(`STL: ${groups.length} body(ies) detectado(s)`);

            const parent = new THREE.Group();
            parent.scale.set(scale, scale, scale);

            if (groups.length > 1) {
                // Multi-body
                isMultiBody = true;
                bodyMeshes  = buildBodyMeshes(geometry, groups);
                bodyMeshes.forEach(m => parent.add(m));

                // Oculta seletor de cor única, mostra botão bodies
                document.getElementById('single-color-picker').style.display = 'none';
                buildBodyColorPanel(bodyMeshes);

            } else {
                // Single-body
                isMultiBody = false;
                const solidMesh = new THREE.Mesh(geometry, solidMaterial);
                solidMesh.castShadow    = true;
                solidMesh.receiveShadow = true;
                parent.add(solidMesh);

                wireMesh = new THREE.Mesh(geometry, wireMaterial);
                wireMesh.visible = false;
                parent.add(wireMesh);
            }

            scene.add(parent);
            rootObject = parent;

            modelBox = new THREE.Box3().setFromObject(rootObject);
            const modelSize   = new THREE.Vector3();
            const modelCenter = new THREE.Vector3();
            modelBox.getSize(modelSize);
            modelBox.getCenter(modelCenter);

            fitCameraToModel(modelCenter, modelSize);
            adjustHelpers(modelSize, modelBox);

            const triCount = geometry.index
                ? geometry.index.count / 3
                : geometry.attributes.position.count / 3;

            populateInfo(triCount, size);
            hideLoading();
        },
        onProgress,
        () => showLoadingError('Erro ao carregar o arquivo STL.')
    );
}

// ============================================
// Loader 3MF
// ============================================
function load3MF() {
    const loader = new ThreeMFLoader();
    loader.load(
        FILE_URL,
        function(group) {
            // Rotação Z-up → Y-up
            group.rotation.x = -Math.PI / 2;
            group.updateMatrixWorld(true);

            const box = new THREE.Box3().setFromObject(group);

            if (!isFinite(box.min.x) || !isFinite(box.max.x)) {
                showLoadingError('Arquivo 3MF não contém geometria válida.');
                return;
            }

            const center = new THREE.Vector3();
            box.getCenter(center);
            const size   = new THREE.Vector3();
            box.getSize(size);
            const maxDim = Math.max(size.x, size.y, size.z);
            const scale  = maxDim > 0 ? 4.0 / maxDim : 1.0;

            group.position.set(
                -center.x * scale,
                -center.y * scale,
                -center.z * scale
            );
            group.scale.set(scale, scale, scale);

            scene.add(group);
            rootObject = group;

            originalMats3MF = [];
            let triCount  = 0;
            let meshCount = 0;

            group.traverse(child => {
                if (!child.isMesh) return;
                meshCount++;
                child.castShadow    = true;
                child.receiveShadow = true;

                originalMats3MF.push({
                    mesh:     child,
                    material: Array.isArray(child.material)
                        ? child.material.map(m => m.clone())
                        : child.material.clone()
                });

                if (child.geometry?.attributes?.position) {
                    triCount += child.geometry.index
                        ? child.geometry.index.count / 3
                        : child.geometry.attributes.position.count / 3;
                }
            });

            group.updateMatrixWorld(true);
            modelBox = new THREE.Box3().setFromObject(rootObject);

            const modelSize   = new THREE.Vector3();
            const modelCenter = new THREE.Vector3();
            modelBox.getSize(modelSize);
            modelBox.getCenter(modelCenter);

            fitCameraToModel(modelCenter, modelSize);
            adjustHelpers(modelSize, modelBox);
            populateInfo(triCount, size);

            const meshEl = document.getElementById('mesh-count');
            if (meshEl) meshEl.textContent = meshCount;

            console.log(`3MF: ${meshCount} meshes, ${Math.round(triCount).toLocaleString()} triângulos`);

            hideLoading();
        },
        onProgress,
        (err) => {
            console.error('Erro 3MF:', err);
            showLoadingError('Erro ao carregar o arquivo 3MF: ' + (err?.message ?? err));
        }
    );
}

// ============================================
// Inicializa
// ============================================
setTimeout(() => {
    if (FILE_TYPE === '3mf') load3MF();
    else loadSTL();
}, 0);

// ============================================
// Modo de renderização
// ============================================
window.setRenderMode = function(mode) {
    renderMode = mode;
    ['solid','wireframe','both'].forEach(m =>
        document.getElementById('btn-' + m).classList.remove('active')
    );
    document.getElementById('btn-' + mode).classList.add('active');

    // ---- STL multi-body ----
    if (isMultiBody) {
        bodyMeshes.forEach(mesh => {
            switch (mode) {
                case 'solid':
                    mesh.material = new THREE.MeshPhongMaterial({
                        color:     mesh.userData.colorHex,
                        specular:  0x222244,
                        shininess: 60,
                        side:      THREE.DoubleSide,
                    });
                    // Remove wireframe overlays
                    mesh.children
                        .filter(c => c.userData.isWireOverlay)
                        .forEach(c => mesh.remove(c));
                    break;

                case 'wireframe':
                    mesh.material = new THREE.MeshBasicMaterial({
                        color:     mesh.userData.colorHex,
                        wireframe: true,
                    });
                    mesh.children
                        .filter(c => c.userData.isWireOverlay)
                        .forEach(c => mesh.remove(c));
                    break;

                case 'both':
                    mesh.material = new THREE.MeshPhongMaterial({
                        color:     mesh.userData.colorHex,
                        specular:  0x222244,
                        shininess: 60,
                        side:      THREE.DoubleSide,
                    });
                    mesh.children
                        .filter(c => c.userData.isWireOverlay)
                        .forEach(c => mesh.remove(c));

                    const wGeo  = new THREE.WireframeGeometry(mesh.geometry);
                    const wLine = new THREE.LineSegments(wGeo,
                        new THREE.LineBasicMaterial({
                            color: 0x000000, opacity: 0.15, transparent: true
                        })
                    );
                    wLine.userData.isWireOverlay = true;
                    mesh.add(wLine);
                    break;
            }
        });
        return;
    }

    // ---- STL single-body ----
    if (FILE_TYPE !== '3mf') {
        if (!rootObject) return;
        const solidMesh = rootObject.children[0];
        const wMesh     = rootObject.children[1];
        if (!solidMesh) return;

        switch (mode) {
            case 'solid':
                solidMesh.material = solidMaterial;
                if (wMesh) wMesh.visible = false;
                break;
            case 'wireframe':
                solidMesh.material = new THREE.MeshBasicMaterial({
                    color: currentColor, wireframe: true
                });
                if (wMesh) wMesh.visible = false;
                break;
            case 'both':
                solidMesh.material = solidMaterial;
                if (wMesh) wMesh.visible = true;
                break;
        }
        return;
    }

    // ---- 3MF ----
    if (!rootObject) return;

    rootObject.traverse(child => {
        if (child.userData.isWireframeOverlay) child.parent?.remove(child);
    });

    rootObject.traverse(child => {
        if (!child.isMesh || child.userData.isWireframeOverlay) return;
        const orig = originalMats3MF.find(o => o.mesh === child);

        switch (mode) {
            case 'solid':
                if (orig) child.material = Array.isArray(orig.material)
                    ? orig.material.map(m => m.clone())
                    : orig.material.clone();
                break;
            case 'wireframe':
                child.material = new THREE.MeshBasicMaterial({
                    color: 0x00aaff, wireframe: true
                });
                break;
            case 'both':
                if (orig) child.material = Array.isArray(orig.material)
                    ? orig.material.map(m => m.clone())
                    : orig.material.clone();
                const wGeo  = new THREE.WireframeGeometry(child.geometry);
                const wLine = new THREE.LineSegments(wGeo,
                    new THREE.LineBasicMaterial({
                        color: 0x000000, opacity: 0.12, transparent: true
                    })
                );
                wLine.userData.isWireframeOverlay = true;
                child.add(wLine);
                break;
        }
    });
};

// ============================================
// Cor única (single-body STL)
// ============================================
window.setModelColor = function(hexStr) {
    currentColor = parseInt(hexStr.replace('#', ''), 16);
    solidMaterial.color.setHex(currentColor);
    solidMaterial.needsUpdate = true;
};

window.setBgColor = function(hexStr) {
    scene.background = new THREE.Color(hexStr);
};

// ============================================
// Eixos / Grid / Reset / Fullscreen
// ============================================
window.toggleAxes = function() {
    showAxes = !showAxes;
    if (axesHelper) axesHelper.visible = showAxes;
    document.getElementById('btn-axes').classList.toggle('active', showAxes);
};

window.toggleGrid = function() {
    showGrid = !showGrid;
    if (gridHelper) gridHelper.visible = showGrid;
    document.getElementById('btn-grid').classList.toggle('active', showGrid);
};

window.resetCamera = function() {
    if (!modelBox) return;
    const size   = new THREE.Vector3();
    const center = new THREE.Vector3();
    modelBox.getSize(size);
    modelBox.getCenter(center);
    fitCameraToModel(center, size);
};

window.toggleFullscreen = function() {
    if (!document.fullscreenElement) {
        container.requestFullscreen().catch(err => console.warn(err));
    } else {
        document.exitFullscreen();
    }
};

document.addEventListener('fullscreenchange', () => {
    document.getElementById('btn-fullscreen')
            .classList.toggle('active', !!document.fullscreenElement);
    if (!document.fullscreenElement) onResize();
});

// ============================================
// Teclado
// ============================================
document.addEventListener('keydown', e => {
    if (e.target.tagName === 'INPUT') return;
    switch (e.key.toLowerCase()) {
        case 'r': window.resetCamera();              break;
        case 'w': window.setRenderMode('wireframe'); break;
        case 's': window.setRenderMode('solid');     break;
        case 'b': window.setRenderMode('both');      break;
        case 'g': window.toggleGrid();               break;
        case 'a': window.toggleAxes();               break;
        case 'f': window.toggleFullscreen();         break;
    }
});

// ============================================
// Resize
// ============================================
function onResize() {
    const w = container.clientWidth;
    const h = container.clientHeight;
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
    renderer.setSize(w, h);
}
window.addEventListener('resize', onResize);

// ============================================
// Loop de animação
// ============================================
let animFrameId = null;

function animate() {
    animFrameId = requestAnimationFrame(animate);
    controls.update();
    renderer.render(scene, camera);
}
animate();

window.addEventListener('beforeunload', () => {
    cancelAnimationFrame(animFrameId);
    renderer.dispose();
    controls.dispose();
});
</script>

</body>
</html>
