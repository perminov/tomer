Ext.define('Indi.controller.vertifire', {
    extend: 'Indi.lib.controller.Controller',
    actionsConfig: {
        index: {
            rowset: {
                multiSelect: true
            }
        },
        form: {
            formItem$Results: {
                height: 150
            },
            formItem$New_results: {
                height: 150
            }
        }
    }
});