Ext.define('Indi.controller.vertifire', {
    extend: 'Indi.lib.controller.Controller',
    actionsConfig: {
        index: {
            rowset: {
                multiSelect: true
            },
            gridColumn$Title: {
                minWidth: 300
            },
            gridColumn$Organic0_Renderer: function(v) {
                return '<span style="color: '+ (v ? 'red' : 'lightgray') + ';">' + v + '</span>';
            },
            gridColumn$Organic1_Renderer: function(v) {
                return '<span style="color: '+ (v ? 'lime' : 'lightgray') + ';">' + v + '</span>';
            },
            gridColumn$Video0_Renderer: function(v) {
                return '<span style="color: '+ (v ? 'red' : 'lightgray') + ';">' + v + '</span>';
            },
            gridColumn$Video1_Renderer: function(v) {
                return '<span style="color: '+ (v ? 'lime' : 'lightgray') + ';">' + v + '</span>';
            },
            gridColumn$Ad_top0_Renderer: function(v) {
                return '<span style="color: '+ (v ? 'red' : 'lightgray') + ';">' + v + '</span>';
            },
            gridColumn$Ad_top1_Renderer: function(v) {
                return '<span style="color: '+ (v ? 'lime' : 'lightgray') + ';">' + v + '</span>';
            },
            gridColumn$Ad_bottom0_Renderer: function(v) {
                return '<span style="color: '+ (v ? 'red' : 'lightgray') + ';">' + v + '</span>';
            },
            gridColumn$Ad_bottom1_Renderer: function(v) {
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