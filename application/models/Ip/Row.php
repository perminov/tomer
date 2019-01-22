<?php
class Ip_Row extends Indi_Db_Table_Row {

    /**
     * @inheritdoc
     */
    public function validate() {

        // Check `title` prop, for it to be required, integer and unique
        $this->mcheck([
            'title' => [
                'req' => true,
                'rex' => 'ipv4',
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

        // Foreach `port` entry
        foreach (Indi::model('Port')->fetchAll() as $portR)

            // Create new `usage` entry
            Indi::model('Usage')->createRow([
                'ipId' => $this->id,
                'portId' => $portR->id
            ], true)->save();
    }

    /**
     * @inheritdoc
     */
    public function onUpdate() {

        // Get current value of `toggle`
        $toggle = $this->toggle;

        // Batch update `toggle` prop for each `usage` entry, linked to current `ip` entry
        Indi::model('Usage')->batch(function($r) use ($toggle) {
            $r->assign(['toggle' => $toggle])->save();
        }, '`ipId` = "' . $this->id . '"');
    }
}