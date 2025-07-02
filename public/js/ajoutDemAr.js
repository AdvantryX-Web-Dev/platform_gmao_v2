var nombreChamps = 0;

function ajouterReferenceQuantite() {
    var nouvelleReferenceQuantite = '<div class="demandeDetailsContainer"><label for="referenceArticle" class="form-label" style="color: black;  font-size: 20px;"> Article : </label><br>' +
        ' <select class="form-select form-control article"  name="refarticles[]" multiple required>';

    // Ajouter les options basées sur les données PHP
    articles.forEach(function (article) {
        var quantite = article.qte_stock;
        var seuil = article.stock_Min;
        var quantiteDisponible = (quantite - seuil) > 0 ? (quantite - seuil) : 0;
        nouvelleReferenceQuantite += '<option value="' + article.reference + '" data-quantite="' + quantiteDisponible + '">' + article.reference + ' ' + article.designation + '</option>';
    });

    nouvelleReferenceQuantite += '</select> <br><br>' +
        '<div class="quantite">' +
        ' <label for="quantite" class="form-label" style="color: black;  font-size: 20px;">Quantité : </label><br>' +
        ' <input type="number" class="form-control ContenuQte " style="width: 80px;" name="quantites[]" value="1" min="1" required> </div> </div> ';

    $('#demandeDetailsContainer').append(nouvelleReferenceQuantite);
    nombreChamps++;
    if (nombreChamps > 0) {
        $('.action-message').replaceWith('<img class="round-button supprimerChamp" src="../images/moins5.png" alt="Supprimer" onclick="supprimerDernierChamp()" style="width: 46px; height: 46px; margin-top:-4px;">');
    }
    $('.supprimerChamp').removeClass('disabled');
    // Réinitialiser Select2 sur le nouvel élément
    $('.article').select2({
        placeholder: 'Saisissez un article',
        tags: false,
        tokenSeparators: [',', ' '], // Séparateurs de jetons (virgule et espace)
        maximumSelectionLength: 1,
        language: "fr"
    });

    $('.article').change(function () {
        var quantiteActuelle = $(this).find(':selected').data('quantite');
        console.log(quantiteActuelle);
        $('.ContenuQte').attr('max', quantiteActuelle);
    });
}

function supprimerDernierChamp() {
    $('#demandeDetailsContainer .demandeDetailsContainer:last').remove();
    nombreChamps--;
    if (nombreChamps === 0) {
        var iconeMoins = $('.supprimerChamp');
        var message = $('<span class="action-message"style="position: relative; top:0.5vw;">Ajouter un autre article</span>');
        iconeMoins.replaceWith(message);
        $('.supprimerChamp').addClass('disabled');
    }
}

//article
$(document).ready(function () {
    $('.article').select2({
        placeholder: 'Selectionner un article',
        tags: false,
        tokenSeparators: [',', ' '], // Séparateurs de jetons (virgule et espace)
        maximumSelectionLength: 1,
        language: "fr"
    });

    $('.supprimerChamp').addClass('disabled');

});