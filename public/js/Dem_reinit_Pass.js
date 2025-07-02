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
    var resetEmailForm = document.getElementById('resetEmailForm');

    resetEmailForm.addEventListener('submit', function (e) {
        e.preventDefault(); // Empêche le formulaire de se soumettre normalement

        var emailValue = document.getElementById('email').value;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../Controleur/Traitement_reset_password.php');
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            if (xhr.status === 200) {
                console.log(xhr.responseText)
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    // Afficher un message de succès
                    var successAlert = document.createElement('div');
                    successAlert.classList.add('alert-box');
                    successAlert.innerHTML = `
                    <h2>Nous avons bien reçu votre demande de réinitialisation de mot de passe</h2>
                    <p>Nous venons de vous envoyer un courriel contenant toutes les informations nécessaires à la récupération de votre mot de passe. Si l'email n'arrive pas, veuillez vérifier votre dossier spam.</p>
                    <p>Vous n'avez pas reçu le lien ? Vous pouvez en envoyer un autre.</p>
                `;
                    var container = document.getElementsByClassName('col-lg-4')[0];
                    container.insertBefore(successAlert, container.firstChild);
                } else {

                    var alertHTML = '<div class="alert alert-danger" role="alert">' + response.error + '</div>';
                    document.getElementById('errorContainer').innerHTML = alertHTML;
                }
            } else {
                // Gérer les erreurs de requête
                alert('Erreur de requête: ' + xhr.status);
            }
        };
        xhr.send('email=' + encodeURIComponent(emailValue) + '&Dem_reset_pass=1');
    });
});
