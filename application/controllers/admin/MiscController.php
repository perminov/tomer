<?php
class Admin_MiscController extends Indi_Controller {

    /**
     * Create `port` entries
     */
    public function portsAction() {

        // Get already existing `port` entries
        $already = Indi::db()->query('SELECT `title`, 1 FROM `port`')->fetchAll(PDO::FETCH_KEY_PAIR);

        // Foreach port number within given range
        for ($i = 10005; $i <= 12000; $i++) {

            // If such port already exists - skip
            if ($already[$i]) continue;

            // Create `port` entry
            Indi::model('Port')->createRow(['title' => $i], true)->save();
        }

        // Flush success
        jflush(true);
    }

    /**
     * Import Ips from text file
     */
    public function ipv4Action() {

        // Get existing `ip` entries
        $already = Indi::db()->query('SELECT `title`, 1 FROM `ip`')->fetchAll(PDO::FETCH_KEY_PAIR);

        // Absolute path to txt-file, containing list of IPs
        $abs = DOC . STD . '/www/data/import/ipv4.txt';

        // Check that file exists
        if (!file_exists($abs)) jflush(false, 'File ' . $abs . ' - does not exists');

        // Get contents of text file
        $txt = file_get_contents($abs);

        // Counters
        $invalid = $existing = $imported = 0;

        // Foreach line
        foreach (explode("\n", $txt) as $total => $line) {

            // Trim
            $line = trim($line);

            // If line is not ipv4 - skip
            if ((!$ipv4 = Indi::rexm('ipv4', $line, 0)) && (++ $invalid)) continue;

            // If such IP already exists - skip
            if ($already[$ipv4] && (++ $existing)) continue;

            // Create `IP` entry
            Indi::model('Ip')->createRow(['title' => $ipv4], true)->save();

            // Importer counter
            $imported ++;
        }

        // Flush success
        jflush(true, [
            'processed' => $total + 1,
            'imported' => $imported,
            'invalid' => $invalid,
            'existing' => $existing
        ]);
    }
}