<?php
class Admin_CsvController extends Indi_Controller_Admin {
    public function importAction() {
        
        $csv = DOC . STD . '/www/data/import/' . 'IT  SE Parent Dept_Test File_10.14.2020.csv';
        
        Indi::db()->query('TRUNCATE `csv`');
        
        Indi::db()->query('LOAD DATA INFILE "' . $csv . '" INTO TABLE `csv` FIELDS TERMINATED BY ","  IGNORE 1 LINES;');
        
        jflush(false, file_exists($csv) ? 'asd' : 'qwe');
    }
}