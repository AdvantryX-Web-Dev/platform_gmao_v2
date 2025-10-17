<!-- <?php include __DIR__ . '/../layout/header.php'; ?>
<?php include __DIR__ . '/../layout/sidebar.php'; ?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Inventaire Machines</h1>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['flash_error']);
                                        unset($_SESSION['flash_error']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['flash_success']);
                                            unset($_SESSION['flash_success']); ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Machine</th>
                        <th>Mainteneur</th>
                        <th>Emplacement</th>
                        <th>Statut</th>
                        <th>Créé le</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($inventaires)): ?>
                        <?php foreach ($inventaires as $inv): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($inv['id']); ?></td>
                                <td><?php echo htmlspecialchars($inv['machine_reference'] ?? $inv['machine_id']); ?></td>
                                <td><?php echo htmlspecialchars($inv['maintainer_name'] ?? $inv['maintener_id']); ?></td>
                                <td><?php echo htmlspecialchars($inv['location_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($inv['status_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($inv['created_at'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">Aucun enregistrement</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <a href="../../platform_gmao/public/index.php?route=importInventaire" class="btn btn-primary">Importer</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout/footer.php'; ?>

 -->