<?php
// Only start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$loggedMaintainer = '';
if (isset($_SESSION['user']) && isset($_SESSION['user']['matricule'])) {
    $loggedMaintainer = htmlspecialchars($_SESSION['user']['matricule']);
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
<!-- <script src="https://unpkg.com/html5-qrcode@2.3.10/minified/html5-qrcode.min.js"></script> -->

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Affectation machine – équipement (Scan)</title>
    <link rel="icon" type="image/x-icon" href="/public/images/images.png" />
    <link href="/platform_gmao/public/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="/platform_gmao/public/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/platform_gmao/public/css/ImplantationMachBox.css">

    <style>
        .scan-card { max-width: 900px; margin: 0 auto; }
        .scanner-box { border: 2px dashed #4e73df; border-radius: 8px; padding: 12px; background: #f8f9fc; }
        .scanner-header { display: flex; align-items: center; justify-content: space-between; }
        .scanner-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .value-badge { font-weight: 600; color: #1cc88a; }
        .hint { font-size: 0.9rem; color: #6c757d; }
        .form-section { margin-top: 16px; }
        @media (max-width: 576px) {
            .scanner-actions { width: 100%; }
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include(__DIR__ . "/../../layout/sidebar.php"); ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include(__DIR__ . "/../../layout/navbar.php"); ?>
                <div class="container-fluid">


                    <div class="card shadow mb-4 scan-card">
                        <div class="card-header py-3 d-flex align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Affectation machine – équipement (Scan)</h6>
                            <div class="d-flex gap-2">
                                <a href="?route=equipement_machine" class="btn btn-outline-secondary">Retour à la liste</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="affectationForm" method="POST" action="/platform_gmao/public/index.php?route=equipement_machine/affecter">
                                <?php if (!empty($loggedMaintainer)) : ?>
                                    <input type="hidden" name="maintainer" id="maintainer" value="<?php echo $loggedMaintainer; ?>">
                                <?php else : ?>
                                    <div class="mb-3">
                                        <label for="maintainer" class="form-label">Maintenicien (matricule)</label>
                                        <input class="form-control" name="maintainer" id="maintainer" placeholder="Saisir le matricule du maintenicien" required>
                                    </div>
                                <?php endif; ?>
                                <input type="hidden" name="mvt_state" value="SS">

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="scanner-box">
                                            <div class="scanner-header mb-2">
                                                <div>
                                                    <h5 class="text-primary mb-0">1) Scanner l'équipement</h5>
                                                    <div class="hint">QR/Code-barres de l'équipement</div>
                                                </div>
                                                <div class="scanner-actions">
                                                    <button type="button" id="startScanEquip" class="btn btn-sm btn-primary"><i class="fas fa-qrcode"></i> Scanner</button>
                                                    <button type="button" id="stopScanEquip" class="btn btn-sm btn-outline-secondary" disabled>Stop</button>
                                                </div>
                                            </div>
                                            <div id="equipScanner" style="width: 100%; min-height: 220px;"></div>
                                            <div class="form-section">
                                                <label for="equipment_id" class="form-label">Code équipement</label>
                                                <input class="form-control" name="equipment_id" id="equipment_id" placeholder="Scannez ou saisissez manuellement" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <div class="scanner-box">
                                            <div class="scanner-header mb-2">
                                                <div>
                                                    <h5 class="text-primary mb-0">2) Scanner la machine</h5>
                                                    <div class="hint">QR/Code-barres de la machine</div>
                                                </div>
                                                <div class="scanner-actions">
                                                    <button type="button" id="startScanMach" class="btn btn-sm btn-primary"><i class="fas fa-qrcode"></i> Scanner</button>
                                                    <button type="button" id="stopScanMach" class="btn btn-sm btn-outline-secondary" disabled>Stop</button>
                                                </div>
                                            </div>
                                            <div id="machScanner" style="width: 100%; min-height: 220px;"></div>
                                            <div class="form-section">
                                                <label for="machine_id" class="form-label">Code machine</label>
                                                <input class="form-control" name="machine_id" id="machine_id" placeholder="Scannez ou saisissez manuellement" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 d-flex align-items-center justify-content-between">
                                    <div class="hint">Les champs optionnels (heure/date d'allocation) ne sont pas requis.</div>
                                    <button id="submitBtn" type="submit" class="btn btn-success" disabled>Affecter</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include(__DIR__ . "/../../layout/footer.php"); ?>
        </div>
    </div>

    <script src="/platform_gmao/public/js/jquery-3.6.4.min.js"></script>
    <script src="/platform_gmao/public/js/bootstrap.bundle.min.js"></script>
    <script src="/platform_gmao/public/js/sb-admin-2.min.js"></script>
    <script src="/platform_gmao/public/js/sideBare.js"></script>
    <script src="/platform_gmao/public/js/html5-qrcode.min.js"></script>
    <!-- Body -->

<script>
document.addEventListener("DOMContentLoaded", function() {
    if (!window.Html5Qrcode) {
        alert("La librairie Html5Qrcode n'est pas chargée.");
        return;
    }

    // Ton code de scan ici
});
</script>

    <!-- <script>
        // Fallback to CDN if local file is missing
        if (!window.Html5Qrcode) {
            var cdn = document.createElement('script');
            cdn.src = 'https://unpkg.com/html5-qrcode@2.3.10/minified/html5-qrcode.min.js';
            document.head.appendChild(cdn);
        }
    </script> -->
    <script>
        (function() {
            const equipmentInput = document.getElementById('equipment_id');
            const machineInput = document.getElementById('machine_id');
            const submitBtn = document.getElementById('submitBtn');
            const maintainer = document.getElementById('maintainer');

            function updateSubmitState() {
                const ready = equipmentInput.value.trim() !== '' && machineInput.value.trim() !== '' && maintainer.value.trim() !== '';
                submitBtn.disabled = !ready;
            }

            equipmentInput.addEventListener('input', updateSubmitState);
            machineInput.addEventListener('input', updateSubmitState);

            // Html5Qrcode instances
            let equipScanner = null;
            let machScanner = null;

            function playBeep() {
                try { new Audio('data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEAESsAACJWAAACABAAZGF0YQAAAAA=').play(); } catch (e) {}
            }

            async function startScanner(targetId, onSuccess, controls) {
                const { startBtn, stopBtn } = controls;
                // Preferred: html5-qrcode when available
                if (window.Html5Qrcode && window.Html5QrcodeSupportedFormats) {
                    const qr = new Html5Qrcode(targetId, { verbose: false });
                    const config = { fps: 10, qrbox: { width: 250, height: 250 }, aspectRatio: 1.7778, rememberLastUsedCamera: true, formatsToSupport: [
                        Html5QrcodeSupportedFormats.QR_CODE,
                        Html5QrcodeSupportedFormats.CODE_128,
                        Html5QrcodeSupportedFormats.CODE_39,
                        Html5QrcodeSupportedFormats.EAN_13,
                        Html5QrcodeSupportedFormats.EAN_8,
                        Html5QrcodeSupportedFormats.UPC_A,
                        Html5QrcodeSupportedFormats.UPC_E
                    ] };
                    try {
                        const cameras = await Html5Qrcode.getCameras();
                        const cameraId = cameras && cameras.length ? cameras[0].id : { facingMode: 'environment' };
                        await qr.start(cameraId, config, (decodedText) => {
                            playBeep();
                            onSuccess(decodedText);
                            updateSubmitState();
                        });
                        startBtn.disabled = true;
                        stopBtn.disabled = false;
                        return qr;
                    } catch (err) {
                        alert('Impossible de démarrer la caméra: ' + err);
                        try { await qr.stop(); } catch (_) {}
                        return null;
                    }
                }

                // Fallback: Native BarcodeDetector if supported
                if ("BarcodeDetector" in window) {
                    const target = document.getElementById(targetId);
                    // Clean previous
                    target.innerHTML = '';
                    const video = document.createElement('video');
                    video.setAttribute('playsinline', 'true');
                    video.style.width = '100%';
                    video.style.maxHeight = '260px';
                    target.appendChild(video);

                    let rafId = null;
                    let stream = null;
                    const detector = new BarcodeDetector({ formats: ['qr_code','code_128','code_39','ean_13','ean_8','upc_a','upc_e'] });
                    try {
                        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
                        video.srcObject = stream;
                        await video.play();
                    } catch (err) {
                        alert('Accès caméra refusé: ' + err);
                        return null;
                    }

                    const tick = async () => {
                        try {
                            const barcodes = await detector.detect(video);
                            if (barcodes && barcodes.length) {
                                const value = barcodes[0].rawValue || barcodes[0].cornerPoints ? barcodes[0].rawValue : '';
                                if (value) {
                                    playBeep();
                                    onSuccess(value);
                                    updateSubmitState();
                                }
                            }
                        } catch (_) {
                            // ignore frame errors
                        }
                        rafId = requestAnimationFrame(tick);
                    };
                    rafId = requestAnimationFrame(tick);

                    startBtn.disabled = true;
                    stopBtn.disabled = false;

                    return {
                        stop: () => new Promise((resolve) => {
                            if (rafId) cancelAnimationFrame(rafId);
                            if (video) {
                                try { video.pause(); } catch (_) {}
                                video.srcObject = null;
                            }
                            if (stream) {
                                stream.getTracks().forEach(t => t.stop());
                            }
                            // Clear UI
                            try { target.innerHTML = ''; } catch (_) {}
                            resolve();
                        }),
                        clear: () => { try { target.innerHTML = ''; } catch (_) {} }
                    };
                }

                alert('Aucun moteur de scan disponible. Utilisez la saisie manuelle ou connectez-vous à Internet.');
                return null;
            }

            function stopScanner(instance, controls) {
                const { startBtn, stopBtn } = controls;
                if (!instance) return;
                const stopper = instance.stop && typeof instance.stop === 'function' ? instance.stop() : Promise.resolve();
                stopper.then(() => {
                    try { if (instance.clear) instance.clear(); } catch (_) {}
                    startBtn.disabled = false;
                    stopBtn.disabled = true;
                }).catch(() => {
                    startBtn.disabled = false;
                    stopBtn.disabled = true;
                });
            }

            const equipControls = { startBtn: document.getElementById('startScanEquip'), stopBtn: document.getElementById('stopScanEquip') };
            const machControls = { startBtn: document.getElementById('startScanMach'), stopBtn: document.getElementById('stopScanMach') };

            equipControls.startBtn.addEventListener('click', async () => {
                if (equipScanner) stopScanner(equipScanner, equipControls);
                equipScanner = await startScanner('equipScanner', (text) => { equipmentInput.value = text; }, equipControls);
            });
            equipControls.stopBtn.addEventListener('click', () => stopScanner(equipScanner, equipControls));

            machControls.startBtn.addEventListener('click', async () => {
                if (machScanner) stopScanner(machScanner, machControls);
                machScanner = await startScanner('machScanner', (text) => { machineInput.value = text; }, machControls);
            });
            machControls.stopBtn.addEventListener('click', () => stopScanner(machScanner, machControls));

            updateSubmitState();
        })();
    </script>
</body>

</html>


