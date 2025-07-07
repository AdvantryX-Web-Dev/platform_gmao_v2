var myChart = null; // Déclarez myChart en dehors de la fonction

document.addEventListener('DOMContentLoaded', function () {

    var machineRows = document.querySelectorAll('.machine-row');
    var statsCard = document.getElementById('statsCard');

    machineRows.forEach(function (row) {
        var machineId = row.querySelector('[machine-id]').textContent.trim();
        row.addEventListener('click', function () {

            afficherGraphique(machineId);
        });
    });
});

function afficherGraphique(machineId) {

    // Destruction du graphique existant
    if (myChart) {
        myChart.destroy();
    }

    // Récupérez les données spécifiques de la machine
    const machineData = allMachinesData[machineId];
    console.log(allMachinesData);
    var ctx = document.getElementById('myChart').getContext('2d');
    myChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: machineData.map(data => data.codePanne),
            datasets: [{
                label: 'Nombre d\'interventions',
                data: machineData.map(data => data.nbInter),
                backgroundColor: '#007BFF',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
        

            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Pannes',
                        font: {
                            size: 12 // Ajustez la taille de la police
                        }
                    },
                    beginAtZero: true
                },
                y: {
                    title: {
                        display: true,
                        text: 'Nombre d\'interventions',
                        font: {
                            size: 12 // Ajustez la taille de la police
                        }
                    },
                    beginAtZero: true,
                    suggestedMax: 30,
                    stepSize: 5
                },

            },


        }
    });
    statsCard.querySelector('.card-header').innerHTML = '<h5 class="m-0"><i class="fas fa-chart-bar"></i> Statistiques de la Machine ' + machineId + '</h5>';

    // Affichez la carte statistique
    document.getElementById('statsCard').style.display = 'block';

    event.stopPropagation();

    // Ajoutez un gestionnaire d'événements pour masquer la carte statistique lorsqu'on clique en dehors du graphique
    document.addEventListener('click', function (event) {
        // Vérifiez si l'élément cliqué est en dehors du graphique
        if (!document.getElementById('myChart').contains(event.target)) {
            // Masquez la carte statistique
            document.getElementById('statsCard').style.display = 'none';
        }
    });
}