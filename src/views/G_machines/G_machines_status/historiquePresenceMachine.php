<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$records = $history['data'] ?? [];
$page = $history['page'] ?? 1;
$perPage = $history['per_page'] ?? 25;
$total = $history['total'] ?? 0;
$totalPages = $history['total_pages'] ?? 1;

$stateLabels = [
    '1' => 'Active',
    '0' => 'Inactive',
];

$stateClasses = [
    '1' => 'badge-success',
    '0' => 'badge-warning',
];

function buildPresenceHistoryUrl(array $extra = []): string
{
    $params = array_merge($_GET, $extra);
    $params['route'] = 'machines/presence/history';
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Historique présence machine</title>
    <link rel="icon" type="image/x-icon" href="/public/images/images.png" />
    <link rel="stylesheet" href="/platform_gmao/public/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="/platform_gmao/public/css/sb-admin-2.min.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/table.css">
    <style>
        .filter-label {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }

        .filter-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        table.table {
            width: 100%;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include(__DIR__ . "/../../../views/layout/sidebar.php") ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include(__DIR__ . "/../../../views/layout/navbar.php"); ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Historique de présence machines</h1>
                        <a href="?route=Gestion_machines/status" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i> Retour
                        </a>
                    </div>

                    <?php if (!empty($_SESSION['flash_message'])): ?>
                        <div id="flash-message" class="alert alert-<?= $_SESSION['flash_message']['type'] === 'success' ? 'success' : 'danger' ?> mb-4">
                            <?= htmlspecialchars($_SESSION['flash_message']['text']) ?>
                        </div>
                        <?php unset($_SESSION['flash_message']); ?>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Filtres</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="" class="mb-0" id="presenceFilters">
                                <input type="hidden" name="route" value="machines/presence/history">
                                <div class="form-row">


                                    <div class="form-group col-md-4">
                                        <label class="filter-label" for="state">Etat</label>
                                        <select name="state" id="state" class="form-control js-auto-submit">
                                            <?php foreach ($stateOptions as $value => $label): ?>
                                                <option value="<?= htmlspecialchars($value) ?>" <?= ($activeFilters['state'] === $value) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label class="filter-label" for="start_date">Date début</label>
                                        <input type="date" name="start_date" id="start_date" class="form-control js-auto-submit" value="<?= htmlspecialchars($activeFilters['start_date'] ?? '') ?>">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label class="filter-label" for="end_date">Date fin</label>
                                        <input type="date" name="end_date" id="end_date" class="form-control js-auto-submit" value="<?= htmlspecialchars($activeFilters['end_date'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="form-row align-items-end">
                                    <div class="form-group col-md-2">
                                        <label class="filter-label" for="per_page">Par page</label>
                                        <select name="per_page" id="per_page" class="form-control js-auto-submit">
                                            <?php foreach ([10, 25, 50, 100] as $size): ?>
                                                <option value="<?= $size ?>" <?= ($perPage == $size) ? 'selected' : '' ?>><?= $size ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Historique</h6>
                            <div class="small text-muted">
                                <?php
                                $start = $total > 0 ? (($page - 1) * $perPage) + 1 : 0;
                                $end = min($start + $perPage - 1, $total);
                                ?>
                                Affichage <?= $start ?> - <?= $end ?> / <?= $total ?> lignes
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-sm mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Machine</th>
                                        <th>Opératrice</th>
                                        <th>Emplacement</th>
                                        <th>Smartbox</th>
                                        <th>Etat</th>
                                        <th>Date &amp; heure</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($records) === 0): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                Aucun enregistrement à afficher pour les filtres sélectionnés.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($records as $row): ?>
                                            <tr>

                                                <td><?= htmlspecialchars($row['machine_id'] ?? '-') ?></td>
                                                <td>
                                                    <?php
                                                    $fullName = trim(($row['operator_first_name'] ?? '') . ' ' . ($row['operator_last_name'] ?? ''));
                                                    $matricule = $row['operator_matricule'] ?? $row['operator'] ?? '';

                                                    if (!empty($fullName)) {
                                                        if ($matricule) {
                                                            echo '<div class="text-muted">' . htmlspecialchars($matricule) . '</div>';
                                                        }
                                                        echo '<strong>' . htmlspecialchars($fullName) . '</strong>';
                                                    } else {
                                                        echo htmlspecialchars($matricule ?: '-');
                                                    }
                                                    ?>
                                                </td>

                                                <td><?= htmlspecialchars($row['prod_line'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($row['smartbox'] ?? '-') ?></td>
                                                <td>
                                                    <?php
                                                    $stateValue = isset($row['p_state']) ? (string)$row['p_state'] : '';
                                                    $label = $stateLabels[$stateValue] ?? ($stateValue !== '' ? $stateValue : 'N/A');
                                                    $class = $stateClasses[$stateValue] ?? 'badge-dark';
                                                    ?>
                                                    <span class="badge <?= $class ?>"><?= htmlspecialchars($label) ?></span>
                                                </td>

                                                <td>
                                                    <?php
                                                    $date = $row['cur_date'] ?? '';
                                                    $time = $row['cur_time'] ?? '';

                                                    if ($date !== '' || $time !== '') {
                                                        echo htmlspecialchars($date);
                                                        if ($time !== '') {
                                                            echo '<br> ' . htmlspecialchars($time);
                                                        }
                                                    } else {
                                                        echo '-';
                                                    }
                                                    ?>
                                                </td>

                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>

                            <?php if ($totalPages > 1):
                                $windowSize = 2;
                                $pagesToDisplay = [];

                                $pagesToDisplay[] = 1;
                                $windowStart = max(2, $page - $windowSize);
                                $windowEnd = min($totalPages - 1, $page + $windowSize);

                                if ($windowStart > 2) {
                                    $pagesToDisplay[] = 'ellipsis';
                                }

                                for ($i = $windowStart; $i <= $windowEnd; $i++) {
                                    $pagesToDisplay[] = $i;
                                }

                                if ($windowEnd < $totalPages - 1) {
                                    $pagesToDisplay[] = 'ellipsis';
                                }

                                if ($totalPages > 1) {
                                    $pagesToDisplay[] = $totalPages;
                                }
                            ?>
                                <nav aria-label="Pagination présence machines" class="mt-3">
                                    <ul class="pagination justify-content-end mt-5 mb-0 flex-wrap">
                                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                            <a class="page-link" href="<?= $page <= 1 ? '#' : htmlspecialchars(buildPresenceHistoryUrl(['page' => $page - 1])) ?>" tabindex="-1">Précédent</a>
                                        </li>
                                        <?php foreach ($pagesToDisplay as $p): ?>
                                            <?php if ($p === 'ellipsis'): ?>
                                                <li class="page-item disabled"><span class="page-link">…</span></li>
                                            <?php else: ?>
                                                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                                    <a class="page-link" href="<?= htmlspecialchars(buildPresenceHistoryUrl(['page' => $p])) ?>"><?= $p ?></a>
                                                </li>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                            <a class="page-link" href="<?= $page >= $totalPages ? '#' : htmlspecialchars(buildPresenceHistoryUrl(['page' => $page + 1])) ?>">Suivant</a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php include(__DIR__ . "/../../../views/layout/footer.php"); ?>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <script src="/platform_gmao/public/js/jquery-3.6.4.min.js"></script>
    <script src="/platform_gmao/public/js/bootstrap.bundle.min.js"></script>
    <script src="/platform_gmao/public/js/sb-admin-2.min.js"></script>
    <script src="/platform_gmao/public/js/sideBare.js"></script>
    <script>
        setTimeout(function() {
            const flash = document.getElementById('flash-message');
            if (flash) {
                flash.style.transition = 'opacity 0.4s';
                flash.style.opacity = '0';
            }
        }, 4000);

        (function() {
            const form = document.getElementById('presenceFilters');
            if (!form) return;
            const autoFields = form.querySelectorAll('.js-auto-submit');
            autoFields.forEach(function(field) {
                field.addEventListener('change', function() {
                    form.submit();
                });
            });
        })();
    </script>
</body>

</html>