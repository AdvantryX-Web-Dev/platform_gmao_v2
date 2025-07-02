function toggleDetails() {
    var typeIntervention = document.getElementById('typeIntervention').value;
    var curatifDetails = document.getElementById('curatifDetails');
    var preventifDetails = document.getElementById('preventifDetails');

    if (typeIntervention === 'curative') {
        curatifDetails.style.display = 'block';
        preventifDetails.style.display = 'none';
    } else if (typeIntervention === 'preventive') {
        curatifDetails.style.display = 'none';
        preventifDetails.style.display = 'block';
    } else {
        curatifDetails.style.display = 'none';
        preventifDetails.style.display = 'none';
    }
}
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.typeMach').addEventListener('change', function() {
        var selectedType = this.value;

        var mRequest = new XMLHttpRequest();

        mRequest.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                var pannes = JSON.parse(this.responseText);
                updatePanne(pannes);
            }
        };

        mRequest.open("GET", "../Controleur/FilterPannes.php?type=" + selectedType, true);
        mRequest.send();
    });

    function updatePanne(pannes) {
        var panneSelect = document.querySelector(".PanneI");
        panneSelect.innerHTML = "";

        pannes.forEach(function(panne) {
            var option = document.createElement("option");
            option.value = panne.failure_code;
            option.textContent = panne.failure_code + '' + panne.failure_type;
            panneSelect.appendChild(option);
        });
    }


    $(document).ready(function() {
        $('.PanneI').select2({
            placeholder: '--Type Pannes--',
            tags: false,
            tokenSeparators: [',', ' '], // Séparateurs de jetons (virgule et espace)
            language: "fr"

        });
    });

});

document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.typeMach').addEventListener('change', function() {
        var selectedType = this.value;
        var mRequest = new XMLHttpRequest();

        mRequest.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                var typeIntervs = JSON.parse(this.responseText);
                updateTypeIntervs(typeIntervs);
            }
        };

        mRequest.open("GET", "../Controleur/TypeMach_TypeInterController.php?type=" + selectedType, true);
        mRequest.send();
    });

    function updateTypeIntervs(typeIntervs) {
        var designationSelect = document.querySelector(".designation");
        designationSelect.innerHTML = "";

        typeIntervs.forEach(function(typeInterv) {
            var option = document.createElement("option");
            option.value = typeInterv.numInter;
            option.textContent = typeInterv.designation;
            designationSelect.appendChild(option);
        });
    }
});
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.typeMach').addEventListener('change', function() {
        var selectedType = this.value;
        var mRequest = new XMLHttpRequest();

        mRequest.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                var machines = JSON.parse(this.responseText);
                updateMachine(machines);
            }
        };

        mRequest.open("GET", "../Controleur/filterMachines.php?type=" + selectedType, true);
        mRequest.send();
    });

    function updateMachine(machines) {
        var machineSelect = document.getElementById("machineSelect");
        machineSelect.innerHTML = "";

        machines.forEach(function(machine) {
            var option = document.createElement("option");
            option.value = machine.machine_id;
            option.textContent = machine.machine_id + '-' + machine.designation;
            machineSelect.appendChild(option);
        });
    }


    $('#machineSelect').select2({
        placeholder: '--Sélectionnez une machine--',
        tags: false,
        tokenSeparators: [',', ' '],
        maximumSelectionLength: 1,
        language: "fr"

    });
});
$(document).ready(function() {
    $('.articleList').select2({
        placeholder: 'Saisissez un article',
        tags: false,
        tokenSeparators: [',', ' '], // Séparateurs de jetons (virgule et espace)
        language: "fr"

    });
});
$('.articleList').change(function() {
    // Obtenez la liste des articles sélectionnés
    var selectedArticles = $(this).val();

    // Videz le conteneur des champs de quantité
    $('.quantityFieldsContainer').empty();


    if (selectedArticles) {
        selectedArticles.forEach(function(article) {
            var quantityField = '<div class="mb-3" style="display: flex; align-items: center;">' +
                '<label for="' + article + '" class="form-label" style="margin-right: 10px;">Quantité pour ' + article + ':</label>' +
                '<input type="number" class="form-control quantity-input" name="quantite" value="0" min="0">' +
                '</div>';
            $('.quantityFieldsContainer').append(quantityField);
        });
    }
});
$('.article').select2({
    placeholder: 'Selectionner  un article',
    tags: false,
    tokenSeparators: [',', ' '], // Séparateurs de jetons (virgule et espace)
    maximumSelectionLength: 1,
    language: "fr"
});
    //boxMachine
    document.addEventListener('DOMContentLoaded', function() {

        // document.getElementById("machineSelect").addEventListener("change", function() {
        $('#machineSelect').on('select2:select', function(e) {

            console.log(e.params.data)
            // var Machine = this.value;
            var Machine = e.params.data.id;
            console.log(Machine);
            var mRequest = new XMLHttpRequest();
            mRequest.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    var response = JSON.parse(mRequest.responseText);
                    document.getElementById("operatriceI").value = response.operator;
                    document.getElementById("boxI").value = response.smartbox;
                }
            };
            mRequest.open("GET", "../Controleur/InterCu_Box_Op.php?id_machine=" + Machine, true);
            mRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            mRequest.send();
        });


    });


    //interv de chaque ligne d'intervention
    $(document).ready(function() {

        $(document).on('click', '.AjoutInterv', function() {
            var machine_id = $(this).data('id');
            var typeMachine = $(this).closest('tr').find('.typeMachine').text();
            var id = $(this).closest('tr').find('.id').text();
            var date = $(this).closest('tr').find('.date').text();
            // Envoyer typeMachine au serveur via une requête AJAX
            $.ajax({
                url: '../Modal/PanneTypeMach.php',
                method: 'POST',
                data: {
                    id: id
                },
                success: function(response) {
                    $('#typePanne').html(response);

                },
                error: function(xhr, status, error) {
                    console.error(error);
                }
            });
            var operatrice = $(this).closest('tr').find('.operatrice').text();
            var smartBox = $(this).closest('tr').find('.box').text();
            var group = $(this).closest('tr').find('.group').text();
            $('#interventionMain').modal('show');


            $('#machine').val(machine_id);
            $('#typeMac').val(typeMachine);
            $('#idMac').val(id);
            $('#date').val(date);
            $('#operatrice').val(operatrice);

            $('#box').val(smartBox);
            $('#group').val(group);

        });

    });
    $(document).ready(function() {
        $('.Panne').select2({
            placeholder: '--Type Pannes--',
            tags: false,
            tokenSeparators: [',', ' '], // Séparateurs de jetons (virgule et espace)
            language: "fr"

        });
    });