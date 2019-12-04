<?php

namespace Analyzer;

use Application\Services\Levenshtein;
use Application\Services\Tokenizer;

class Matcher {
    private static $PERCENT_THRESHOLD = 0.55;

    /**
     * Try to match a input in a dictionary
     *
     * @param string $input the input
     * @param array $dictionary the dictionary
     * @return string|null the closest match
     */
    public function run($input, $dictionary) {
        $output = null;
        echo PHP_EOL . "START RETRIVE: " . $input . PHP_EOL;
        //Set dictionary in Levenshtein
        $levenshtein = new Levenshtein();
        $levenshtein->setDictionary($dictionary);
        $levenshtein->setCaseInsensitive(true);

        $tokens = $this->tokenization($input);
        //Run matching
        $results = $this->applyMatch($levenshtein, $tokens);

        //Check if has match
        if ($results != null) {
            print_r($results);
             //Search best result
             $best_result = ['', '', 0];
             foreach ($results as $result) {
                $closest = $result[0];
                $num_different_characters = $result[1];
                $percent = $result[2];
                if ($percent > self::$PERCENT_THRESHOLD && $percent > $best_result[2]) {
                    $best_result = [$closest, $num_different_characters, $percent];
                }
            }
            if ($best_result[2] > 0) {
                print_r($best_result);
                $output = $best_result[0];
                echo PHP_EOL . "FINISH, RETRIVED: $output" . PHP_EOL;
            } else {
                echo PHP_EOL . "FINISH, SORRY MATCH PERCENT IS LESS THAN OUR THRESHOLD PERCENT" . PHP_EOL;
            }
        } else {
            echo PHP_EOL . "FINISH, SORRY NO MATCH FOUND" . PHP_EOL;
        }            
        return $output;
    }

    /**
     * Divide input into token
     *
     * @param string $txt the text
     * @return array tokens with at least 1 char
     */
    private function tokenization($txt) {
        $tokenizer = new Tokenizer();
        $regex = "/[\s(),-]+/";
        $tokens = $tokenizer->run($regex, $txt);
        $tokens = $tokenizer->filterByLength($tokens, 2);
        return $tokens;
    }

    /**
     * Apply Levenshtein matching
     *
     * @param Levenshtein $levenshtein the Levenshtein algorithm
     * @param string $input the input
     * @return array the results of Levenshtein algorithm
     */
    private function applyMatch($levenshtein, $tokens) {
        $results = null;
        foreach ($tokens as $token) {
            $levenshtein->setInput($token);
            $result = $levenshtein->run();
            $levenshtein->printResult($result);
            $results[] = $result;
        }
        return $results;
    }
}
