// block_tareaspendientes.js
define(['jquery'], function($) {
    "use strict";

    return {
        init: function() {
            // Toggle de las tareas
            $('.block-tareaspendientes-toggle').click(function(){
                $(this).next('ul').slideToggle();
            });

            // Actualización AJAX cada 60 segundos
            setInterval(function(){
                // Obtener la URL actual y recargar solo el bloque
                $.get(window.location.href, function(data){
                    var newblock = $('#block-tareaspendientes-tasks', data).html();
                    $('#block-tareaspendientes-tasks').html(newblock);
                });
            }, 60000);
        }
    };
});

