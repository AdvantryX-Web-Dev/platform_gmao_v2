


// var matriculeInput = document.getElementById('matricule');
// var nomInput = document.getElementById('last_name');
// var prenomInput = document.getElementById('first_name');
// var emailInput = document.getElementById('email');
// var rfidInput = document.getElementById('rfid');


// matriculeInput.addEventListener('blur', function () {
//     var matricule = matriculeInput.value;

//     // Faites une requête HTTP GET à votre script PHP avec le matricule saisi
//     var xhr = new XMLHttpRequest();
//     xhr.open('GET', '../Controleur/EmployeController.php?matricule=' + matricule, true);
//     xhr.onreadystatechange = function () {
//         if (xhr.readyState === 4 && xhr.status === 200) {

//             var employe = JSON.parse(xhr.responseText);
//             if (employe.erreur) {

//                 createAlert('alert-danger', employe.erreur);
//                 nomInput.value = '';
//                 prenomInput.value = '';
//                 emailInput.value = '';
//                 passwordInput.value = '';
//                 rfidInput.value = '';
//             } else {
//                 nomInput.value = employe.nom;
//                 prenomInput.value = employe.prenom;
//                 rfidInput.value = employe.cart_rfid;
//                 emailInput.value = employe.email;
//             }
//         }
//     };
//     xhr.send();
// });

// function createAlert(type, message) {
//     var colElement = document.querySelector('.col-xs-12');
//     var alertDiv = document.createElement('div');
//     alertDiv.classList.add('alert', type);
//     alertDiv.textContent = message;
//     colElement.appendChild(alertDiv)
//     setTimeout(function () {
//         alertDiv.style.display = 'none';
//     }, 3000);
// }
function validateChamps(event) {
    var motDePasse = document.getElementById('password-field').value;
    var nom = document.getElementById('last_name').value;
    var prenom = document.getElementById('first_name').value;

    if (!nom || !prenom) {
        event.preventDefault();

        if (!nom) {
            var errorDivNom = document.getElementById('nom-error');
            errorDivNom.textContent = "Veuillez remplir le champ de nom avant de soumettre le formulaire.";
        } else {
            var errorDivPren = document.getElementById('prenom-error');
            errorDivPren.textContent = "Veuillez remplir le champ de prénom avant de soumettre le formulaire.";
        }

        return false; // Exit the function if there is an error
    }
}

var form = document.querySelector('.AjoutEmp');
form.addEventListener('submit', validateChamps);

var matriculeInput = document.getElementById('matricule');
var nomInput = document.getElementById('last_name');
var prenomInput = document.getElementById('first_name');
var emailInput = document.getElementById('email');
var rfidInput = document.getElementById('rfid');

matriculeInput.addEventListener('blur', function () {
    var matricule = matriculeInput.value;

    // Faites une requête HTTP GET à votre script PHP avec le matricule saisi
    var xhr = new XMLHttpRequest();
    xhr.open('GET', '../Controleur/RecupereEmp.php?matricule=' + matricule, true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var employe = JSON.parse(xhr.responseText);
                if (employe.erreur) {
                    createAlert('alert-danger', employe.erreur);
                    nomInput.value = '';
                    prenomInput.value = '';
                    emailInput.value = '';
                    rfidInput.value = '';
                } else {
                    nomInput.value = employe.first_name;
                    prenomInput.value = employe.last_name;
                    rfidInput.value = employe.card_rfid;
                    emailInput.value = employe.email;
                }
            } catch (e) {
                console.error('Error parsing JSON response:', e);
                createAlert('alert-danger', 'Une erreur est survenue lors de la récupération des données.');
            }
        }
    };
    xhr.send();
});

function createAlert(type, message) {
    var containerElement = document.querySelector('.container-fluid');
    var colElement = document.createElement('div');
    colElement.classList.add('col-xs-12');

    var alertDiv = document.createElement('div');
    alertDiv.classList.add('alert', 'alert-dismissible', type);
    alertDiv.textContent = message;

    var closeButton = document.createElement('button');
    closeButton.setAttribute('type', 'button');
    closeButton.classList.add('close');
    closeButton.setAttribute('data-dismiss', 'alert');
    closeButton.setAttribute('aria-label', 'Close');
    closeButton.innerHTML = '<span aria-hidden="true">&times;</span>';

    alertDiv.appendChild(closeButton);

    colElement.appendChild(alertDiv);
    containerElement.insertBefore(colElement, containerElement.firstChild);
    setTimeout(function () {
        alertDiv.style.display = 'none';
    }, 3500);
}
