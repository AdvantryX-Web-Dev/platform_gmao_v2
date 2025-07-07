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
    <title>Historique des audits</title>
    <link rel="icon" type="image/x-icon" href="/public/images/images.png" />
    <link rel="stylesheet" href="/public/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/sb-admin-2.min.css">
    <link rel="stylesheet" href="/public/css/table.css">
    <link rel="stylesheet" href="/public/css/init_Machine.css">
    <link rel="stylesheet" href="/public/css/datatables.min.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include(__DIR__ . "/../../views/layout/sidebar.php") ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include(__DIR__ . "/../../views/layout/navbar.php"); ?>
                <div class="container-fluid">
                    <button class="btn btn-primary" id="sidebarTo"><i class="fas fa-bars"></i></button>
                    
                    <?php if (!empty($_SESSION['flash_message'])): ?>
                        <div id="flash-message" class="alert alert-<?= $_SESSION['flash_message']['type'] === 'success' ? 'success' : 'danger' ?> mb-4">
                            <?= htmlspecialchars($_SESSION['flash_message']['text']) ?>
                        </div>
                        <?php unset($_SESSION['flash_message']); ?>
                    <?php endif; ?>
                    
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Historique des audits - Mainteneurs</h6>
                            <div>
                                <a href="../../public/index.php?route=maintainers" class="btn btn-secondary"><i class="fas fa-history"></i>  Retour à la liste</a>
                                <!-- <div class="dropdown d-inline-block">
                                    <button class="btn btn-primary dropdown-toggle" type="button" id="filterDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        Filtrer par action
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="filterDropdown">
                                        <a class="dropdown-item" href="../../public/index.php?route=maintainer/audit">Tous</a>
                                        <a class="dropdown-item" href="../../public/index.php?route=maintainer/audit&action=add">Ajouts</a>
                                        <a class="dropdown-item" href="../../public/index.php?route=maintainer/audit&action=update">Modifications</a>
                                        <a class="dropdown-item" href="../../public/index.php?route=maintainer/audit&action=delete">Suppressions</a>
                                    </div>
                                </div> -->
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Date/Heure</th>
                                            <th>Utilisateur</th>
                                            <th>Action</th>
                                            <th>Ancienne valeur</th>
                                            <th>Nouvelle valeur</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (is_array($auditTrails) && count($auditTrails) > 0): ?>
                                            <?php foreach ($auditTrails as $audit): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($audit['created_at']))) ?></td>
                                                    <td>
                                                        <?php if ($audit['first_name'] && $audit['last_name']): ?>
                                                            <?= htmlspecialchars($audit['first_name'] . ' ' . $audit['last_name']) ?>
                                                        <?php else: ?>
                                                            Utilisateur #<?= htmlspecialchars($audit['user_id']) ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        switch ($audit['action']) {
                                                            case 'add':
                                                                echo '<span class="badge badge-success">Ajout</span>';
                                                                break;
                                                            case 'update':
                                                                echo '<span class="badge badge-warning">Modification</span>';
                                                                break;
                                                            case 'delete':
                                                                echo '<span class="badge badge-danger">Suppression</span>';
                                                                break;
                                                            default:
                                                                echo htmlspecialchars($audit['action']);
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($audit['old_value']): ?>
                                                            <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#oldValueModal<?= $audit['id'] ?>">
                                                                Voir détails
                                                            </button>
                                                            <!-- Modal pour afficher les anciennes valeurs -->
                                                            <div class="modal fade" id="oldValueModal<?= $audit['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="oldValueModalLabel<?= $audit['id'] ?>" aria-hidden="true">
                                                                <div class="modal-dialog" role="document">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title" id="oldValueModalLabel<?= $audit['id'] ?>">Anciennes valeurs</h5>
                                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                                <span aria-hidden="true">&times;</span>
                                                                            </button>
                                                                        </div>
                                                                        <div class="modal-body">
                                                                            <pre><?= htmlspecialchars(json_encode(json_decode($audit['old_value']), JSON_PRETTY_PRINT)) ?></pre>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted">Non applicable</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($audit['new_value']): ?>
                                                            <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#newValueModal<?= $audit['id'] ?>">
                                                                Voir détails
                                                            </button>
                                                            <!-- Modal pour afficher les nouvelles valeurs -->
                                                            <div class="modal fade" id="newValueModal<?= $audit['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="newValueModalLabel<?= $audit['id'] ?>" aria-hidden="true">
                                                                <div class="modal-dialog" role="document">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title" id="newValueModalLabel<?= $audit['id'] ?>">Nouvelles valeurs</h5>
                                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                                <span aria-hidden="true">&times;</span>
                                                                            </button>
                                                                        </div>
                                                                        <div class="modal-body">
                                                                            <pre><?= htmlspecialchars(json_encode(json_decode($audit['new_value']), JSON_PRETTY_PRINT)) ?></pre>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted">Non applicable</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5">Aucun enregistrement d'audit trouvé.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include(__DIR__ . "/../../views/layout/footer.php"); ?>
        </div>
        <a class="scroll-to-top rounded" href="#page-top">
            <i class="fas fa-angle-up"></i>
        </a>
        <script src="/public/js/jquery-3.6.4.min.js"></script>
        <script src="/public/js/jquery.dataTables.min.js"></script>
        <script src="/public/js/sideBare.js"></script>
        <script src="/public/js/bootstrap.bundle.min.js"></script>
        <script src="/public/js/sb-admin-2.min.js"></script>
        <script src="/public/js/dataTables.bootstrap4.min.js"></script>
        <script>
            $(document).ready(function() {
                $('#dataTable').DataTable({
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
                    order: [[0, 'desc']] // Trier par date/heure (première colonne) en descendant
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