<?php
class Port_Row extends Indi_Db_Table_Row {

    /**
     * @inheritdoc
     */
    public function validate() {

        // Check `title` prop, for it to be required, integer and unique
        $this->mcheck([
            'title' => [
                'req' => true,
                'rex' => 'int11',
                'unq' => true
            ]
        ]);

        // Call parent
        return $this->callParent();
    }

    /**
     * @inheritdoc
     */
    public function onInsert() {

        // Foreach `ip` entry
        foreach (Indi::model('Ip')->fetchAll() as $ipR)

            // Create new `usage` entry
            Indi::model('Usage')->createRow([
                'ipId' => $ipR->id,
                'portId' => $this->id
            ], true)->save();
    }
}