// Fonction pour formater le nombre avec un zéro devant s'il est inférieur à 10
function formatterNombre(nombre) {
    return nombre < 10 ? `0${nombre}` : nombre;
}

// Sélectionner les éléments HTML pour la date et l'heure
const dateElement = document.getElementById('date');
const heureElement = document.getElementById('time');

// Fonction pour afficher la date et l'heure actuelles
function afficherDateHeure() {
    // Obtenir la date et l'heure actuelles
    const maintenant = new Date();
    const annee = maintenant.getFullYear();
    const mois = formatterNombre(maintenant.getMonth() + 1);
    const jour = formatterNombre(maintenant.getDate());
    const heures = formatterNombre(maintenant.getHours());
    const minutes = formatterNombre(maintenant.getMinutes());
    const secondes = formatterNombre(maintenant.getSeconds());

    // Afficher la date dans l'élément HTML
    dateElement.textContent = 'DATE: '.concat(`${jour}-${mois}-${annee}`);

    // Afficher l'heure dans l'élément HTML
    heureElement.textContent = 'HEURE: '.concat(`${heures}:${minutes}:${secondes}`);
}

afficherDateHeure();
//Utilise la fonction setInterval pour appeler la fonction afficherDateHeure toutes les 1000 millisecondes (1 seconde). Cela permet de mettre à jour la date et l'heure affichées en temps réel.
setInterval(afficherDateHeure, 1000);