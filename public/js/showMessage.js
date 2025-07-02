function showMessage(type, message) {
    // Vérifier si le conteneur de notifications existe, sinon le créer
    let container = $('#notification-container');
    if (container.length === 0) {
        container = $('<div id="notification-container" class="position-fixed w-100" style="z-index: 500; top: 0; left: 20px"></div>');
        $('body').append(container);
    }

    // Déterminer la classe d'alerte en fonction du type
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const iconClass = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';


    const notification = `
<div class="alert ${alertClass} alert-dismissible fade show mb-0" role="alert" style="background-color:white; width:1000px; margin-left:480px; height:60px; margin-top:20px;">
<i class="${iconClass}" style="margin-right: 5px;"></i> ${message}
<button type="button" class="close" data-dismiss="alert" aria-label="Close">
<span aria-hidden="true">&times;</span>
</button>
</div>
`;
    container.append(notification);
    setTimeout(function () {
        container.empty();
    }, 5000);
    const urlWithoutParam = window.location.href.split('?')[0];
    window.history.replaceState({}, document.title, urlWithoutParam);
}