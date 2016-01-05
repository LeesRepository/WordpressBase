
window.hmAjax = false;
window.guid = function() {
    function s4() {
        return Math.floor((1 + Math.random()) * 0x10000)
            .toString(16)
            .substring(1);
    }

    return s4() + s4() + '-' + s4() + '-' + s4() + '-' +
        s4() + '-' + s4() + s4() + s4();
};

(function ($) {

    $(document).ready(function () {

        window.generateGUID = function() {

            $('#this_key').val(guid());

        }

        window.testConnection = function() {

            sendAjax( 'test_connection' );

        }

        window.sendDatabase = function() {

            sendAjax( 'send_database' );

        }


        window.importDatabase = function() {

            sendAjax( 'import_database' );

        }



        window.sendAjax = function( command, thisData ) {

            if( window.hmAjax === true ) {
                notification( "Ajax Call currently running, please wait.", 'warning');
            }

            if( typeof command == "undefined" ) {
                notification( "command not defined for ajax call", 'warning');
            }

            if( typeof thisData == "undefined" ) {
                thisData = {};
            }

            thisData.action = command;



            window.hmAjax = true;

            $.ajax({
                type: "POST",
                url: ajaxurl,
                data: thisData,
                success: function(data, textStatus, jqXHR) {

                    window.hmAjax = false;

                    data = JSON.parse(data);

                    if( data.success === true ) {

                        notification( data.message );

                    } else {
                        notification( data.message, 'warning')
                    }


                },
                failure: function() {
                    window.hmAjax = false;
                }
            });

        }





        // EVENT BINDINGS:

        $('#generate_key').on('click', generateGUID);

        $('#test_connection').on('click', testConnection);

        $('#ajax_send_database').on('click', sendDatabase)

        $('#ajax_import_database').on('click', importDatabase)



    })

})(jQuery)