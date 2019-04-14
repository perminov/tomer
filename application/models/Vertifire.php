<?php
class Vertifire extends Indi_Db_Table {

    /**
     * Classname for row
     *
     * @var string
     */
    public $_rowClass = 'Vertifire_Row';

    /**
     * Array containing props for each result type
     *
     * @var array
     */
    public static $props = [
        'organic' =>           ['display_url,title,description', 'url'],
        'video' =>             ['display_url,title,description', 'url'],
        'ad_top' =>            ['display_url,title,description', 'url'],
        'ad_bottom' =>         ['display_url,title,description', 'url'],
        'related' =>           ['title',                       'title'],
        'snack_pack' =>        ['url,title,description',         'cid'],
        'top_stories' =>       ['display_url,title,description', 'url'],
        'featured_snippet' =>  ['display_url,title,description', 'url']
    ];
}