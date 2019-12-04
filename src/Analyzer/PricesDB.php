<?php

namespace Analyzer;

use \PDO;

class PricesDB {
    private static $CONFIG_INI = '\..\..\config\config.ini';
    private static $ANALYSIS_FILE = '\..\..\json\prices-analysis.json';

    public function run(){
        $ini = parse_ini_file(dirname(__FILE__).self::$CONFIG_INI);
        $servername = $ini['db_servername'];
        $db_name = $ini['db_name'];
        $user = $ini['db_user'];
        $password = $ini['db_password'];
        $table = 'Prices';

        try {
            $conn = new PDO("mysql:host=$servername;dbname=$db_name", $user, $password);
            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "Connected successfully";
            
            // $sql = "DROP TABLE " . $table;
            // sql to create table
            $sql = "CREATE TABLE IF NOT EXISTS " . $table . " (
                makes VARCHAR(30),
                models VARCHAR(50),
                years INT(6),
                trims VARCHAR(90),
                prices INT(9),
                km INT(9),
                retailer VARCHAR(5)
                )";
            // use exec() because no results are returned
            $conn->exec($sql);
            echo PHP_EOL . "Table ". $table . " created successfully";
            
            $json = file_get_contents(dirname(__FILE__) . self::$ANALYSIS_FILE);
            //convert json object to php associative array
            $data = json_decode($json, true);
            
            // begin the transaction
            $conn->beginTransaction();
            $tmp = [];
            $i=1;
            foreach($data['makes'] as $key_make=>$value_make){
                foreach ($value_make['models'] as $key_model=>$value_model) {
                    if(!empty($value_model)){
                        foreach ($value_model['years'] as $key_year=>$value_year) {
                            foreach ($value_year['trims'] as $key_trim=>$value_trim) {
                                if(!empty($value_trim)){
                                    foreach ($value_trim as $key=>$value) {
                                        echo PHP_EOL . 'riga' .$i++ . " -> " .$key_make.  " - ".
                                        $key_model." - ". $key_year . " - ". $key_trim.  " - " . 
                                        $value['prices'].  " - " . $value['km'].  " - " . $value['retailer']. PHP_EOL;
                                        $conn->exec("INSERT INTO ".$table.
                                        " (makes, models, years, trims, prices, km, retailer) VALUES ('".
                                        $key_make."', '".$key_model."', '".$key_year."', '".$key_trim."', '".
                                        $value['prices']."', '".$value['km']."', '".$value['retailer']."')");
                                    }
                                }
                            }
                        }
                    }
                }
            }
            // commit the transaction
            $conn->commit();
            echo "New records created successfully";
        } catch(PDOException $e) {
            // roll back the transaction if something failed
            $conn->rollback();
            echo $sql . "<br>" . $e->getMessage();
        }
    }
}
