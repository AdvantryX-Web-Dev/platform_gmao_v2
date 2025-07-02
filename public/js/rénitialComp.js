(function($) {
    //Lorsqu'activé, le mode strict impose un ensemble de règles plus strictes sur le code JavaScript et génère des erreurs pour certaines pratiques jugées comme non recommandées ou susceptibles de provoquer des erreurs silencieuses.
    "use strict";

    $(".toggle-confirm-password").click(function() {
        //Cela est souvent utilisé avec des bibliothèques d'icônes comme Font Awesome pour changer l'icône d'un œil ouvert à un œil fermé, symbolisant la visibilité du mot de passe.
        $(this).toggleClass("fa-eye fa-eye-slash");
        //Récupère la valeur de l'attribut "toggle" de l'élément actuel (qui est censé être un sélecteur jQuery) et crée un objet jQuery avec cette valeur.
        var input = $($(this).attr("toggle"));
        if (input.attr("type") == "password") {
            input.attr("type", "text");
        } else {
            input.attr("type", "password");
        }
    });

})(jQuery);