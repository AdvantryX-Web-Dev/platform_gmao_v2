$(document).ready(function () {
    $('#type').on('change', function () {
        var selectedType = $(this).val();
        console.log(selectedType);
        var mRequest = new XMLHttpRequest();
        mRequest.onreadystatechange = function () {
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

        machines.forEach(function (machine) {
            var option = document.createElement("option");
            option.value = machine.machine_id;
            option.textContent = machine.machine_id + " - " + machine.reference;
            machineSelect.appendChild(option);
        });
    }

    $('#machineSelect').select2({
        placeholder: '--Sélectionnez une machine--',
        tags: false,
        tokenSeparators: [',', ' '],
        maximumSelectionLength: 1,
        // theme: 'classic', // Si vous souhaitez utiliser un thème différent
    });
});

function MouvementMach() {
    var selectElement = document.getElementById('raisonM');
    var entreRadio = document.getElementById('entreRadio');
    var sortieRadio = document.getElementById('sortieRadio');
    if (entreRadio.checked) {
        var typeSelectionne = entreRadio.value;
        // Mettez à jour le libellé de la liste déroulante et ses options pour l'entrée
        document.getElementById('labelRaisonM').innerHTML = 'Raison Mouvement Machine (Entrée)';
        $('#raisonM option').hide();

        // Afficher uniquement les options du type sélectionné
        $('#raisonM option[data-type="' + typeSelectionne + '"]').show();

        $('#raisonM').val($('#raisonM option[data-type="' + typeSelectionne + '"]:visible:first').val());



    } else {
        var typeSelectionne = sortieRadio.value;
        // Mettez à jour le libellé de la liste déroulante et ses options pour la sortie
        document.getElementById('labelRaisonM').innerHTML = 'Raison Mouvement Machine (Sortie)';
        $('#raisonM option').hide();

        // Afficher uniquement les options du type sélectionné
        $('#raisonM option[data-type="' + typeSelectionne + '"]').show();

        $('#raisonM').val($('#raisonM option[data-type="' + typeSelectionne + '"]:visible:first').val());
        //  selectElement.innerHTML = '<option value="Décision de Coudre la Ferraille">Décision de Coudre la Ferraille</option><option value="Déplacement pour Optimiser la Chaîne de Production">Déplacement pour Optimiser la Chaîne de Production</option>';
    }
};
document.getElementById("sortieRadio").addEventListener("change", MouvementMach);
document.getElementById("entreRadio").addEventListener("change", MouvementMach);