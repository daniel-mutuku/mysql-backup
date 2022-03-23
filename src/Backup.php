<?php
/**
 * Created by PhpStorm.
 * User: DANIEL
 * Date: 8 Nov 2021
 * Time: 18:14
 */

namespace CodingSniper\MysqlBackup;
use PDO;
use PDOException;
class Backup
{
    protected $Db = "";
    protected $Db_user = "";
    protected $Db_pass = "";
    protected $_timezone = "";
    protected $_sql = "";
    protected $_db = "";
    protected $_tables = array();

    public function __construct($db,$user,$pass)
    {
        $this->Db = $db;
        $this->Db_user = $user;
        $this->Db_pass = $pass;
        
        $this->_gettimezone();
        $this->_setdatabase_connection();
        $this->_gettables();
        $this->_deleteexistingexport();
    }

    private function _gettimezone(){
        $target_time_zone = new \DateTimeZone(date_default_timezone_get());
        $date_time = new \DateTime('now', $target_time_zone);
        $timezone = $date_time->format('P');
        $this->_timezone = $timezone;
    }
    private function _setdatabase_connection(){
        $dsn = "mysql:host=localhost;dbname=$this->Db;charset=UTF8";
        try {
            $this->_db = new PDO($dsn, $this->Db_user, $this->Db_pass);
        } catch (PDOException $e) {

        }
    }
    private function _deleteexistingexport(){
        if (file_exists($this->Db.".sql"))
            unlink($this->Db.".sql");
    }

    private function _gettables(){
        $query = $this->_db->prepare('show tables');
        $query->execute();
        $this->_tables = $query->fetchAll(PDO::FETCH_ASSOC);
    }
    private function _gettablestructure($thistable){
        $tquery = "show create table $thistable";
        $query = $this->_db->prepare($tquery);
        $query->execute();
        $data = $query->fetch(PDO::FETCH_ASSOC);
        $table_structure = $data['Create '.array_keys($data)[0]];
        $this->_sql .= PHP_EOL."--".PHP_EOL."--table structure for table: $thistable".PHP_EOL."--".PHP_EOL;
        $this->_sql .=  $table_structure.";".PHP_EOL;
    }

    private function _gettablecolumns($thistable){
        $cquery = "SHOW COLUMNS FROM $thistable";
        $qcolumns = $this->_db->prepare($cquery);
        $qcolumns->execute();
        $datacolumns = $qcolumns->fetchAll(PDO::FETCH_ASSOC);

        return $datacolumns;
    }

    private function _getdumpeddata($thistable){
        $sqldumpdata = "SELECT * FROM ".$thistable;
        $qdumpdata = $this->_db->prepare($sqldumpdata);
        $qdumpdata->execute();
        $dumpeddata = $qdumpdata->fetchAll(PDO::FETCH_ASSOC);

        return $dumpeddata;
    }

    public function export()
    {
        $this->_sql .= '---'.PHP_EOL.'--- Database: '.$this->Db.PHP_EOL.'---'.PHP_EOL.'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";'.PHP_EOL.'START TRANSACTION;'.PHP_EOL.'SET time_zone = "'.$this->_timezone.'";';

        foreach($this->_tables as $table){
            $dumpeddata = array();
            $datacolumns = array();
            $index = "Tables_in_$this->Db";
            $thistable = $table[$index];

            $this->_gettablestructure($thistable);

            $datacolumns = $this->_gettablecolumns($thistable);

            $k = 0;
            $rowtitles="";
            foreach($datacolumns as $column){
                $rowtitles .= "`".$column['Field']."`";
                if($k < (sizeof($datacolumns)-1)){
                    $rowtitles .= ", ";
                }

                $k++;
            }

            $dumpeddata = $this->_getdumpeddata($thistable);

            $rowdata = "";
            if(!empty($dumpeddata)){
                $this->_sql .= PHP_EOL."--".PHP_EOL."--Dumping data for table: $thistable".PHP_EOL."--".PHP_EOL;

                $rowdata = "INSERT INTO `".$thistable."` (".$rowtitles.") VALUES".PHP_EOL;
                $i = 0;
                foreach($dumpeddata as $dumpeddatum){
                    $rowdata .= "(";
                    $n = 0;
                    foreach($datacolumns as $column){
                        $rowdata .= "'".str_replace("'","\'",$dumpeddatum[$column['Field']])."'";
                        if($n < sizeof($datacolumns) - 1) {
                            $rowdata .= ",";
                        }
                        $n++;
                    }
                    $rowdata .= ")";
                    if($i < sizeof($dumpeddata) - 1) {
                        $rowdata .= ",".PHP_EOL;
                    }else{
                        $rowdata .= ";". PHP_EOL;
                    }
                    $i++;
                }
                $this->_sql .= $rowdata;
            }

        }
        file_put_contents($this->Db.'.sql', $this->_sql.PHP_EOL , FILE_APPEND | LOCK_EX);

        $exportfile = $this->Db.'.sql';
        $this->_downloadexportfile($exportfile);
    }
    private function _downloadexportfile($exportfile){
        $file_contents = file_get_contents($exportfile);
        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"$exportfile\"");
        echo $file_contents;
    }
}
