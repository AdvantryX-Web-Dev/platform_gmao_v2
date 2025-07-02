$(document).ready(function() {
    $('#preventiveTable').DataTable({
        language: {
            search: "Rechercher:",
            lengthMenu: "Afficher _MENU_ éléments par page",
            info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments",
            infoEmpty: "Aucun élément à afficher",
            infoFiltered: "(filtré de _MAX_ éléments au total)",
            zeroRecords: "Aucun enregistrement correspondant trouvé",
            paginate: {
                first: "Premier",
                previous: "Précédent",
                next: "Suivant",
                last: "Dernier"
            }
        }
    });

    $('#curativeTable').DataTable({
        language: {
            search: "Rechercher:",
            lengthMenu: "Afficher _MENU_ éléments par page",
            info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments",
            infoEmpty: "Aucun élément à afficher",
            infoFiltered: "(filtré de _MAX_ éléments au total)",
            zeroRecords: "Aucun enregistrement correspondant trouvé",
            paginate: {
                first: "Premier",
                previous: "Précédent",
                next: "Suivant",
                last: "Dernier"
            }
        }
    });
});

const curativeBtn = document.getElementById("btnCurative");
const preventiveBtn = document.getElementById("btnPreventive");


const curativeSection = document.getElementById("curativeSection");
const preventiveSection = document.getElementById("preventiveSection");


curativeBtn.addEventListener("click", function() {

    curativeSection.style.display = "block";
    preventiveSection.style.display = "none";
});

preventiveBtn.addEventListener("click", function() {

    curativeSection.style.display = "none";
    preventiveSection.style.display = "block";
});