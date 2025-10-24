<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Models\Machine_model;

$machines = Machine_model::findAllMachine();

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
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <?php if ($isAdmin): ?>
                                    Maintenance - Filtres administrateur
                                <?php else: ?>
                                    Mes machines assignées (<?= htmlspecialchars($_SESSION['user']['matricule'] ?? 'non défini') ?>)
                                <?php endif; ?>
                            </h6>
                        </div>

                        <?php if ($isAdmin): ?>
                            <div class="card-body border-bottom pb-2 mb-3">
                                <form method="GET" action="" id="filterForm">
                                    <input type="hidden" name="route" value="maintenancier_machine">


                                    <!-- Première ligne : les 3 champs de filtre -->
                                    <div class="row align-items-end justify-content-end">
                                        <div class="col-2 pr-3">
                                            <label for="filterMatricule" class="mb-1 small text-muted">Matricule</label>
                                            <select name="matricule" id="filterMatricule" class="form-control form-control-sm">
                                                <option value="">Tous les matricules</option>
                                                <?php foreach ($matriculeOptions as $matricule): ?>
                                                    <option value="<?= htmlspecialchars($matricule) ?>"
                                                        <?= (isset($_GET['matricule']) && $_GET['matricule'] === $matricule) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($matricule) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-2 pr-3">
                                            <label for="filterChaine" class="mb-1 small text-muted">Chaîne</label>
                                            <select name="chaine" id="filterChaine" class="form-control form-control-sm">
                                                <option value="">Toutes les chaînes</option>
                                                <?php foreach ($chainOptions as $chaine): ?>
                                                    <option value="<?= htmlspecialchars($chaine) ?>"
                                                        <?= (isset($_GET['chaine']) && $_GET['chaine'] === $chaine) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($chaine) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-2 pr-3">
                                            <label for="filterMachine" class="mb-1 small text-muted">Machine</label>
                                            <select name="machine" id="filterMachine" class="form-control form-control-sm">
                                                <option value="">Toutes les machines</option>
                                                <?php foreach ($machineOptions as $machine): ?>
                                                    <option value="<?= htmlspecialchars($machine) ?>"
                                                        <?= (isset($_GET['machine']) && $_GET['machine'] === $machine) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($machine) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-2">
                                            <button type="submit" class="btn btn-primary btn-sm mr-2">
                                                <i class="fas fa-search"></i> Filtrer
                                            </button>

                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>

                        <div class="card-body">
                            <?php if (!empty($maintenances)): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Matricule</th>
                                                <th>Maintenancier</th>
                                                <th>Chaîne</th>
                                                <th>Machine</th>
                                                <th>Statut</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($maintenances as $row): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($row['maintener_name']); ?></td>
                                                    <td><?= htmlspecialchars(trim($row['last_name'] . ' ' . $row['first_name'])); ?></td>
                                                    <td>
                                                        <span class="badge badge-info">
                                                            <?= htmlspecialchars($row['location_name'] ?? 'non défini'); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $machineId = $row['machine_id'] ?? '';
                                                        $machineRef = $row['machine_reference'] ?? '';
                                                        $machineDisplay = '';

                                                        if (!empty($machineId) && !empty($machineRef)) {
                                                            $machineDisplay = htmlspecialchars($machineId . ' - ' . $machineRef);
                                                        } elseif (!empty($machineId)) {
                                                            $machineDisplay = htmlspecialchars($machineId);
                                                        } elseif (!empty($machineRef)) {
                                                            $machineDisplay = htmlspecialchars($machineRef);
                                                        } else {
                                                            $machineDisplay = 'non défini';
                                                        }
                                                        echo $machineDisplay;
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $status = $row['machine_status'] ?? 'non défini';
                                                        $badgeClass = ($status === 'active') ? 'badge-success' : 'badge-warning';
                                                        ?>
                                                        <span class="badge <?= $badgeClass; ?>">
                                                            <?= htmlspecialchars($status); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-muted">
                                    <?php if ($isAdmin): ?>
                                        Aucune liaison maintenancier/machine trouvée avec les filtres appliqués.
                                    <?php else: ?>
                                        Aucune machine assignée à votre matricule (<?= htmlspecialchars($_SESSION['user']['matricule'] ?? 'N/A') ?>).
                                    <?php endif; ?>
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
        <script src="/platform_gmao/public/js/dataTables.bootstrap4.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            $(document).ready(function() {
                // Init Select2 for better search/UX
                $('#filterMatricule, #filterChaine, #filterMachine').select2({
                    width: 'resolve',
                    placeholder: 'Sélectionner...',
                    allowClear: true
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