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
        .scan-card {
            max-width: 900px;
            margin: 0 auto;
        }

        .scanner-box {
            border: 2px dashed #4e73df;
            border-radius: 8px;
            padding: 12px;
            background: #f8f9fc;
        }

        .scanner-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .scanner-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .value-badge {
            font-weight: 600;
            color: #1cc88a;
        }

        .hint {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .form-section {
            margin-top: 16px;
        }

        .input-group .btn {
            border-top-left-radius: 6px;
            border-bottom-left-radius: 6px;
        }

        .input-group .form-control {
            border-top-right-radius: 6px;
            border-bottom-right-radius: 6px;
        }

        @media (max-width: 576px) {
            .scanner-actions {
                width: 100%;
            }
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
                                <a href="?route=equipement_machine" class="btn btn-outline-secondary">Retour à la
                                    liste</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($_SESSION['flash_success'])): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($_SESSION['flash_error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            <?php endif; ?>
                            <div id="alertContainer"></div>
                            <form id="affectationForm" method="POST"
                                action="/platform_gmao/public/index.php?route=equipement_machine/affecter">
                                <?php if (!empty($loggedMaintainer)): ?>
                                    <input type="hidden" name="maintainer" id="maintainer"
                                        value="<?php echo $loggedMaintainer; ?>">
                                <?php else: ?>
                                    <div class="mb-3">
                                        <label for="maintainer" class="form-label">Maintenicien (matricule)</label>
                                        <input class="form-control" name="maintainer" id="maintainer"
                                            placeholder="Saisir le matricule du maintenicien" required>
                                    </div>
                                <?php endif; ?>
                                <input type="hidden" name="mvt_state" value="SS">

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="scanner-box">
                                            <div class="scanner-header mb-2">
                                                <div>
                                                    <h5 class="text-primary mb-0">1) Scanner l'équipement</h5>
                                                    <div class="hint">QR de l'équipement</div>
                                                </div>
                                                <div class="scanner-actions"></div>
                                            </div>
                                            <input id="equipment_photo" type="file" accept="image/*" capture="environment" style="display:none;">
                                            <div class="form-section">
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <button type="button" id="btnEquipPhoto"
                                                            class="btn btn-outline-primary" aria-label="Scanner QR équipement" title="Scanner QR équipement">
                                                            <i class="fas fa-qrcode"></i>
                                                        </button>
                                                    </div>
                                                    <input class="form-control" name="equipment_id" id="equipment_id"
                                                        placeholder="Scannez ou saisissez manuellement" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <div class="scanner-box">
                                            <div class="scanner-header mb-2">
                                                <div>
                                                    <h5 class="text-primary mb-0">2) Scanner la machine</h5>
                                                    <div class="hint">QR de la machine</div>
                                                </div>
                                                <div class="scanner-actions"></div>
                                            </div>
                                            <input id="machine_photo" type="file" accept="image/*" capture="environment" style="display:none;">
                                            <div class="form-section">
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <button type="button" id="btnMachPhoto"
                                                            class="btn btn-outline-primary" aria-label="Scanner QR machine" title="Scanner QR machine">
                                                            <i class="fas fa-qrcode"></i>
                                                        </button>
                                                    </div>
                                                    <input class="form-control" name="machine_id" id="machine_id"
                                                        placeholder="Scannez ou saisissez manuellement" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 d-flex align-items-center justify-content-between">
                                    <div class="hint">Les champs optionnels (heure/date d'allocation) ne sont pas
                                        requis.</div>
                                    <button id="submitBtn" type="submit" class="btn btn-success"
                                        disabled>Affecter</button>
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

    <script>
        (function () {
            const equipmentInput = document.getElementById('equipment_id');
            const machineInput = document.getElementById('machine_id');
            const submitBtn = document.getElementById('submitBtn');
            const maintainer = document.getElementById('maintainer');
            const alertContainer = document.getElementById('alertContainer');
            const equipmentPhotoInput = document.getElementById('equipment_photo');
            const machinePhotoInput = document.getElementById('machine_photo');
            const btnEquipPhoto = document.getElementById('btnEquipPhoto');
            const btnMachPhoto = document.getElementById('btnMachPhoto');
            // Removed filename and preview elements for a cleaner UI

            function updateSubmitState() {
                const ready = equipmentInput.value.trim() !== '' && machineInput.value.trim() !== '' && maintainer.value.trim() !== '';
                submitBtn.disabled = !ready;
            }

            equipmentInput.addEventListener('input', updateSubmitState);
            machineInput.addEventListener('input', updateSubmitState);

            // Photo capture handlers (Python/OpenCV backend)
            btnEquipPhoto.addEventListener('click', () => equipmentPhotoInput.click());
            btnMachPhoto.addEventListener('click', () => machinePhotoInput.click());

            function showAlert(type, message) {
                if (!alertContainer) return;
                const wrapper = document.createElement('div');
                wrapper.innerHTML = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>`;
                alertContainer.appendChild(wrapper.firstElementChild);
            }

            async function compressImage(file, maxW = 1280, maxH = 1280, quality = 0.8) {
                return new Promise((resolve, reject) => {
                    const img = new Image();
                    const url = URL.createObjectURL(file);
                    img.onload = () => {
                        let { width, height } = img;
                        const ratio = Math.min(maxW / width, maxH / height, 1);
                        width = Math.round(width * ratio);
                        height = Math.round(height * ratio);
                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);
                        canvas.toBlob((blob) => {
                            if (!blob) return reject(new Error('Compression échouée'));
                            resolve(new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), { type: 'image/jpeg' }));
                        }, 'image/jpeg', quality);
                    };
                    img.onerror = () => reject(new Error('Lecture image échouée'));
                    img.src = url;
                });
            }

            async function uploadAndDecode(file, target) {
                const form = new FormData();
                form.append('photo', file);
                form.append('target', target);
                try {
                    const res = await fetch('/platform_gmao/public/index.php?route=scan/decode', {
                        method: 'POST',
                        body: form
                    });

                    const data = await res.json();
                    console.log('Scan result:', data);

                    if (!data.success) {
                        throw new Error(data.error || 'Décodage échoué');
                    }
                    return data;
                } catch (e) {
                    showAlert('danger', 'Erreur de scan: ' + e.message);
                    return null;
                }
            }

            equipmentPhotoInput.addEventListener('change', async (e) => {
                const file = e.target.files && e.target.files[0];
                if (!file) return;
                let payload = file;
                try { payload = await compressImage(file); } catch (e) { }
                const result = await uploadAndDecode(payload, 'equipment');
                if (result && result.text) {
                    equipmentInput.value = result.text;
                    updateSubmitState();
                }
            });

            machinePhotoInput.addEventListener('change', async (e) => {
                const file = e.target.files && e.target.files[0];
                if (!file) return;
                let payload = file;
                try { payload = await compressImage(file); } catch (e) { }
                const result = await uploadAndDecode(payload, 'machine');
                if (result && result.text) {
                    machineInput.value = result.text;
                    updateSubmitState();
                }
            });
            updateSubmitState();
        })();
    </script>
</body>

</html>