//date format 
var dateCourante = new Date();

// // // Formatez la date et l'heure actuelles dans le format requis pour datetime-local
var year = dateCourante.getFullYear();
var month = ('0' + (dateCourante.getMonth() + 1)).slice(-2); // Les mois commencent à partir de 0, donc on ajoute 1
var day = ('0' + dateCourante.getDate()).slice(-2);
var hour = ('0' + dateCourante.getHours()).slice(-2);
var minute = ('0' + dateCourante.getMinutes()).slice(-2);
var currentDateTime = year + '-' + month + '-' + day + 'T' + hour + ':' + minute;
document.getElementById('dateIntervention').setAttribute('max', year + '-12-31T23:59');
document.getElementById('dateIntervention').setAttribute('min', currentDateTime);
document.getElementById('modificationDate').setAttribute('max', year + '-12-31T23:59');
document.getElementById('modificationDate').setAttribute('min', currentDateTime);

var id;
document.addEventListener('DOMContentLoaded', function () {
    var idIntervention;
    var calendarEl = document.getElementById('calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {

        plugins: ['interaction', 'dayGrid', 'timeGrid', 'list'],
        height: 'parent',
        header: {
            left: 'prev,next ',
            right: 'listYear,dayGridMonth,dayGridWeek,timeGridDay',
            center: 'title',
        },
        selectable: true,
        themeSystem: 'bootstrap',
        locale: 'fr',
        events: events,
        eventDidMount: function (info) { },
        buttonText: {},
        views: {
            dayGridMonth: {
                buttonText: 'Mois',

            },
            dayGridWeek: {
                buttonText: 'Semaine'
            },
            listYear: {
                buttonText: 'Année',


            },
            timeGridDay: {
                buttonText: 'Jour',

            },


        },
        editable: true,
        dayRender: function (info) {

            var today = new Date();
            today.setHours(0, 0, 0, 0);

            if (info.date.getTime() === today.getTime()) {
                info.el.style.backgroundColor = 'lightpink';
                info.el.style.borderRadius = '5px';
                info.el.style.padding = '5px';
                info.el.style.color = 'white';
            }

        },
        yearRender: function (info) {
            info.el.style.color = 'white';
        },

        dayHeaderContent: function (args) {
            return '<div style="text-align: center;">' +
                '<div>' + args.day.date.format('dddd') + '</div>' +
                '<div>' + args.day.date.format('DD/MM') + '</div>' +
                '</div>';
        },

        slotLabelFormat: [{
            hour: 'numeric',
            minute: '2-digit',
            omitZeroMinute: false,
            meridiem: 'short'
        }],



        eventClick: function (info) {
            idIntervention = info.event.id;
            var designation = info.event.extendedProps.designation;
            var datePrevue = info.event.start;
            var currentDate = new Date();
            var typeMachine = info.event.extendedProps.typeMachine;
            var numInter = info.event.extendedProps.numInter;
            var statutIntervention = info.event.extendedProps.statut;
            var freq = info.event.extendedProps.frequence;

            $('#designation').text(designation);
            $('#typeMachine').text(typeMachine);
            $('#dateI').text(datePrevue.toLocaleDateString('fr-FR'));

            var btnModifier = $('#modifier');
            var btnValider = $('#valider');
            var alerteIntervention = document.getElementById('alerteIntervention');
            var iconeIntervention = document.getElementById('iconeIntervention');
            if (statutIntervention.toLowerCase() != 'validée') {
                if (statutIntervention.toLowerCase() == 'enretard') {
                    alerteIntervention.classList.remove('alert-primary', 'alert-success');
                    alerteIntervention.classList.add('alert-danger');
                    iconeIntervention.style.color = 'darkred';

                } else {
                    alerteIntervention.classList.remove('alert-danger', 'alert-success');
                    alerteIntervention.classList.add('alert-primary');
                    iconeIntervention.style.color = '#004080';

                }
                btnModifier.show();
            } else {
                alerteIntervention.classList.remove('alert-primary', 'alert-danger');
                alerteIntervention.classList.add('alert-success');
                iconeIntervention.style.color = 'darkgreen';
                btnModifier.hide(); // Masquer le bouton Modifier
            }

            if (statutIntervention.toLowerCase() === 'validée' || datePrevue > currentDate) {
                btnValider.hide();
            } else {
                btnValider.show();
            }


            $('#eventModal').modal('show');
            //modifier event 
            // Fonction pour récupérer les détails de l'événement
            function getEventDetails(event) {

                return {
                    id: event.id,
                    numInter: event.numInter,
                    typeMachine: event.extendedProps.typeMachine,
                    designation: event.extendedProps.designation,
                    freq: event.extendedProps.frequence,
                    datePrevue: event.start
                };
            }

            // Ajoutez un événement click sur le bouton "Modifier"
            var modifier = document.getElementById('modifier');

            modifier.addEventListener('click', function () {
                // Récupérez les détails de l'événement pour la modification
                var eventDetails = getEventDetails(info.event);

                $('#modifierInterventionModal').modal('show');

                // Remplissez les champs du formulaire avec les détails de l'événement
                var options = {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit'
                };
                /*const year = date.getFullYear();: Cette ligne extrait l'année de la date en utilisant la méthode getFullYear(). Par exemple, si la date est le 15 mars 2024, year sera égal à 2024.

// const month = (date.getMonth() + 1).toString().padStart(2, '0');: Ici, on extrait le mois de la date en utilisant getMonth(). Cependant, getMonth() renvoie les mois de 0 à 11, où 0 correspond à janvier et 11 à décembre. En ajoutant 1 (date.getMonth() + 1), on obtient le mois réel de 1 à 12. Ensuite, toString() convertit ce nombre en chaîne de caractères, et padStart(2, '0') ajoute un zéro devant si le mois a une seule chiffre (par exemple, de 1 à 9). Ainsi, month sera une chaîne de deux chiffres représentant le mois.

// const day = date.getDate().toString().padStart(2, '0');: De manière similaire, cette ligne extrait le jour du mois à l'aide de getDate(), le convertit en chaîne de caractères, et ajoute un zéro devant s'il n'y a qu'un chiffre. day est une chaîne de deux chiffres représentant le jour du mois.*/
                function formatDatetimeLocal(date) {
                    const year = date.getFullYear();
                    const month = (date.getMonth() + 1).toString().padStart(2, '0');
                    const day = date.getDate().toString().padStart(2, '0');
                    const hours = date.getHours().toString().padStart(2, '0');
                    const minutes = date.getMinutes().toString().padStart(2, '0');

                    return `${year}-${month}-${day}T${hours}:${minutes}`;
                }
                document.getElementById('modificationDate').value = formatDatetimeLocal(eventDetails.datePrevue);
                // document.getElementById('typeModifier').value = eventDetails.typeMachine;
                document.querySelector('#typeModifier').value = eventDetails.typeMachine
                console.log(eventDetails.typeMachine);

                var modificationDesignationValue = document.getElementsByTagName('input')[1].value;

                console.log(document.getElementsByName('types')[0].value);
                document.getElementById('modificationDesignation').value = eventDetails.designation;
                console.log(eventDetails.designation);
                console.log(document.getElementById('modificationDesignation').value);
                // var freq = freqData[eventDetails.id] ? freqData[eventDetails.id].frequence : null;
                // console.log(freq);
                document.getElementById('frequenceMod').value = freq;
                document.getElementById('idInter').value = idIntervention;


            });


            //valider evenment
            var valider = document.getElementById('valider');
            valider.addEventListener('click', function (e) {

                var confirmation = confirm("Voulez-vous vraiment valider cette intervention ?");
                if (confirmation == true) {
                    window.location.href = "../Controleur/inte_Preve_ProgController.php?idIntervention=" + idIntervention;
                } else {

                    e.preventDefault();
                }
                window.addEventListener('touchmove', function () {
                    // Votre code de gestion d'événements ici
                }, {
                    passive: true
                });
            });


        },

        //action pour les évenements
        eventRender: function (info) {

            var statut = info.event.extendedProps.statut;


            if (statut.toLowerCase() == 'validée') {

                info.el.style.backgroundColor = 'rgba(0, 128, 0, 0.5)';


            } else if (statut.toLowerCase() == 'enretard') {

                info.el.style.backgroundColor = 'rgb(241, 99, 71)'; // Couleur rouge pour les événements dont la date est dépassée

            }
            //evenement en retard
        },



    });
    calendar.render();
    var currentDate = new Date();
    var currentDateFormatted = currentDate.toISOString().split('T')[0];
    events.forEach(function (event) {
        var datePrevue = new Date(event.start);
        var statutIntervention = event.statut;
        var datePrevueFormatted = datePrevue.toISOString().split('T')[0];
        /*ous utilisons la méthode toISOString() pour convertir la date en une chaîne de caractères au format ISO 8601, qui inclut la date et l'heure. Ensuite, nous utilisons split('T') pour diviser cette chaîne en deux parties à chaque occurrence du caractère 'T',*/
        if (datePrevueFormatted < currentDateFormatted && statutIntervention.toLowerCase() === 'planifiée') {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '../Controleur/inte_Preve_ProgController.php?idInterven=' + event.id + '&nouveauStatut=EnRetard', true);

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    console.log('La base de données a été mise à jour automatiquement.');
                }
            };
            xhr.send();
        }
    });
    window.addEventListener('touchmove', function () {
        // Votre code de gestion d'événements ici
    }, {
        passive: true
    });
});

/*cette nouvelle version offre une meilleure gestion des modals en évitant les conflits potentiels entre les modals et en assurant que le code JavaScript s'exécute uniquement pour le modal actuellement affiché.*/
$(document).ready(function () {
    $('#modifierInterventionModal').on('shown.bs.modal', function () {
        var typeMSelect = $(this).find('.typeM');
        var designationSelect = $(this).find('.designation');
        typeMSelect.on('change', function () {
            var selectedType = $(this).val();
            var mRequest = new XMLHttpRequest();

            mRequest.onreadystatechange = function () {
                if (this.readyState == 4 && this.status == 200) {
                    var typeIntervs = JSON.parse(this.responseText);
                    designationSelect.html("");
                    typeIntervs.forEach(function (typeInterv) {
                        var option = $("<option></option>").attr("value", typeInterv.numInter).text(typeInterv.designation);
                        designationSelect.append(option);
                    });
                }
            };

            mRequest.open("GET", "../Controleur/TypeMach_TypeInterController.php?type=" + selectedType, true);
            mRequest.send();
        });
    });

    $('#ajouterInterventionModal').on('shown.bs.modal', function () {
        var typeMSelect = $(this).find('.typeM');
        var designationSelect = $(this).find('.designation');
        typeMSelect.on('change', function () {
            var selectedType = $(this).val();
            var mRequest = new XMLHttpRequest();

            mRequest.onreadystatechange = function () {
                if (this.readyState == 4 && this.status == 200) {
                    var typeIntervs = JSON.parse(this.responseText);
                    designationSelect.html("");
                    typeIntervs.forEach(function (typeInterv) {
                        var option = $("<option></option>").attr("value", typeInterv.numInter).text(typeInterv.designation);
                        designationSelect.append(option);
                    });
                }
            };

            mRequest.open("GET", "../Controleur/TypeMach_TypeInterController.php?type=" + selectedType, true);
            mRequest.send();
        });
    });
});

function checkAndCopyInterventions() {
    let currentDate = new Date(); // Obtenir la date actuelle
    let currentMonth = currentDate.getMonth() + 1;
    let currentDay = currentDate.getDate();
    let currentHour = currentDate.getHours();
    let currentMinute = currentDate.getMinutes();
    let currentSeconde = currentDate.getSeconds();
    // Vérifier si la date actuelle est le 16 avril
    if (currentMonth === 1 && currentDay === 1 /* && currentHour === 20 && currentMinute === 51*/) {
        let lastCopiedDate = localStorage.getItem('lastCopiedDate');
        let today = currentDate.toDateString();
        if (lastCopiedDate !== today) {
            // Appeler ici la fonction pour copier les interventions de l'année précédente
            let currentYear = currentDate.getFullYear(); // Obtenir l'année précédente
            copyInterventionsToNextYear(currentYear);

        }
        localStorage.setItem('lastCopiedDate', today);
    }
    setTimeout(checkAndCopyInterventions, 60000);
}

// // Fonction pour copier les interventions vers la nouvelle année
// function copyInterventionsToNextYear(currentYear) {
//     // Créer un objet XMLHttpRequest
//     var xhr = new XMLHttpRequest();
//     // Définir la fonction de rappel pour gérer la réponse
//     xhr.onreadystatechange = function () {
//         if (xhr.readyState === XMLHttpRequest.DONE) {
//             if (xhr.status === 200) {
//                 console.log(xhr.responseText);
//                 // Traiter la réponse ici si nécessaire
//             } else {
//                 console.error('Erreur lors de la requête AJAX : ' + xhr.status);
//             }
//         }
//     };
//     // Ouvrir une connexion vers le contrôleur PHP
//     xhr.open('POST', '../Controleur/inte_Preve_ProgController.php', true);
//     // Définir les en-têtes de la requête
//     xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
//     // Envoyer la requête avec les données nécessaires
//     xhr.send('action=copy_interventions&currentYear=' + currentYear);
// }


// let currentDate = new Date();


// checkAndCopyInterventions();