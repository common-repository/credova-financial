jQuery(document).ready(function($) {
    var public_id = credovaData.publicId;
    var redirect_url = credovaData.redirect;
    console.log(public_id);
    console.log(redirect_url);
    if (public_id) {
        CRDV.plugin.checkout(public_id).then(function(completed) {
            if (completed) {
                window.location.href = window.location.href.slice(window.location.href.indexOf('_url=') + 5);
            } else {
                console.log("cancelled");
                window.location.href = window.location.href.slice(window.location.href.indexOf('_url=') + 5) + '&status=cancelled';
            }
        });
    }
});