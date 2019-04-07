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
            },

            gridColumn$RelatedQty_old_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=related&mode=old">' + v + '</a>' : '<span style="color: lightgray;">' + v + '</span>';
            },
            gridColumn$Related0_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=related&mode=0" style="color: red;">' + v + '</a>': '<span style="color: lightgray;">' + v + '</span>';
            },
            gridColumn$RelatedQty_new_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=related&mode=new">' + v + '</a>' : '<span style="color: lightgray;">' + v + '</span>';
            },
            gridColumn$Related1_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=related&mode=1" style="color: lime;">' + v + '</a>': '<span style="color: lightgray;">' + v + '</span>';
            },

            gridColumn$Snack_packQty_old_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=snack_pack&mode=old">' + v + '</a>' : '<span style="color: lightgray;">' + v + '</span>';
            },
            gridColumn$Snack_pack0_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=snack_pack&mode=0" style="color: red;">' + v + '</a>': '<span style="color: lightgray;">' + v + '</span>';
            },
            gridColumn$Snack_packQty_new_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=snack_pack&mode=new">' + v + '</a>' : '<span style="color: lightgray;">' + v + '</span>';
            },
            gridColumn$Snack_pack1_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=snack_pack&mode=1" style="color: lime;">' + v + '</a>': '<span style="color: lightgray;">' + v + '</span>';
            },

            gridColumn$Top_storiesQty_old_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=top_stories&mode=old">' + v + '</a>' : '<span style="color: lightgray;">' + v + '</span>';
            },
            gridColumn$Top_stories0_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=top_stories&mode=0" style="color: red;">' + v + '</a>': '<span style="color: lightgray;">' + v + '</span>';
            },
            gridColumn$Top_storiesQty_new_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=top_stories&mode=new">' + v + '</a>' : '<span style="color: lightgray;">' + v + '</span>';
            },
            gridColumn$Top_stories1_Renderer: function(v, s, r) {
                return v ? '<a load="/vertifire/view/id/'+ r.get('id')+'/?type=top_stories&mode=1" style="color: lime;">' + v + '</a>': '<span style="color: lightgray;">' + v + '</span>';
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