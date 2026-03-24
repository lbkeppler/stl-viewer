// ============================================
// Utilitários extras do visualizador STL / 3MF
// ============================================

/**
 * Captura screenshot do canvas e faz download.
 * Requer preserveDrawingBuffer: true no renderer.
 */
function takeSnapshot() {
    const canvas   = document.getElementById('stl-canvas');
    const fileName = document.title.split('—')[0].trim() || 'model-snapshot';

    renderer.render(scene, camera);

    canvas.toBlob(function(blob) {
        const url  = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href     = url;
        link.download = fileName.replace(/\.(stl|3mf)$/i, '') + '_snapshot.png';
        link.click();
        URL.revokeObjectURL(url);
    }, 'image/png', 1.0);
}

/**
 * Calcula volume aproximado em mm³ (apenas STL — malhas fechadas).
 * Para 3MF não é aplicável diretamente pois o model é um Group.
 */
function calcVolume(geometry) {
    let volume = 0;
    const pos  = geometry.attributes.position;

    for (let i = 0; i < pos.count; i += 3) {
        const v1 = new THREE.Vector3().fromBufferAttribute(pos, i);
        const v2 = new THREE.Vector3().fromBufferAttribute(pos, i + 1);
        const v3 = new THREE.Vector3().fromBufferAttribute(pos, i + 2);
        volume  += signedVolumeTriangle(v1, v2, v3);
    }

    return Math.abs(volume);
}

function signedVolumeTriangle(p1, p2, p3) {
    return p1.dot(p2.cross(p3)) / 6.0;
}

/**
 * Formata número com separadores pt-BR
 */
function formatNumber(n) {
    return n.toLocaleString('pt-BR', { maximumFractionDigits: 2 });
}

/**
 * Exibe toast de notificação temporária
 */
function showToast(message, type = 'info', duration = 2500) {
    let toast = document.getElementById('viewer-toast');

    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'viewer-toast';
        toast.style.cssText = `
            position: fixed;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(22,27,34,0.95);
            border: 1px solid #30363d;
            color: #e6edf3;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 0.84rem;
            font-family: 'Inter', sans-serif;
            z-index: 9999;
            pointer-events: none;
            transition: opacity 0.3s ease;
            backdrop-filter: blur(4px);
        `;
        document.body.appendChild(toast);
    }

    const colors = {
        info:    '#2563eb',
        success: '#16a34a',
        warning: '#d97706',
        error:   '#dc2626',
    };

    toast.style.borderColor = colors[type] || colors.info;
    toast.textContent       = message;
    toast.style.opacity     = '1';

    clearTimeout(toast._timeout);
    toast._timeout = setTimeout(() => {
        toast.style.opacity = '0';
    }, duration);
}
