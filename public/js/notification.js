var currentSpeechSynthesis;

function checkForNotifications() {

    var mRequest = new XMLHttpRequest();
    mRequest.onreadystatechange = function () {
        if (this.readyState == 4) {
            if (this.status == 200) {
                var response = JSON.parse(mRequest.responseText);

                // Arrêter la synthèse vocale actuelle si elle existe
                if (currentSpeechSynthesis) {
                    currentSpeechSynthesis.onend = null;
                    window.speechSynthesis.cancel();
                }

                // Vérifier si des notifications sont présentes dans la réponse JSON
                if (response.notifications.length > 0) {

                    response.notifications.forEach(function (notification) {
                        // Créer un nouvel objet SpeechSynthesisUtterance pour chaque notification

                        var msg = new SpeechSynthesisUtterance('Monitrice ' + notification['monitor'] + ' informe que la machine ' + notification['machine_id'] + '         ' + ' sur la chaine ' + notification['chaine'] + ' est en panne.');


                        // Désactiver l'événement onend pour éviter l'affichage du message de fin
                        msg.onend = function (event) {
                            // Aucun message de fin ici
                        };

                        // Démarrer la synthèse vocale pour chaque notification
                        window.speechSynthesis.speak(msg);
                    });

                } else {


                    if (currentSpeechSynthesis) {
                        currentSpeechSynthesis.onend = null;
                        window.speechSynthesis.cancel();
                    }
                }
            } else {
                console.error("Error status: " + mRequest.status);
            }
        }
    }

    mRequest.open("GET", "../Controleur/NotificationChefMainController.php", true);
    mRequest.send();
}

function NotificationsMaint() {

    var mRequest = new XMLHttpRequest();
    mRequest.onreadystatechange = function () {
        if (this.readyState == 4) {
            if (this.status == 200) {
                var response = JSON.parse(mRequest.responseText);

                // Arrêter la synthèse vocale actuelle si elle existe
                if (currentSpeechSynthesis) {
                    currentSpeechSynthesis.onend = null;
                    window.speechSynthesis.cancel();
                }

                // Vérifier si des notifications sont présentes dans la réponse JSON
                if (response.notificationsMain.length > 0) {

                    response.notificationsMain.forEach(function (notification) {

                        // Créer un nouvel objet SpeechSynthesisUtterance pour chaque notification
                        var msg = new SpeechSynthesisUtterance('Monitrice ' + notification['monitor'] + ' informe que la machine ' + notification['machine_id'] + ' sur la chaine ' + notification['group'] + ' est en panne.');

                        // Désactiver l'événement onend pour éviter l'affichage du message de fin
                        msg.onend = function (event) {
                            // Aucun message de fin ici
                        };

                        // Démarrer la synthèse vocale pour chaque notification
                        window.speechSynthesis.speak(msg);

                    });

                } else {

                    // Si aucune notification n'est trouvée, arrêter la synthèse vocale actuelle
                    if (currentSpeechSynthesis) {
                        currentSpeechSynthesis.onend = null;
                        window.speechSynthesis.cancel();
                    }
                }
            } else {
                // Commenter ou supprimer la ligne suivante pour éviter tout affichage visuel lié à l'erreur
                console.error("Error status: " + mRequest.status);
            }
        }
    }

    mRequest.open("GET", "../Controleur/NotificationMain.php", true);
    mRequest.send();
}