$(document).ready(function () {


    $('#RecevoirTable').DataTable({
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
$(document).ready(function () {


    $('#NumeroTable').DataTable({
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
    $(document).on('click', '.Soumisssion', function () {
        var id_machine = $(this).data('id');


        $('#numSou').modal('show');

        $('#id_mach').val(id_machine);

    });

});



const btnRecevoir = document.getElementById("btnRecevoir");
const btnEmprunter = document.getElementById("btnEmprunter");
const btnNum = document.getElementById("btnNum");

const emprunterSection = document.getElementById("emprunterSection");
const recevoirSection = document.getElementById("recevoirSection");
const NumeroSection = document.getElementById("NumeroSection");

btnEmprunter.addEventListener("click", function () {

    emprunterSection.style.display = "block";
    recevoirSection.style.display = "none";
    NumeroSection.style.display = "none";
});

btnRecevoir.addEventListener("click", function () {
    NumeroSection.style.display = "none";
    emprunterSection.style.display = "none";
    recevoirSection.style.display = "block";
});
btnNum.addEventListener("click", function () {
    recevoirSection.style.display = "none";
    emprunterSection.style.display = "none";
    NumeroSection.style.display = "block";
});
$(document).ready(function () {
    $('#type').select2({
        placeholder: '--Type de Machine--',
        tags: false,
        tokenSeparators: [',', ' '],
        maximumSelectionLength: 1,
        // theme: 'classic',
    });
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
            option.textContent = machine.machine_id;
            machineSelect.appendChild(option);
        });
    }

    $('#machineSelect').on('change', function () {
        var selectedMachineId = $(this).val();
        var machineRequest = new XMLHttpRequest();
        machineRequest.onreadystatechange = function () {
            if (this.readyState == 4 && this.status == 200) {
                var machineDetails = JSON.parse(this.responseText);
                console.log(machineDetails);
                $('#refMach').val(machineDetails.reference);
                $('#numTete').val(machineDetails.numTete);
            }
        };
        machineRequest.open("GET", "../Controleur/getMachineDetails.php?id=" + selectedMachineId, true);
        machineRequest.send();
    });
    $('#machineSelect').select2({
        placeholder: '--Sélectionnez une machine--',
        tags: false,
        tokenSeparators: [',', ' '],
        maximumSelectionLength: 1,
        // theme: 'classic', // Si vous souhaitez utiliser un thème différent
    });

});
