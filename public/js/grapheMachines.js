const statsImage = document.getElementById('statsImage');
const statsCard = document.getElementById('statsCard');
let myChart; // Définir myChart dans un scope plus large pour être accessible dans toutes les fonctions

statsImage.addEventListener('click', function (event) {
    statsCard.style.display = 'block';
    event.stopPropagation();
    afficherGraphique();

    // Utiliser un seul écouteur pour gérer les clics en dehors de statsCard
    document.addEventListener('click', handleOutsideClick);
});

function handleOutsideClick(event) {
    // Vérifiez si l'élément cliqué est en dehors de statsCard
    if (!statsCard.contains(event.target) && !statsImage.contains(event.target)) {
        statsCard.style.display = 'none';
        document.removeEventListener('click', handleOutsideClick);
    }
}

function afficherGraphique() {
    // Destruction du graphique existant
    if (myChart) {
        myChart.destroy();
    }

    var ctx = document.getElementById('myChart').getContext('2d');
    myChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: Object.keys(intersParMachine),
            datasets: [{
                label: 'Nombre d\'interventions par machine',
                data: Object.values(intersParMachine),
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
                        text: 'Machines',
                        font: {
                            size: 12
                        }
                    },
                    beginAtZero: true
                },
                y: {
                    title: {
                        display: true,
                        text: 'Nombre d\'interventions',
                        font: {
                            size: 12
                        }
                    },
                    beginAtZero: true,
                    suggestedMax: 30,
                    stepSize: 5
                }
            }
        }
    });
}
