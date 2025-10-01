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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>


<body id="page-top">
    <div id="wrapper">
        <?php include(__DIR__ . "/../../views/layout/sidebar.php") ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include(__DIR__ . "/../../views/layout/navbar.php"); ?>


                <div class="container-fluid">

                    <?php
                    // Préparer les options uniques pour les filtres si des données existent
                    $chainOptions = [];
                    $machineOptions = [];
                    if (!empty($maintenances)) {
                        foreach ($maintenances as $row) {
                            if (!empty($row['chains_list'])) {
                                $chains = explode(', ', $row['chains_list']);
                                foreach ($chains as $c) {
                                    $c = trim($c);
                                    if ($c !== '') {
                                        $chainOptions[$c] = true;
                                    }
                                }
                            }
                            if (!empty($row['machines_with_status'])) {
                                $machinesTmp = explode('||', $row['machines_with_status']);
                                foreach ($machinesTmp as $machineData) {
                                    $parts = explode('|', $machineData);
                                    $machineLabel = trim($parts[0]);
                                    if ($machineLabel !== '') {
                                        $machineOptions[$machineLabel] = true;
                                    }
                                }
                            }
                        }
                        ksort($chainOptions);
                        ksort($machineOptions);
                    }
                    ?>

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
                            <h6 class="m-0 font-weight-bold text-primary">Maintenancier - Machines :</h6>
                            <div class="d-flex flex-wrap align-items-center" style="gap: 10px;">
                                <div>
                                    <label for="filterMatricule" class="mb-0 small text-muted">Matricule</label>
                                    <input type="text" id="filterMatricule" class="form-control form-control-sm" placeholder="Rechercher matricule">
                                </div>
                                <div style="min-width:220px;">
                                    <label for="filterChaine" class="mb-0 small text-muted">Chaînes</label>
                                    <select id="filterChaine" class="form-control form-control-sm" multiple>
                                        <?php foreach ($chainOptions as $opt => $_): ?>
                                            <option value="<?php echo htmlspecialchars($opt); ?>"><?php echo htmlspecialchars($opt); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div style="min-width:220px;">
                                    <label for="filterMachine" class="mb-0 small text-muted">Machine</label>
                                    <select id="filterMachine" class="form-control form-control-sm">
                                        <option value="">Toutes</option>
                                        <?php foreach ($machineOptions as $opt => $_): ?>
                                            <option value="<?php echo htmlspecialchars($opt); ?>"><?php echo htmlspecialchars($opt); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($maintenances)): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Matricule</th>
                                                <th>Chaines </th>
                                                <th>Machines </th>

                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($maintenances as $row): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['maintener_name']); ?></td>
                                                    <td>
                                                        <?php
                                                        $chains = !empty($row['chains_list']) ? explode(', ', $row['chains_list']) : [];
                                                        foreach ($chains as $chain): ?>
                                                            <span class="badge badge-info mr-1 mb-1"><?php echo htmlspecialchars(trim($chain)); ?></span>
                                                        <?php endforeach; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $machines = !empty($row['machines_with_status']) ? explode('||', $row['machines_with_status']) : [];
                                                        foreach ($machines as $machineData):
                                                            $parts = explode('|', $machineData);
                                                            $machine = $parts[0];
                                                            $status = isset($parts[1]) ? $parts[1] : 'unknown';
                                                            $badgeClass = ($status === 'active') ? 'badge' : 'badge-';
                                                        ?>
                                                            <span class="badge <?php echo $badgeClass; ?> mr-1 mb-1"><?php echo htmlspecialchars(trim($machine)); ?></span>
                                                        <?php endforeach; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-muted">Aucune liaison maintenancier/machine trouvée.</div>
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
        <script src="/platform_gmao/public/js/dataTables.bootstrap4.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            $(document).ready(function() {
                // Init Select2 for better search/UX
                $('#filterChaine').select2({
                    width: 'resolve',
                    placeholder: 'Sélectionner des chaînes',
                    allowClear: true
                });
                $('#filterMachine').select2({
                    width: 'resolve',
                    placeholder: 'Rechercher une machine',
                    allowClear: true
                });

                // Custom filter en fonction des contrôles
                $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                    var valMatricule = ($('#filterMatricule').val() || '').toString().toLowerCase();
                    var selectedChaines = $('#filterChaine').val() || [];
                    var valMachine = ($('#filterMachine').val() || '').toString().toLowerCase();

                    // data[0] = Matricule, data[1] = Chaines (HTML), data[2] = Machines (HTML)
                    var matriculeText = (data[0] || '').toString().toLowerCase();
                    var chainesHtml = (data[1] || '').toString();
                    var machinesHtml = (data[2] || '').toString();

                    var chainesText = $('<div>').html(chainesHtml).text().toLowerCase();
                    var machinesText = $('<div>').html(machinesHtml).text().toLowerCase();

                    if (valMatricule && matriculeText.indexOf(valMatricule) === -1) {
                        return false;
                    }
                    if (selectedChaines.length > 0) {
                        var allMatch = true;
                        for (var i = 0; i < selectedChaines.length; i++) {
                            var c = (selectedChaines[i] || '').toString().toLowerCase();
                            if (c && chainesText.indexOf(c) === -1) {
                                allMatch = false;
                                break;
                            }
                        }
                        if (!allMatch) {
                            return false;
                        }
                    }
                    if (valMachine && machinesText.indexOf(valMachine) === -1) {
                        return false;
                    }
                    return true;
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
                    pageLength: 10
                });

                // Rafraîchir le tableau sur changement des filtres
                $('#filterMatricule').on('input', function() {
                    table.draw();
                });
                $('#filterChaine').on('change', function() {
                    table.draw();
                });
                $('#filterMachine').on('change', function() {
                    table.draw();
                });

                // Faire disparaître les messages flash après 4 secondes
                setTimeout(function() {
                    $("#flash-message").fadeOut("slow");
                }, 4000);
            });
        </script>
    </div>
</body>

</html>