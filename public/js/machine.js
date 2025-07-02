$(document).ready(function () {

    $(document).on('click', '.edit', function () {
        var id_machine = $(this).data('id');
        var reference = $(this).closest('tr').find('.reference').text();
        var designation = $(this).closest('tr').find('.designation').text();
        var nomType = $(this).closest('tr').find('.nomType').text();
        var brand = $(this).closest('tr').find('.brand').text();
        var numFact = $(this).closest('tr').find('.numFa').text();
        var dateFact = $(this).closest('tr').find('.dateFa').text();

        // Récupérer seulement la partie numérique du prix sans "DT"
        // var prixFactMatch = $(this).closest('tr').find('.price').text().match(/\d+/);
        // var prixFact = prixFactMatch ? prixFactMatch[0] : '';  // Si match est null, assigner une chaîne vide

        $('#modifieMach').modal('show');

        $('#id_mach').val(id_machine);
        $('#ref').val(reference);
        $('#des').val(designation);
        $('#typesM').val(nomType);
        $('#marqueMmodi').val(brand);
        $('#numFmodi').val(numFact);
        $('#dateFmodi').val(dateFact);
        // $('#prixFmodi').val(prixFact);
    });

});
