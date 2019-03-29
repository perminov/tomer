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
            gridColumn$Title_Renderer: function(v, s, r) {
                return '<a href="' + Indi.pre + '/vertifire/view/id/'+ r.get('id')+'/" target="_blank">' + v + '</a>';
            },
            gridColumn$OrganicQty_old_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=organic&mode=old">' + v + '</a>' : '<span style="color: lightgray;">' + v + '</span>';
            },
            gridColumn$Organic0_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=organic&mode=0" style="color: red;">' + v + '</a>': '<span style="color: lightgray;">' + v + '</span>';
            },
            gridColumn$OrganicQty_new_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=organic&mode=new">' + v + '</a>' : '<span style="color: lightgray;">' + v + '</span>';
            },
            gridColumn$Organic1_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=organic&mode=1" style="color: lime;">' + v + '</a>': '<span style="color: lightgray;">' + v + '</span>';
            },

            gridColumn$VideoQty_old_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=video&mode=old">' + v + '</a>' : '<span style="color: lightgray;">' + v + '</span>';
            },
            gridColumn$Video0_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=video&mode=0" style="color: red;">' + v + '</a>': '<span style="color: lightgray;">' + v + '</span>';
            },
            gridColumn$VideoQty_new_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=video&mode=new">' + v + '</a>' : '<span style="color: lightgray;">' + v + '</span>';
            },
            gridColumn$Video1_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=video&mode=1" style="color: lime;">' + v + '</a>': '<span style="color: lightgray;">' + v + '</span>';
            },

            gridColumn$Ad_topQty_old_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=ad_top&mode=old">' + v + '</a>' : '<span style="color: lightgray;">' + v + '</span>';
            },
            gridColumn$Ad_top0_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=ad_top&mode=0" style="color: red;">' + v + '</a>': '<span style="color: lightgray;">' + v + '</span>';
            },
            gridColumn$Ad_topQty_new_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=ad_top&mode=new">' + v + '</a>' : '<span style="color: lightgray;">' + v + '</span>';
            },
            gridColumn$Ad_top1_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=ad_top&mode=1" style="color: lime;">' + v + '</a>': '<span style="color: lightgray;">' + v + '</span>';
            },

            gridColumn$Ad_bottomQty_old_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=ad_bottom&mode=old">' + v + '</a>' : '<span style="color: lightgray;">' + v + '</span>';
            },
            gridColumn$Ad_bottom0_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=ad_bottom&mode=0" style="color: red;">' + v + '</a>': '<span style="color: lightgray;">' + v + '</span>';
            },
            gridColumn$Ad_bottomQty_new_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=ad_bottom&mode=new">' + v + '</a>' : '<span style="color: lightgray;">' + v + '</span>';
            },
            gridColumn$Ad_bottom1_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=ad_bottom&mode=1" style="color: lime;">' + v + '</a>': '<span style="color: lightgray;">' + v + '</span>';
            }
        },
        form: {
            formItem$Html_link: {
                lbarItems: [{
                    iconCls: 'i-btn-icon-goto',
                    tooltip: {html: 'Open in a new tab', constrainParent: false},
                    handler: function(c) {
                        window.open(c.target.val());
                    }
                }]
            },
            formItem$Results: {
                height: 150
            },
            formItem$New_results: {
                height: 150
            }
        }
    }
});