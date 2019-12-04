<?php

namespace Analyzer;

use Analyzer\RestApiCaller;
use Application\Services\MakeMatcher;
use Analyzer\Matcher;

class CarData {
    private static $CAR_HISTORIES_FILE = '\..\..\json\cars-histories-test.json';
    private static $JSON_ANALYSIS_FILE = '\..\..\json\prices-analysis.json';
    private static $URL_MODEL = 'https://www.carqueryapi.com/api/0.3/?cmd=getModels&make=';
    private static $URL_TRIM_PARTIAL = 'https://www.carqueryapi.com/api/0.3/?cmd=getTrims&make=';
    private static $MAKES = 'makes';
    private static $MODELS = 'models';
    private static $YEARS = 'years';
    private static $TRIMS = 'trims';
    private static $PRICES = 'prices';
    private static $KM = 'km';
    private static $RETAILER = 'retailer';
    
    public function run() {
        echo PHP_EOL . "START READING CAR HISTORIES..." . PHP_EOL;
        $data = [];
        $makes_visited = [];
        $api_caller= new RestApiCaller();
        $json = file_get_contents(dirname(__FILE__) . self::$CAR_HISTORIES_FILE);
        $lines = explode(PHP_EOL, $json);
        $c1=0;$c2=0;$c3=0;$c4=0;$c5=0;$c6=0;$c7=0;
        //Read lines of cars-histories.json
        foreach ($lines as $line) {
            $make = "";
            $model = "";
            $trim = "";
            if ($line!=""){
                echo PHP_EOL . 'Reading new record...' . PHP_EOL;
                $record = json_decode($line, true);
                print_r($record);
                if (isset($record["Marca"]) && isset($record["Immatricolazione"]) && isset($record["Modello"])
                     && isset($record["Versione"]) && isset($record["Prezzo"]) && isset($record["Km"])
                     && isset($record["Rivenditore"])) {
                    $make = $this->retriveMake($record["Marca"]);
                    if ($make!=""){
                        $ready_for_models = true;
                        $registration = $record["Immatricolazione"];
                        $year = $this->retriveYear($registration);
                        sleep(2);

                        //Check for don't visit the same Make two times to get models
                        $make_already_present = in_array($make, $makes_visited);
                        if ($make_already_present){
                            echo PHP_EOL . 'CARS MODELS FOR THIS MAKE ARE ALREADY IN CACHE' . PHP_EOL;                    
                        }
                        else {
                            echo PHP_EOL . 'Getting models...' . PHP_EOL;  
                            $json_model = $api_caller->startRequest(self::$URL_MODEL . $make);
                            array_push($makes_visited, $make);
                            $model_obj = json_decode($json_model, true);
                            $models = $model_obj['Models'];
                            if (empty($models)) {
                                $ready_for_models = false;
                                echo PHP_EOL . 'ERROR: MODELS IS EMPTY, PLEASE CHECK THE URL OF THE API CALLED' . PHP_EOL;
                            } else {
                                $models_names = array();
                                foreach ($models as $model) {
                                    $mod =  $model['model_name'];
                                    $models_names[$mod] = array();
                                }
                                $data[self::$MAKES][$make][self::$MODELS] = $models_names;
                            }                        
                        }
                        sleep(2);

                        if ($ready_for_models) {
                            //Getting dictionary of models of a make before try to matching
                            $all_models_make = [];
                            foreach ($data[self::$MAKES][$make][self::$MODELS] as $key => $value) {
                                array_push($all_models_make, $key);
                            }
                            $model = $this->retriveItem($record["Modello"], $all_models_make);
                            
                            if ($model!=""){
                                $ready_for_trims = true;
                                //Check for don't visit the same model two times to get trims
                                if (!empty($data[self::$MAKES][$make][self::$MODELS][$model][self::$YEARS][$year])){
                                    echo PHP_EOL . 'CARS TRIM FOR THIS MODEL-YEAR ARE ALREADY IN CACHE' . PHP_EOL;                    
                                }
                                else {
                                    //If trim is not present check it in the previous year (because year of registration >= year of production)
                                    for($i=$year; $i>=$year-1; $i--){
                                        sleep(1);
                                        $json_trim = $api_caller->startRequest(self::$URL_TRIM_PARTIAL . $make . '&year=' . $i . '&model=' . $model);
                                        $trim_obj = json_decode($json_trim, true);
                                        $trims = $trim_obj['Trims'];
                                        if (empty($trims)) {
                                            $ready_for_trims = false;
                                            echo PHP_EOL . 'ERROR: TRIMS IS EMPTY, PLEASE CHECK THE URL OF THE API CALLED, attempt WITH YEAR:' . $i . PHP_EOL;
                                        } else {
                                            $year = $i;
                                            $trims_array = [];
                                            foreach ($trims as $trim) {
                                                $model_trim =  $trim['model_trim'];
                                                //Remove model_trim without a name
                                                if ($model_trim!='') {
                                                    $trims_array[$model_trim] = array();
                                                }
                                            }
                                            //Check when trims has only one model_trim but you remove it because is without a name
                                            if (empty($trims_array)) {
                                                $ready_for_trims = false;
                                            } else{
                                                $data[self::$MAKES][$make][self::$MODELS][$model][self::$YEARS][$year][self::$TRIMS] = $trims_array;
                                            }
                                            //Match, so no need to search in past year
                                            break;
                                        }
                                    }
                                }
                                
                                if ($ready_for_trims) {
                                    //Getting dictionary of trims of a model before try to matching
                                    $all_trims_model = [];
                                    foreach ($data[self::$MAKES][$make][self::$MODELS][$model][self::$YEARS][$year][self::$TRIMS] as $key => $value) {
                                        array_push($all_trims_model, $key);
                                    }
                                    $trim = $this->retriveItem($record["Versione"], $all_trims_model);
                                    
                                    $price = $record["Prezzo"];
                                    $km = $this->retriveKM($record["Km"]);
                                    $retailer = $record["Rivenditore"];
                                    $price_km_retailer = array(self::$PRICES => $price, self::$KM => $km, self::$RETAILER => $retailer);
                                    if ($trim!=""){
                                        //Add the price
                                        $prices = [];
                                        if (isset($data[self::$MAKES][$make][self::$MODELS][$model][self::$YEARS][$year][self::$TRIMS][$trim])){
                                            $prices = $data[self::$MAKES][$make][self::$MODELS][$model][self::$YEARS][$year][self::$TRIMS][$trim];
                                        }
                                        $prices[] = $price_km_retailer;
                                        $data[self::$MAKES][$make][self::$MODELS][$model][self::$YEARS][$year][self::$TRIMS][$trim] = $prices;
                                        echo PHP_EOL . 'PRICE ADDED' . PHP_EOL;
                                        $c7++;
                                    } else {
                                        echo PHP_EOL . 'No trim match, Record skipped...' . PHP_EOL;
                                        $c6++;
                                    }
                                } else {
                                    echo PHP_EOL . 'ERROR: TRIMS IS EMPTY, Record skipped...' . PHP_EOL;
                                    $c5++;                                
                                }
                            } else {
                                echo PHP_EOL . 'No model match, Record skipped...' . PHP_EOL;
                                $c4++;
                            }
                        } else {
                            echo PHP_EOL . 'ERROR: MODELS IS EMPTY, Record skipped...' . PHP_EOL;
                            $c3++;
                        }
                    } else {
                        echo PHP_EOL . 'No make match, Record skipped...' . PHP_EOL;
                        $c2++;
                    }
                } else {
                    echo PHP_EOL . 'Some field in this record is missing, Record skipped...' . PHP_EOL;
                    $c1++;
                }
            } else {
                echo PHP_EOL . "Empy line, start to reading next" . PHP_EOL;
            }
        }
        if (!empty($data)) {
            $data = json_encode($data, JSON_PRETTY_PRINT);
            file_put_contents(dirname(__FILE__) . self::$JSON_ANALYSIS_FILE, $data);
        }
        echo PHP_EOL ."Record skipped for:" . PHP_EOL;
        echo "-field missing: " . $c1. PHP_EOL;
        echo "-no make match: " . $c2. PHP_EOL;
        echo "-models empty API Fault: " . $c3. PHP_EOL;
        echo "-no model match: " . $c4. PHP_EOL;
        echo "-trims empty API Fault: " . $c5. PHP_EOL;
        echo "-no trim match: " . $c6. PHP_EOL;
        echo PHP_EOL ."Prices added: " . $c7. PHP_EOL;
        
        //return ;
    }

    private function retriveMake($make){
        $makeMatcher = new MakeMatcher();
        $make_found = $makeMatcher->run($make);
        return $make_found;
    }

    private function retriveYear($registration){
        $year = $registration;
        $findme = '/';
        //e.g month/year
        if (strpos($registration, $findme) !== false){
            $registration = explode($findme, $registration);
            $year = $registration[1];
        }
        return $year;
    }

    private function retriveItem($item, $all_items){
        $matcher = new Matcher();
        $item_found = $matcher->run($item, $all_items);
        return $item_found;
    }
    
    private function retriveKM($km){
        $number_km = $km;
        $findme = ' ';
        //e.g '12000 km'
        if (strpos($km, $findme) !== false){
            $km = explode($findme, $km);
            $number_km = $km[0];
        }
        return $number_km;
    }    
}
