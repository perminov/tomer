<?php
class Admin_VertifireController extends Indi_Controller_Admin {

    /**
     * Bulk parse
     */
    public function parseAction() {

        // Html source file download counters
        $stat = ['already' => 0, 'error' => 0, 'new' => 0];
        
        // Foreach selected row - try to download
        foreach ($this->selected as $r)
            if ($r->src('source')) $stat['already'] ++;
            else if (!$raw = file_get_contents($r->html_link)) $stat['error'] ++;
            else $r->file('source', 'html', $raw);

        // Parsed rows counter
        $parsedQty = 0;

        /*Array(
            [related] => 2
            [snack_pack] => 1
            [featured_snippet] => 0
        )*/

        // Foreach selected row
        foreach ($this->selected as $r) {

            // If can't be parsed - skip
            if (!$r->parse()) continue;

            // Compare results and calc diff
            $r->compare();

            // Increase counter
            $parsedQty ++;
        }

        // Flush download stat
        jflush(true, 'HTML source-files download stats: ' . json_encode($stat) . '. Parsed: ' . $parsedQty);        
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
            foreach (Vertifire::$props as $type => $props) {

                // Check whether such result type detected within data item
                $zero = !$item[$type . 'Qty_old'] && !$item[$type . 'Qty_new'];

                // Foreach prop
                foreach (ar($props) as $prop) {

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
        if (!Indi::get()->type) {

            // If no source-file - flush error
            if (!$abs = $this->row->abs('source')) jflush(false, 'No file with html source');

            // Flush source
            die(file_get_contents($abs));
        }

        // Get diff
        $diff = $this->row->compare(Indi::get()->type, Indi::get()->mode, Indi::get()->prop);

        // Flush diff
        jtextarea(true, print_r($diff, true));
    }
    
    /**
     * Show html source within textarea
     */
    public function sourceAction () {
    
        // If no source-file - flush error
        if (!$abs = $this->row->abs('source')) jflush(false, 'No file with html source');
    
        // Flush source
        jtextarea(true, file_get_contents($abs));
    }
}