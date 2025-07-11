<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="https://advantryx.com/">
        <div class="sidebar-brand-text mx-3">
            <img src="/public/images/LogoAdvantryX.png" alt="" width="150px" height="50px">
        </div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item active">
        <a class="nav-link" href="../../public/index.php?route=dashboard">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span></a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- 1. Données de base -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseBaseData" aria-expanded="true" aria-controls="collapseBaseData">
            <i class="fas fa-fw fa-database"></i>
            <span>Données de base</span>
        </a>
        <div id="collapseBaseData" class="collapse" aria-labelledby="headingBaseData" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="../../public/index.php?route=machines">Liste des machines</a>
                <a class="collapse-item" href="../../public/index.php?route=maintainers">Liste des maintenanciers</a>
                <a class="collapse-item" href="../../public/index.php?route=intervention_type/list">Liste des interventions</a>
                <a class="collapse-item" href="../../public/index.php?route=machines_status/list">Liste des états de machine</a>
                <a class="collapse-item" href="../../public/index.php?route=categories">Liste des motifs de mvt</a>
                <a class="collapse-item" href="../../public/index.php?route=equipement/list">Liste des équipements</a>
            </div>
        </div>
    </li>

    <!-- 2. Gestion de machine -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseGestionMachine" aria-expanded="true" aria-controls="collapseGestionMachine">
            <i class="fas fa-fw fa-cogs"></i>
            <span>Gestion de machine</span>
        </a>
        <div id="collapseGestionMachine" class="collapse" aria-labelledby="headingGestionMachine" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
            <a class="collapse-item" href="../../public/index.php?route=Gestion_machines/status">Etat des machines</a>
            <a class="collapse-item" href="../../public/index.php?route=machinesbox">Machine-Box</a>
                <a class="collapse-item collapsed" href="#" data-toggle="collapse" data-target="#collapseMouvMachine" aria-expanded="false" aria-controls="collapseMouvMachine">Mouvement machine </a>
                <div id="collapseMouvMachine" class="collapse ml-3" aria-labelledby="headingMouvMachine" data-parent="#collapseGestionMachine">
                    <a class="collapse-item" href="../../public/index.php?route=mouvement_machines/chaine_parc">Entrer en Parc</a>
                    <a class="collapse-item" href="../../public/index.php?route=mouvement_machines/parc_chaine">Sortie de Parc</a>
                    <a class="collapse-item" href="../../public/index.php?route=mouvement_machines/inter_chaine">Inter Chaine</a>


                </div>
            </div>
        </div>
    </li>

    <!-- 3. Gestion d'équipement -->
    <li class="nav-item">
        <a class="nav-link" href="../Vue/GestionEquipement.php">
            <i class="fas fa-fw fa-tools"></i>
            <span>Gestion d'équipement</span>
        </a>
    </li>

    <!-- 4. Maintenance intervention -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseMaintenance" aria-expanded="true" aria-controls="collapseMaintenance">
            <i class="fas fa-fw fa-wrench"></i>
            <span>Maintenance intervention</span>
        </a>
        <div id="collapseMaintenance" class="collapse" aria-labelledby="headingMaintenance" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="../../public/index.php?route=intervention_preventive">Préventive</a>
                <a class="collapse-item" href="../../public/index.php?route=intervention_curative">Curative</a>
            </div>
        </div>
    </li>

    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>
</ul>