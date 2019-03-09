Ext.define('Indi.controller.example', {
    extend: 'Indi.lib.controller.Controller',
    actionsConfig: {
        index: {

        },
        form: {
            formItem$Html: {
                grow: false,
                rows: 30
            }
        }
    }
});