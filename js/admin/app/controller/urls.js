Ext.define('Indi.controller.urls', {
    extend: 'Indi.lib.controller.Controller',
    actionsConfig: {
        form: {
            formItem$OgImage: {
                lbarItems: [{
                    iconCls: 'i-btn-icon-goto',
                    tooltip: {html: 'Open image in a new tab', constrainParent: false},
                    handler: function(c) {
                        window.open(c.target.val());
                    }
                }]
            }
        }
    }
});