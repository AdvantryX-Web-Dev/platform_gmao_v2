<!--$_SERVER['PHP_SELF'] est une variable superglobale en PHP qui renvoie le nom du fichier du script en cours d'exécution. Cette variable contient le chemin relatif du script par rapport à la racine du serveur web.

basename() est une fonction PHP qui renvoie le composant de nom de fichier d'un chemin donné. Elle extrait le nom du fichier à partir d'un chemin complet.

Ainsi, $current_page = basename($_SERVER['PHP_SELF']); est utilisé pour obtenir le nom du fichier en cours d'exécution, c'est-à-dire le script PHP actuel. Cela est souvent utilisé pour déterminer la page active dans une application web.

Par exemple, si votre URL est "http://example.com/planning.php", alors $_SERVER['PHP_SELF'] renverra "/planning.php", et basename($_SERVER['PHP_SELF']) renverra "planning.php". Cela vous permet de travailler avec le nom du fichier de la page en cours dans votre script PHP.-->

<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">


    <!-- Sidebar Toggle (Topbar) -->

    <?php

    if (isset($_SESSION['email'])) {
        $role = $_SESSION['qualification'];
        if (strtolower($role) == 'chefmaintenancier') {
            // Vérifier si la page actuelle est l'interface de planification (à adapter selon votre structure d'URL)
            $page_courant = basename($_SERVER['PHP_SELF']);
            $HomeChefMain = ($page_courant == 'HomeChefmaintenancier.php'); // Assurez-vous d'ajuster le nom de votre page de planning
    
            if ($HomeChefMain) {
                echo '
                <a class="btn btn-outline-primary" href="planningChefmaintenancier.php">
                    <i class="fas fa-calendar-alt"></i> Intervention préventive
                </a>';
            } else {
                echo '<a href="HomeChefmaintenancier.php" style="font-size: 20px;">
                <i class="fas fa-home" ></i>
     </a>';
            }
        } elseif (strtolower($role) == 'maintenancier') {
            $page_courant = basename($_SERVER['PHP_SELF']);
            $HomeMainte_page = ($page_courant == 'HomeMainte.php'); // Assurez-vous d'ajuster le nom de votre page de planning
    
            if ($HomeMainte_page) {
                echo ' <a class="btn btn-outline-danger" data-toggle="modal" data-target="#interventionModal">
                <i class="fas fa-wrench"></i> <span class="btnInterv">Ajouter une intervention</span> </a>';
            } else {
                echo '<a  href="HomeMainte.php" style="font-size: 20px;"> <i class="fas fa-home" ></i> </a>';
            }
        } elseif (strtolower($role) == 'magasinier') {
            $page_courant = basename($_SERVER['PHP_SELF']);
            $magasin_page = ($page_courant == 'StockArticles.php');
            if ($magasin_page) {
                echo ' <a class="btn btn-outline-primary" data-toggle="modal" data-target="#ESModal">
                <i class="fas fa-exchange-alt"></i> E/S
            </a>';
            } else {
                echo '<a  href="StockArticles.php" style="font-size: 20px;"> <i class="fas fa-home" ></i> </a>';
            }
        } else if (strtolower($role) == 'responsableparcmachine') {
            $page_courant = basename($_SERVER['PHP_SELF']);
            $ParcMachine_page = ($page_courant == 'ParcMachine.php');
            if ($ParcMachine_page) {
                echo ' <a class="btn btn-outline-primary btnM" data-toggle="modal" data-target="#ESMachineModal">
                <i class="fas fa-exchange-alt"></i> <span class="btnMv">Mouvement Machine</span>
            </a>';
            } else {
                echo '<a  href="ParcMachine.php" style="font-size: 20px;"> <i class="fas fa-home" ></i> </a>';
            }
        }
        $prenom = $_SESSION['last_name'];
        $nom = $_SESSION['first_name'];


        ?>
        <!-- Topbar Navbar -->
        <ul class="navbar-nav ml-auto">
            <!-- Nav Item - Alerts -->
            <li class="nav-item dropdown no-arrow mx-1">
                <a class="nav-link dropdown-toggle" id="alertsDropdown" role="button" data-toggle="dropdown"
                    aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-bell fa-fw"></i>
                    <!-- Counter - Alerts -->
                    <?php

                    include("../Controleur/DemMainController.php");
                    include("../Controleur/Dem_ArController.php");
                    include("../Controleur/Comman_InterController.php");
                    include("../Controleur/inte_Preve_ProgController.php");
                    include("../Controleur/ArticleController.php");
                    $articles = afficherArticles();
                    $interventionsPr = intersProg();
                    $notificationsJour = getDemByDate();
                    $nombreDeNotificationsNonLues = 0;
                    $Notifications = getNotifications();
                    $NotificationMai = getNotifByMain($_SESSION['matricule']);
                    $NotificationM = getNotifsByMain($_SESSION['matricule']);
                    $DemsArti = findDemsArByDate();
                    $CommInterns = findByDate();
                    if (strtolower($role) == 'chefmaintenancier' || strtolower($role) == 'maintenancier') {

                        if (strtolower($role) == 'chefmaintenancier') {
                            foreach ($Notifications as $notification) {
                                // echo $notification['monitor'];
                                $nombreDeNotificationsNonLues++;
                            }
                        } else {
                            foreach ($NotificationMai as $notification) {
                                $nombreDeNotificationsNonLues++;
                            }
                        }


                        foreach ($interventionsPr as $intervention) {
                            $datePrevue = new DateTime($intervention['datePrevue']);
                            $datePrevue->setTimezone(new DateTimeZone('Africa/Tunis'));
                            $dateActuelle = new DateTime();
                            $dateActuelle->setTimezone(new DateTimeZone('Africa/Tunis'));
                            $diff = $datePrevue->diff($dateActuelle);
                            $diffEnJours = $diff->format("%a");
                            $diffEnHeures = $diff->format("%h");
                            $datePrevueFormatted = $datePrevue->format('Y-m-d');
                            $dateActuelleFormatted = $dateActuelle->format('Y-m-d');
                            if ($diffEnJours >= 0 && $diffEnJours <= 3 && $dateActuelle <= $datePrevue && $intervention['statut'] !== 'validée') {
                                $nombreDeNotificationsNonLues++;
                            } else if ($datePrevueFormatted < $dateActuelleFormatted && $intervention['statut'] !== 'validée') {
                                $nombreDeNotificationsNonLues++;
                            }
                        }
                    } else if (strtolower($role) == 'magasinier') {
                        foreach ($DemsArti as $DemA) {
                            if ($DemA['statut'] == 1) {
                                $nombreDeNotificationsNonLues++;
                            }
                        }
                        // foreach ($articles as $article) {
                        //     if ($article['qte_stock'] <= $article['stock_Min']) {
                        //         $nombreDeNotificationsNonLues++;
                        //     }
                        // }
                    } else if (strtolower($role) == 'responsableservachat') {
                        foreach ($CommInterns as $commI) {
                            if ($commI['statut'] == 1) {
                                $nombreDeNotificationsNonLues++;
                            }
                        }
                    }
                    ?>
                    <span
                        class="badge badge-<?php echo ($nombreDeNotificationsNonLues > 0) ? 'danger' : ''; ?> badge-counter">
                        <?php echo ($nombreDeNotificationsNonLues > 0) ? $nombreDeNotificationsNonLues : ''; ?>
                    </span>
                </a>
                <!-- Dropdown - Alerts -->
                <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
                    aria-labelledby="alertsDropdown">
                    <h6 class="dropdown-header">
                        Alerts Center
                    </h6>
                    <a class="dropdown-item d-flex align-items-center">
                        <div class="mr-3">

                        </div>
                        <div>
                            <?php
                            if (strtolower($role) == 'chefmaintenancier' || strtolower($role) == 'maintenancier') {
                                ?>

                                <?php if (strtolower($role) == 'chefmaintenancier') {
                                    foreach ($notificationsJour as $notificationJ) {
                                        $idInterv = findByidreqInter($notificationJ['req_interv_id']);

                                        if (isset($notificationJ['monitor'], $notificationJ['group'], $notificationJ['maintainer'], $notificationJ['created_at'])) {

                                            $textColorClass = $idInterv > 0 ? 'text-black' : 'text-danger';
                                            echo '<div class="' . $textColorClass . '">
                                              <div class="d-flex justify-content-between align-items-center mb-2">
                                                  <span class="mr-2"><i class="fas fa-tools"></i></span>'; // Ajout de la classe "mr-2" pour la marge à droite
                                            echo 'Monitrice: ' . $notificationJ['monitor'] . ' | Chaine ' . $notificationJ['group'] . '|' . $notificationJ['maintainer'] . '|' . $notificationJ['created_at'];
                                            echo '</div></div>';
                                        }
                                    }
                                } else {
                                    $dateCourant = date("Y-m-d");
                                    foreach ($NotificationM as $notification) {
                                        $idInterv = findByidreqInter($notification['req_interv_id']);
                                        if (isset($notification['monitor'], $notification['group'], $notification['created_at']) && $dateCourant == Date("Y-m-d", strtotime($notification['created_at']))) {

                                            $textColorClass = $idInterv > 0 ? 'text-black' : 'text-danger';
                                            echo ' <div class="' . $textColorClass . '">
                                                 <div class="d-flex justify-content-between align-items-center mb-2">';
                                            echo '<span class="mr-2"><i class="fas fa-tools"></i></span>'; // Ajout de la classe "mr-2" pour la marge à droite
                                            echo 'Monitrice: ' . $notification['monitor'] . ' |  Chaine ' . $notification['group'] . '|' . $notification['created_at'];
                                            echo '       </div>
                                        </div>';
                                        }
                                    }
                                }
                                ?>

                                <!-- Divider-->
                                <?php echo '<div class="dropdown-divider"></div>';

                                foreach ($interventionsPr as $intervention) {
                                    // $datePrevue = new DateTime($intervention['datePrevue'], new DateTimeZone('Africa/Tunis'));
                                    // $dateActuelle = new DateTime('now', new DateTimeZone('Africa/Tunis'));
                                    $datePrevue = new DateTime($intervention['datePrevue']);
                                    $datePrevue->setTimezone(new DateTimeZone('Africa/Tunis'));
                                    $dateActuelle = new DateTime();
                                    $dateActuelle->setTimezone(new DateTimeZone('Africa/Tunis'));
                                    $diff = $datePrevue->diff($dateActuelle);
                                    $diffEnJours = $diff->format("%a");
                                    $diffEnHeures = $diff->format("%h");
                                    $datePrevueFormatted = $datePrevue->format('Y-m-d');
                                    $dateActuelleFormatted = $dateActuelle->format('Y-m-d');
                                    if ($diffEnJours >= 0 && $diffEnJours <= 3 && $dateActuelle <= $datePrevue && $intervention['statut'] !== 'validée') {
                                        $textColorClass = 'text-warning';
                                        echo '<div class="' . $textColorClass . '">';
                                        echo '<div class="d-flex justify-content-between align-items-center mb-2">';
                                        echo '<span><i class="fas fa-calendar-alt mr-2"></i></span>';
                                        echo '<p class="mb-1">Il reste ' . ($diffEnJours == 0 ? $diffEnHeures . ' ' . ($diffEnHeures > 1 ? 'heures' : 'heure') : $diffEnJours . ' ' . ($diffEnJours > 1 ? 'jours' : 'jour')) . ' avant l\'intervention de ' . $intervention['designation'] . '.</p>';
                                        echo '</div>';
                                        echo '</div>';
                                        echo '<div class="dropdown-divider"></div>';
                                    } else if ($datePrevueFormatted < $dateActuelleFormatted && $intervention['statut'] !== 'validée') {
                                        $textColorClass = 'text-danger';
                                        echo '<div class="' . $textColorClass . '">';
                                        echo '<div class="d-flex justify-content-between align-items-center mb-2">';
                                        echo '<span><i class="fas fa-calendar-alt mr-2"></i></span>';
                                        echo '<p class="mb-1">Retard détecté : ' . ($diffEnJours == 0 ? $diffEnHeures . ' ' . ($diffEnHeures > 1 ? 'heures' : 'heure') : $diffEnJours . ' ' . ($diffEnJours > 1 ? 'jours' : 'jour')) . ' de retard pour l\'intervention de ' . $intervention['designation'] . '.</p>';
                                        echo '</div>';
                                        echo '</div>';
                                        echo '<div class="dropdown-divider"></div>';
                                    }
                                }

                                ?>
                            </div>
                        </a>

                        <!-- Ajoutez ici d'autres éléments d'alerte au besoin -->
                        <a class="dropdown-item text-center small text-gray-500"
                            href="<?php echo (strtolower($role) == 'chefmaintenancier') ? 'Tousnotification.php' : 'TousnotificationMaint.php?maintenancier=' . $_SESSION['matricule']; ?>"
                            style="color: dimgray;">Afficher toutes les notifications</a>

                        <?php
                            } else if (strtolower($role) == 'responsableservachat') {
                                foreach ($CommInterns as $commI) {
                                    $textColorClass = ($commI['statut'] == 1) ? 'text-danger' : 'text-black'; ?>
                                <div class="  <?php echo $textColorClass; ?>">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <?php
                                        echo '<span class="mr-2"><i class="fas fa-clipboard-list"></i></span>';
                                        echo '<p class="mb-1">Le magasinier de matricule ' . $commI['matriculeMag'] . ' a commandé l\'article : ' . $commI['referenceA'] . ' - ' . $commI['designation'] . '</p>';
                                        echo '  </div>
                                    </div>';
                                        echo '<div class="dropdown-divider"></div>';
                                } ?>
                                </div>
                                </a>


                        <?php } else if (strtolower($role) == 'magasinier') {
                                foreach ($DemsArti as $DemA) {
                                    $textColorClass = ($DemA['statut'] == 1) ? 'text-danger' : 'text-black'; ?>
                                        <div class="  <?php echo $textColorClass; ?>">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                            <?php
                                            echo '<span class="mr-2"><i class="fas fa-shopping-basket"></i></span>';
                                            echo 'Le maintenancier ' . $DemA['first_name'] . '' . $DemA['last_name'] . ' (Matricule : ' . $DemA['matriculeMa'] . ') a effectué une demande d\'article avec la référence ' . $DemA['referenceAr'] . ' pour une quantité de ' . $DemA['qteDem'] . '.';
                                            echo '  </div>
                                    </div>';
                                            echo '<div class="dropdown-divider"></div>';
                                }
                                foreach ($articles as $article) {
                                    if ($article['qte_stock'] <= $article['stock_Min']) {
                                        $textColorClass = 'text-danger';

                                        echo '<div class="' . $textColorClass . '">';
                                        echo '<div class="d-flex justify-content-between align-items-center mb-2">';
                                        echo '<span class="mr-2"><i class="fas fa-exclamation-triangle"></i></span>';
                                        echo "La quantité en stock pour l'article '" . $article['designation'] . "' est inférieure ou égale au seuil minimal de stock. Veuillez commander cet article.";

                                        echo '</div>';
                                        echo '</div>';
                                        echo '<div class="dropdown-divider"></div>';
                                    }
                                }
                                ?>
                                        </div>
                                        </a>
                                        <a class="dropdown-item text-center small text-gray-500" href="DemandesArticles.php"
                                            style="  color: dimgray;">Afficher toutes les notifications</a>


                            <?php }
                            ?>
                        </div>
            </li>

            </li>
            <div class="topbar-divider d-none d-sm-block"></div>

            <li class="nav-item dropdown no-arrow">
                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown"
                    aria-haspopup="true" aria-expanded="false">
                    <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo '' . $nom . ' ' . $prenom ?></span>
                    <img class="img-profile rounded-circle" src="../images/avatar1.png">
                </a>
                <!-- Dropdown - User Information -->
                <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                    <a class="dropdown-item" href="#">
                        <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                        Profile
                    </a>

                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="../Modele/deconnexion.php">
                        <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                        Logout
                    </a>
                </div>
            </li>


        </ul>
    <?php } else { ?>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown no-arrow">

                <a class="nav-link dropdown-toggle" href="Authentification.php" target="_blank">
                    <img class="img-profile rounded-circle" src="../images/login.png">
                </a>
            </li>
        </ul>
    <?php } ?>
</nav>