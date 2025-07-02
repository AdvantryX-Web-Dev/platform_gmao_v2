$(document).ready(function () {
    $('#dataTable').DataTable({


        language: {
            search: "Rechercher:",
            // searchPlaceholder: "Saisissez votre recherche",
            lengthMenu: "Afficher _MENU_ éléments par page",
            info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments",
            infoEmpty: "Aucun élément à afficher",
            infoFiltered: "(filtré de _MAX_ éléments au total)",
            zeroRecords: "Aucun enregistrement correspondant trouvé",
            paginate: {
                first: "Premier",
                previous: "Précédent",
                next: "Suivant",
                last: "Dernier",

            }

        },
    });
});