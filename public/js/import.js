document.addEventListener("DOMContentLoaded", function () {
    setTimeout(function () {
        var statusMessages = document.querySelectorAll('.statusM');
        if (statusMessages) {
            statusMessages.forEach(function (statusMessage) {
                statusMessage.style.display = 'none';
            });
            // Modifier l'URL sans recharger la page
            history.replaceState({}, document.title, window.location.pathname);
        }
    }, 4000);
});


function formToggle(ID) {
    var element = document.getElementById(ID);
    if (element.style.display === "none") {
        element.style.display = "block";
    } else {
        element.style.display = "none";
    }
}