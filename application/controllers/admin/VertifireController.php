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

        /*Array(
            [organic] => 0
            [video] => 1
            [related] => 2
            [ad_top] => 0
            [snack_pack] => 1
            [ad_bottom] => 3
            [pla_top] => 0
            [featured_snippet] => 0
        )*/

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

        // Colors
        $color = ['red', 'blue', 'lime'];

        // Clickable value wrapper template
        $tpl = '<a load="/vertifire/view/id/%s/?type=%s&mode=%s&prop=%s" style="color: %s;">';

        // Foreach data item
        foreach ($data as &$item) {

            // Foreach result type
            foreach (['organic', 'video', 'ad_top', 'ad_bottom'] as $type) {

                // Check whether such result type detected within data item
                $zero = !$item[$type . 'Qty_old'] && !$item[$type . 'Qty_new'];

                // Foreach prop
                foreach (['display_url','title','description'] as $prop) {

                    // Shortcut
                    $_ = &$item[$type . '_' . $prop];

                    // If zero - hide values, else colorize
                    if ($zero) $_ = ''; else {

                        // Explode
                        $_ = explode(' / ', $_);

                        // Wrap
                        foreach ($_ as $i => $v) {
                            $_[$i] = wrap($_[$i], sprintf($tpl, $item['id'], $type, $i, $prop, $color[$i]));
                            $_[$i] = wrap($_[$i], '<span style="color: lightgray;">', !$_[$i]);
                        }

                        // Implode back
                        $_ = im($_, '<span style="color: lightgray;"> / </span>');
                    }
                }
            }
        }
    }

    /**
     * View html source, or view diff
     */
    public function viewAction() {

        // If $_GET['type'] is 'html' - show html source
        if (!Indi::get()->type) die(file_get_contents($this->row->html_link));

        // Get diff
        $diff = $this->row->compare(Indi::get()->type, Indi::get()->mode, Indi::get()->prop);

        // Flush diff
        jtextarea(true, print_r($diff, true));
    }
    
    /**
     * Show html source within textarea
     */
    public function sourceAction () {
    
        // Flush source
        jtextarea(true, file_get_contents($this->row->html_link));
    }
}