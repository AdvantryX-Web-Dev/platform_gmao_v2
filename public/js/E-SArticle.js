
function toggleDetails() {
    var sortieRadio = document.getElementById("sortieRadio");
    var maintenancier = document.getElementById("maintenancier");
    var nomArt = document.getElementById("nomArt");
    var type = document.getElementById("TypeO");
    var seuil = document.getElementById("seuil");

    if (sortieRadio.checked) {
        maintenancier.style.display = "block";
        nomArt.style.display = "none";
        type.style.display = "none";
        seuil.style.display = "none";
    } else {
        maintenancier.style.display = "none";
        nomArt.style.display = "block";
        type.style.display = "block";
        seuil.style.display = "block";
    }
}
document.getElementById("sortieRadio").addEventListener("change", toggleDetails);
document.getElementById("entreRadio").addEventListener("change", toggleDetails);
$(document).ready(function () {
    $("#articleRef").change(function () {

        var selectedReference = $(this).val();
        console.log(selectedReference);
        var mRequest = new XMLHttpRequest();
        mRequest.onreadystatechange = function () {
            if (this.readyState == 4 && this.status == 200) {
                var articleDetails = JSON.parse(mRequest.responseText);

                if (articleDetails) {
                    $('#designation').val(articleDetails.designation).prop('readonly', true);
                    $('#seuilA').val(articleDetails.stock_Min).prop('readonly', true);
                    $('#type').val(articleDetails.typologie).prop('readonly', true);
                } else {
                    $('#designation, #seuilA,#type').val('').prop('readonly', false).prop('disabled', false);
                }
            } else {
                // Gérez les erreurs si la requête n'a pas abouti avec le statut HTTP 200
                console.error("Erreur de requête: " + mRequest.status);
            }

        };


        mRequest.open("GET", '../Controleur/ArticleByRef.php?reference=' + selectedReference, true);
        mRequest.send();
    });

    $("#article").on('select2:unselect', function () {
        $('#designation, #seuilA,#type').val('').prop('readonly', false);
    });
});
$(document).ready(function () {
    $('#articleRef').select2({
        placeholder: 'Selectionner un article',
        tags: true,
        tokenSeparators: [',', ' '], // Séparateurs de jetons (virgule et espace)
        maximumSelectionLength: 1,
        language: "fr"
    });



});