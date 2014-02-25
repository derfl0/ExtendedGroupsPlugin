STUDIP.extendedGroups = {
    maxMembers: 250,
    init: function() {
        if (this.entries() > this.maxMembers) {
            this.flattenAll();
        }
    },
    entries: function() {
        return $('.inflatable tbody tr').length; 
    },
    flattenAll: function() {
        $('.inflatable tbody').hide(0);
        $('.inflatable thead').click(function(){
            $(this).closest('.inflatable').find('tbody').toggle(200);
        });
    }
}

$(document).ready(function() {
    STUDIP.extendedGroups.init();
});