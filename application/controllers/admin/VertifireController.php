<?php
class Admin_VertifireController extends Indi_Controller_Admin {

    /**
     * Bulk parse
     */
    public function parseAction() {

        // Parse selected
        foreach ($this->selected as $r) $r->parse();

        // Flush success
        jflush(true, 'Parsed. Ready to be compared.');
    }

    /**
     * Bulk diff
     */
    public function compareAction() {

        // Detect diff stats for selected
        foreach ($this->selected as $r) $r->compare();

        // Flush success
        jflush(true, 'Done');
    }
}