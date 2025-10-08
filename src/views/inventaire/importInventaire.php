<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Models\Machine_model;

$machines = Machine_model::findAll();

// Vérifie si l'utilisateur est un administrateur
$isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Liste des machines</title>
    <link rel="icon" type="image/x-icon" href="/public/images/images.png" />
    <link rel="stylesheet" href="/platform_gmao/public/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="/platform_gmao/public/css/sb-admin-2.min.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/table.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/init_Machine.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/datatables.min.css">
</head>
<style>
    #dropzone {
        border: 1px dashed #9bb6ff;
        background: #f3f7ff;
        cursor: pointer;
        transition: all .15s ease-in-out;
    }

    #dropzone:hover {
        background: #e9f1ff;
        border-color: #5a7bd3;
        box-shadow: 0 2px 8px rgba(54, 89, 204, .12)
    }

    #dropzone .dz-icon {
        font-size: 26px;
        color: #6c8acf
    }

    #dropzone .dz-sub {
        color: #6c7a96
    }

    #dropzone .dz-file {
        font-weight: 600;
        color: #2d3a66
    }

    /* Overlay de chargement import */
    #import-loading {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.8);
        z-index: 1050;
    }

    #import-loading .box {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
        color: #2d3a66;
    }

    #import-loading .spinner {
        font-size: 48px;
        color: #2d3a66;
        animation: spin 1s linear infinite;
    }

    #import-loading .txt {
        margin-top: 10px;
        font-weight: 600;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
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

                    <?php if (!empty($_SESSION['flash_error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show auto-dismiss" role="alert">
                            <?php echo htmlspecialchars($_SESSION['flash_error']); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php unset($_SESSION['flash_error']); ?>
                    <?php endif; ?>
                    <?php if (!empty($_SESSION['flash_success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show auto-dismiss" role="alert">
                            <?php echo htmlspecialchars($_SESSION['flash_success']); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php unset($_SESSION['flash_success']); ?>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Import Inventaire Machines :</h6>

                        </div>
                        <div class="card-body">
                            <?php if ($isAdmin): ?>
                                <form id="inv-upload-form" action="../../platform_gmao/public/index.php?route=importInventaire" method="post" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label for="file" class="mb-2">Fichier Excel</label>

                                        <div id="dropzone" class="rounded d-flex align-items-center justify-content-center text-center p-4">
                                            <div id="dz-content">
                                                <div class="mb-2"><i class="fas fa-cloud-upload-alt dz-icon"></i></div>
                                                <div>Glissez-déposez ou <span class="text-primary" style="text-decoration: underline;">cliquez pour parcourir</span></div>
                                                <div class="small dz-sub mt-1">CSV, XLSX, XLS • Max 5MB</div>
                                            </div>
                                        </div>
                                        <input type="file" name="file" id="file" accept=".csv,.xlsx,.xls" style="display:none" required>
                                        <div id="file-info" class="mt-2"></div>
                                    </div>
                                    <button id="btn-import" type="submit" class="btn btn-success">Importer</button>
                                </form>

                                <!-- Overlay de chargement -->
                                <div id="import-loading">
                                    <div class="box">
                                        <div><i class="fas fa-clock spinner"></i></div>
                                        <div class="txt">En cours d'importation...</div>
                                    </div>
                                </div>

                            <?php endif; ?>

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
        <script></script>
        <script>
            (function() {
                // Auto-dismiss des alertes après 5 secondes
                // setTimeout(function() {
                //     var alerts = document.querySelectorAll('.alert.auto-dismiss');
                //     alerts.forEach(function(a){
                //         if (typeof $ === 'function' && typeof $(a).alert === 'function') {
                //             $(a).alert('close');
                //         } else {
                //             a.classList.remove('show');
                //             a.addEventListener('transitionend', function(){ a.parentNode && a.parentNode.removeChild(a); }, { once: true });
                //         }
                //     });
                // }, 5000);

                var dz = document.getElementById('dropzone'),
                    fi = document.getElementById('file'),
                    info = document.getElementById('file-info'),
                    dzc = document.getElementById('dz-content'),
                    form = document.getElementById('inv-upload-form');
                var btn = document.getElementById('btn-import');
                var overlay = document.getElementById('import-loading');
                var exts = ['csv', 'xlsx', 'xls'],
                    max = 5 * 1024 * 1024;
                var ph = function() {
                    dzc.innerHTML = '<div class="mb-2"><i class="fas fa-cloud-upload-alt dz-icon"></i></div><div>Glissez-déposez ou <span class="text-primary" style="text-decoration: underline;">cliquez pour parcourir</span></div><div class="small dz-sub mt-1">CSV, XLSX, XLS • Max 5MB</div>';
                };
                var ok = function(f) {
                    var ext = (f.name.split('.').pop() || '').toLowerCase(),
                        icon = (ext === 'csv') ? 'fa-file-csv' : 'fa-file-excel';
                    dzc.innerHTML = '<div class="mb-2"><i class="fas ' + icon + ' dz-icon" style="color:#2ea44f"></i></div><div class="dz-file">' + f.name.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</div><div class="small dz-sub mt-1">Fichier prêt à être importé</div>';
                };

                function setInfo(m, s) {
                    info.className = s ? 'text-success' : 'text-danger';
                    info.textContent = m;
                }

                function valid(f) {
                    if (!f) return false;
                    var ext = (f.name.split('.').pop() || '').toLowerCase();
                    if (exts.indexOf(ext) === -1) {
                        setInfo('Type de fichier non supporté: ' + ext + '. Utilisez CSV, XLSX ou XLS.', false);
                        fi.value = '';
                        ph();
                        return false;
                    }
                    if (f.size > max) {
                        setInfo('Fichier trop volumineux (max 5MB).', false);
                        fi.value = '';
                        ph();
                        return false;
                    }
                    setInfo('Fichier sélectionné: ' + f.name, true);
                    ok(f);
                    return true;
                }
                dz.addEventListener('click', function() {
                    fi.click();
                });
                fi.addEventListener('change', function(e) {
                    valid(e.target.files[0]);
                });
                ['dragenter', 'dragover'].forEach(function(v) {
                    dz.addEventListener(v, function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        dz.classList.add('is-dragover');
                    });
                });
                ['dragleave', 'drop'].forEach(function(v) {
                    dz.addEventListener(v, function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        dz.classList.remove('is-dragover');
                    });
                });
                dz.addEventListener('drop', function(e) {
                    var dt = e.dataTransfer;
                    if (!dt || !dt.files || !dt.files[0]) return;
                    var f = dt.files[0];
                    if (valid(f)) {
                        var d = new DataTransfer();
                        d.items.add(f);
                        fi.files = d.files;
                    }
                });
                form.addEventListener('submit', function(e) {
                    if (!fi.files || !fi.files[0]) {
                        e.preventDefault();
                        setInfo('Veuillez choisir un fichier.', false);
                        return;
                    }
                    if (!valid(fi.files[0])) {
                        e.preventDefault();
                        return;
                    }
                    // Afficher l'overlay et désactiver le bouton pendant l'envoi
                    if (btn) {
                        btn.disabled = true;
                        btn.textContent = 'Importation...';
                    }
                    if (overlay) {
                        overlay.style.display = 'block';
                    }
                });
                ph();
            })();
        </script>
    </div>
</body>

</html>