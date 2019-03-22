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

    /**
     * Colorize
     *
     * @param array $data
     */
    public function adjustGridData(&$data) {
        foreach ($data as &$item) {
            foreach (['display_url','title','description'] as $prop) {
                $_ = &$item['organic_' . $prop]; $_ = explode(' / ', $_);
                $_[0] = '<span style="color: ' . ($_[0] ? 'red'  : 'lightgray') . ';">' . $_[0].'</span>';
                $_[2] = '<span style="color: ' . ($_[2] ? 'lime' : 'lightgray') . ';">' . $_[2].'</span>';
                $_[1] = '<span style="color: ' . ($_[1] ? 'blue' : 'lightgray') . ';">' . $_[1].'</span>';
                $_ = im($_, '<span style="color: lightgray;"> / </span>');
            }
        }
    }
}