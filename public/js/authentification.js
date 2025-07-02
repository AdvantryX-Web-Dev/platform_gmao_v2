//un bouton d'affichage/masquage du mot de passe à côté du champ de mot de passe,
(function ($) {
    //Lorsqu'activé, le mode strict impose un ensemble de règles plus strictes sur le code JavaScript et génère des erreurs pour certaines pratiques jugées comme non recommandées ou susceptibles de provoquer des erreurs silencieuses.
    "use strict";

    $(".toggle-password").click(function () {
        //Cela est souvent utilisé avec des bibliothèques d'icônes comme Font Awesome pour changer l'icône d'un œil ouvert à un œil fermé, symbolisant la visibilité du mot de passe.
        $(this).toggleClass("fa-eye fa-eye-slash");
        //Récupère la valeur de l'attribut "toggle" de l'élément actuel (qui est censé être un sélecteur jQuery) et crée un objet jQuery avec cette valeur.
        var input = $($(this).attr("toggle"));
        if (input.attr("type") == "password") {
            input.attr("type", "text");
        } else {
            input.attr("type", "password");
        }
    });

})(jQuery);

const images = [
    'url(../images/machineT.jpg)',
    'url(../images/machineTex8.jpg)',
    'url(../images/machineTex7.jpg)',
    // Ajoutez autant d'images que nécessaire
];

let currentImageIndex = 0;
const backgroundContainer = document.getElementById('background-container');

function changeBackgroundImage() {
    backgroundContainer.style.backgroundImage = images[currentImageIndex];
    currentImageIndex = (currentImageIndex + 1) % images.length;
}

// Changez d'image toutes les 5 secondes (ajustez si nécessaire)
setInterval(changeBackgroundImage, 6000);

// Initialisez l'arrière-plan avec la première image
changeBackgroundImage();

document.addEventListener('DOMContentLoaded', function () {
    var loginForm = document.getElementById('loginForm');

    loginForm.addEventListener('submit', function (e) {
        e.preventDefault();

        var emailValue = document.getElementById('email').value;
        var passwordValue = document.getElementById('password-field').value;
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../Controleur/EmployeController.php');
        // En résumé, xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded'); est utilisé pour définir le type de contenu de la requête XHR
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            /*Cette partie de code définit une fonction à exécuter lorsque la requête XHR est terminée avec succès. L'événement onload est déclenché lorsque la réponse de la requête est complètement chargée et disponible.*/

            if (xhr.status === 200) {
                try {
                    // console.log("Réponse reçue:", xhr.responseText);
                    var response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        window.location.href = response.redirectUrl;
                    } else {
                        var alertHTML = '<div class="alert alert-danger" role="alert">' + response.error + '</div>';
                        document.getElementById('errorContainer').innerHTML = alertHTML;
                    }
                } catch (e) {
                    // Erreur de parsing JSON
                    console.error("Erreur de parsing JSON:", e);
                    console.log("Réponse reçue:", xhr.responseText);
                    
                    var alertHTML = '<div class="alert alert-danger" role="alert">Erreur de connexion au serveur. Veuillez réessayer.</div>';
                    document.getElementById('errorContainer').innerHTML = alertHTML;
                }
            } else {
                // Gérer les erreurs de requête
                alert('Erreur de requête: ' + xhr.status);
            }
        };
        xhr.send('email=' + encodeURIComponent(emailValue) + '&motDePass=' + encodeURIComponent(passwordValue) + '&connexion=1');
    });
});