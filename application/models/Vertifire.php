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
        'organic' => 'display_url,title,description',
        'video' => 'display_url,title,description',
        'ad_top' => 'display_url,title,description',
        'ad_bottom' => 'display_url,title,description',
        'related' => 'title',
        'snack_pack' => 'cid,title,description'
    ];
}