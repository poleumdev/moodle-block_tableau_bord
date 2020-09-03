/**
 * Call server script ajaxsrvmethod and handle
 * the return.
 *
 * @param @e javascript event or null.
 * @param @args array parameters contents userid, courseorder (course s id separate by comma).
 */
function ajax_update_courseorder(e, args) {
    var launchid = -1;
    var trainingid = -1;
    var categoryid = -1;
    if (e instanceof Event) {
        e.preventDefault();
    } else {
        userid = args[0];
        courseorder = args[1];
    }

    var ioconfig = {
        method: 'POST',
        data: {'sesskey': M.cfg.sesskey, 'userid': userid.toString(),
            'courseorder': courseorder.toString()},
        on: {
            success: function(o, response) {
                var data = Y.JSON.parse(response.responseText);
                console.log('Retour ajax success  ' + data.state);
            },
            failure: function(o, response) {
                console.log('Retour ajax failure ' + response.toSource());
            }
        }
    };

    Y.io(M.cfg.wwwroot + '/blocks/tableau_bord/js/ajaxsrvmethod.php', ioconfig);
}

function ajax_delete_notif(e, args) {
    if (e instanceof Event) {
        e.preventDefault();
    } else {
        id_user = args[0];
        id_activite = args[1];
    }

    var ioconfig = {
        method: 'POST',
        data: {'sesskey': M.cfg.sesskey, 'id_user': id_user.toString(),
            'id_activite': id_activite.toString()},
        on: {
            success: function(o, response) {
                var data = Y.JSON.parse(response.responseText);
                console.log('Retour ajax success  ' + data.state);
            },
            failure: function(o, response) {
                console.log('Retour ajax failure ' + response.toSource());
            }
        }
    };

    Y.io(M.cfg.wwwroot + '/blocks/tableau_bord/js/ajaxsuppression_notif.php', ioconfig);
}