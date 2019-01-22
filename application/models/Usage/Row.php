<?php
class Usage_Row extends Indi_Db_Table_Row {

    /**
     * @inheritdoc
     */
    public function onBeforeSave(){

        // Build title
        $this->title = $this->foreign('ipId')->title . ':' . $this->foreign('portId')->title;
    }
}