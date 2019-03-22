Ext.define('Indi.controller.vertifire', {
    extend: 'Indi.lib.controller.Controller',
    actionsConfig: {
        index: {
            rowset: {
                multiSelect: true
            },
            gridColumn$Organic0_Renderer: function(v) {
                return '<span style="color: '+ (v ? 'red' : 'lightgray') + ';">' + v + '</span>';
            },
            gridColumn$Organic1_Renderer: function(v) {
                return '<span style="color: '+ (v ? 'lime' : 'lightgray') + ';">' + v + '</span>';
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