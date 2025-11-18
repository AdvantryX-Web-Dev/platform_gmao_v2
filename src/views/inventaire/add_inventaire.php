<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Vérifie si l'utilisateur est un administrateur
$isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Inventaire </title>
    <link rel="icon" type="image/x-icon" href="/public/images/images.png" />
    <link rel="stylesheet" href="/platform_gmao/public/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="/platform_gmao/public/css/sb-admin-2.min.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/table.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/init_Machine.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/datatables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
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

<body id="page-top">
    <div id="wrapper">
        <?php include(__DIR__ . "/../../views/layout/sidebar.php") ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include(__DIR__ . "/../../views/layout/navbar.php"); ?>

                <div class="container-fluid">
                    <?php if (!empty($_SESSION['flash_success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($_SESSION['flash_success']);
                            unset($_SESSION['flash_success']); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($_SESSION['flash_error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($_SESSION['flash_error']);
                            unset($_SESSION['flash_error']); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    <div id="alertContainer"></div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <?php if ($isAdmin): ?>
                                    ajouter inventaire :ADMINISTRATEUR
                                <?php else: ?>
                                    ajouter inventaire : (<?= htmlspecialchars($_SESSION['user']['matricule'] ?? 'non défini') ?>)
                                <?php endif; ?>
                            </h6>
                        </div>
                        <form method="post" action="/platform_gmao/public/index.php?route=ajouterInventaire">

                            <div class="row mt-3 justify-content-center px-3">
                                <div class="col-12 col-md-6 col-lg-5 mb-3">
                                    <div class="scanner-box m-0 h-100">
                                        <div class="form-col align-items-end g-2">
                                            <div class="form-group mb-3">
                                                <label for="maintener_id" class="mb-1">Maintenancier</label>
                                                <?php if (!$isAdmin && isset($connectedMaintenerId) && $connectedMaintenerId): ?>
                                                    <input type="hidden" name="maintener_id" value="<?= htmlspecialchars($connectedMaintenerId) ?>">
                                                    <input type="text" class="form-control" value="<?= htmlspecialchars($connectedMatricule) ?>" readonly>
                                                <?php else: ?>
                                                    <select class="form-control" id="maintener_id" name="maintener_id">
                                                        <option value="">-- Sélectionner --</option>
                                                        <?php if (!empty($maintenancier)): foreach ($maintenancier as $opt): ?>
                                                                <option value="<?= (int)$opt['id'] ?>"><?= htmlspecialchars($opt['first_name']) ?> <?= htmlspecialchars($opt['last_name']) ?></option>
                                                        <?php endforeach;
                                                        endif; ?>
                                                    </select>
                                                <?php endif; ?>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label for="location_id" class="mb-1">Emplacement</label>
                                                <select class="form-control" id="location_id" name="location_id" required>
                                                    <option value="">-- Sélectionner --</option>
                                                    <?php if (!empty($locationOptions)): foreach ($locationOptions as $opt): ?>
                                                            <option value="<?= (int)$opt['id'] ?>"><?= htmlspecialchars($opt['location_name']) ?></option>
                                                    <?php endforeach;
                                                    endif; ?>
                                                </select>
                                            </div>
                                            <div class="form-group mb-0">
                                                <label for="status_id" class="mb-1">Statut</label>
                                                <select class="form-control" id="status_id" name="status_id">
                                                    <option value="">-- Sélectionner --</option>
                                                    <?php if (!empty($statusOptions)): foreach ($statusOptions as $opt): ?>
                                                            <?php if ($opt['status_name'] !== 'non disponible' && $opt['status_name'] !== 'disponible'): ?>
                                                                <option value="<?= (int)$opt['id'] ?>"><?= htmlspecialchars($opt['status_name']) ?></option>
                                                            <?php endif; ?>
                                                    <?php endforeach;
                                                    endif; ?>
                                                </select>
                                            </div>

                                        </div>

                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-lg-5 mb-3">
                                    <div class="scanner-box m-0 h-100">


                                        <div class="form-col align-items-end g-2">
                                            <div class="form-group mb-3">
                                                <label for="machine_id" class="mb-1">Machine</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <button type="button" id="btnMachPhoto" class="btn btn-outline-primary" aria-label="Scanner QR machine" title="Scanner QR machine">
                                                            <i class="fas fa-qrcode"></i>
                                                        </button>
                                                    </div>
                                                    <input class="form-control" id="machine_id" placeholder="Scannez ou saisissez manuellement">
                                                    <div class="input-group-append">
                                                        <button type="button" id="addMachine" class="btn btn-outline-success" title="Ajouter à la liste">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <input id="machine_photo" type="file" accept="image/*" capture="environment" style="display:none;">
                                            </div>
                                            <div class="form-group mb-3">
                                                <label class="mb-1">Liste des machines</label>
                                                <div id="machinesList" class="border rounded p-2" style="min-height: 37px; background: #fff;">
                                                    <!-- chips rendered here -->
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-end">
                                                <button id="submitBtn" name="machines_ids" type="submit" class="btn btn-success">Affecter</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </form>

                    </div>
                    <div class="card shadow mb-4">
                       
                        <div class="card-body">
                            <form method="post" action="/platform_gmao/public/index.php?route=ajouterEvaluationInventaire">
                                <div class="row justify-content-center">
                                    <div class="col-12 col-md-10">
                                        <div class="scanner-box m-0 h-100">
                                            <div class="row align-items-end">
                                                <div class="col-12 col-md-10">
                                                    <div class="form-group mb-3">
                                                        <label for="evaluation_maintener_id" class="mb-1">Maintenancier</label>
                                                        <?php if (!$isAdmin && isset($connectedMaintenerId) && $connectedMaintenerId): ?>
                                                            <input type="hidden" name="evaluation_maintener_id" value="<?= htmlspecialchars($connectedMaintenerId) ?>">
                                                            <input type="text" class="form-control" value="<?= htmlspecialchars($connectedMatricule) ?>" readonly>
                                                        <?php else: ?>
                                                            <select class="form-control" id="evaluation_maintener_id" name="evaluation_maintener_id">
                                                                <option value="">-- Sélectionner --</option>
                                                                <?php if (!empty($maintenancier)): foreach ($maintenancier as $opt): ?>
                                                                        <option value="<?= (int)$opt['id'] ?>"><?= htmlspecialchars($opt['first_name']) ?> <?= htmlspecialchars($opt['last_name']) ?></option>
                                                                <?php endforeach;
                                                                endif; ?>
                                                            </select>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-md-2">
                                                    <div class="d-flex justify-content-md-end mb-3 mt-md-0">
                                                        <button id="submitBtnEvaluation" name="evaluationInventaire" type="submit" class="btn btn-success btn-block">
                                                            Résultat inventaire
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
            <?php include(__DIR__ . "/../../views/layout/footer.php"); ?>

        </div>
        <a class="scroll-to-top rounded" href="#page-top">
            <i class="fas fa-angle-up"></i>
        </a>
        <script src="/platform_gmao/public/js/jquery-3.6.4.min.js"></script>
        <script src="/platform_gmao/public/js/jquery.dataTables.min.js"></script>
        <script src="/platform_gmao/public/js/sideBare.js"></script>
        <script src="/platform_gmao/public/js/bootstrap.bundle.min.js"></script>
        <script src="/platform_gmao/public/js/sb-admin-2.min.js"></script>
        <script src="/platform_gmao/public/js/dataTables.bootstrap4.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            $(document).ready(function() {
                function initSelect2AlwaysSearch(sel) {
                    if ($.fn.select2) {
                        $(sel).select2({
                            width: '100%',
                            placeholder: '-- Sélectionner --',
                            allowClear: true,
                            minimumResultsForSearch: 0
                        });
                    }
                }

                initSelect2AlwaysSearch('#maintener_id');
                initSelect2AlwaysSearch('#location_id');
                initSelect2AlwaysSearch('#status_id');
                initSelect2AlwaysSearch('#evaluation_maintener_id');
                // QR scan for machine_id
                const $btnMachPhoto = $('#btnMachPhoto');
                const $machinePhoto = $('#machine_photo');
                const $machineInput = $('#machine_id');

                function compressImage(file, maxW = 1280, maxH = 1280, quality = 0.8) {
                    return new Promise((resolve, reject) => {
                        const img = new Image();
                        const url = URL.createObjectURL(file);
                        img.onload = () => {
                            let width = img.width;
                            let height = img.height;
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
                                resolve(new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), {
                                    type: 'image/jpeg'
                                }));
                            }, 'image/jpeg', quality);
                        };
                        img.onerror = () => reject(new Error('Lecture image échouée'));
                        img.src = url;
                    });
                }

                async function uploadAndDecode(file) {
                    const form = new FormData();
                    form.append('photo', file);
                    form.append('target', 'machine');
                    try {
                        const res = await fetch('/platform_gmao/public/index.php?route=scan/decode', {
                            method: 'POST',
                            body: form
                        });
                        const data = await res.json();
                        if (!data.success) throw new Error(data.error || 'Décodage échoué');
                        return data;
                    } catch (e) {
                        alert('Erreur de scan: ' + e.message);
                        return null;
                    }
                }

                $btnMachPhoto.on('click', function() {
                    $machinePhoto.trigger('click');
                });
                $machinePhoto.on('change', async function(e) {
                    const file = e.target.files && e.target.files[0];
                    if (!file) return;
                    let payload = file;
                    try {
                        payload = await compressImage(file);
                    } catch (e) {}
                    const result = await uploadAndDecode(payload);
                    if (result && result.text) {
                        $machineInput.val(result.text);
                    }
                });

                // Multi-add list behavior
                const $addMachine = $('#addMachine');
                const $machinesList = $('#machinesList');
                const machineSet = new Set();

                function renderChips() {
                    $machinesList.empty();
                    if (machineSet.size === 0) {
                        $machinesList.append('<span class="text-muted">Aucune machine</span>');
                        return;
                    }
                    machineSet.forEach((mid) => {
                        const safe = $('<div/>').text(mid).html();
                        const chip = $('<span class="badge badge-pill badge-primary mr-1 mb-1">' + safe + ' <a href="#" class="text-white ml-1 remove-machine" data-mid="' + safe + '"><i class="fas fa-times"></i></a></span>');
                        $machinesList.append(chip);
                    });
                }

                function addCurrentMachine() {
                    const val = ($machineInput.val() || '').trim();
                    if (!val) return;
                    if (!machineSet.has(val)) {
                        machineSet.add(val);
                        // add hidden for POST
                        const hidden = $('<input type="hidden" name="machines_ids[]">').val(val);
                        $('form[action*="ajouterInventaire"]').append(hidden);
                        renderChips();
                    }
                    $machineInput.val('');
                }

                $addMachine.on('click', addCurrentMachine);
                $machineInput.on('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        addCurrentMachine();
                    }
                });
                $machinesList.on('click', '.remove-machine', function(e) {
                    e.preventDefault();
                    const mid = $(this).attr('data-mid');
                    machineSet.delete(mid);
                    $('input[type="hidden"][name="machines_ids[]"]').filter(function() {
                        return $(this).val() === mid;
                    }).remove();
                    renderChips();
                });

                var table = $('#dataTable').DataTable({
                    language: {
                        search: "Rechercher:",
                        lengthMenu: "Afficher _MENU_ éléments par page",
                        info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments",
                        infoEmpty: "Aucun élément à afficher",
                        infoFiltered: "(filtré de _MAX_ éléments au total)",
                        zeroRecords: "Aucun enregistrement correspondant trouvé",
                        paginate: {
                            first: "Premier",
                            previous: "Précédent",
                            next: "Suivant",
                            last: "Dernier"
                        }
                    },
                    pageLength: 10,
                    order: [
                        [0, 'asc']
                    ] // Trier par matricule par défaut
                });

                // Bouton pour vider les filtres
                $('#clearFilters').on('click', function() {
                    // Réinitialiser tous les selects
                    $('#filterMatricule').val('').trigger('change');
                    $('#filterChaine').val('').trigger('change');
                    $('#filterMachine').val('').trigger('change');

                    // Rediriger vers la page sans filtres
                    window.location.href = '?route=maintenancier_machine';
                });

                // Faire disparaître les messages flash après 4 secondes
                setTimeout(function() {
                    $(".auto-dismiss").fadeOut("slow");
                }, 4000);
            });
        </script>
    </div>
</body>

</html>