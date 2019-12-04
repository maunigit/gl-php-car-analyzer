<?php

namespace Analyzer;

use PHPUnit\Framework\TestCase;
use Analyzer\PricesDB;

final class PricesDBTest extends TestCase {
    
    public function testRun(): void {
        echo PHP_EOL . "testRun" . PHP_EOL;
        $prices = new PricesDB();
        $result= $prices->run();
        //$this->assertEquals('X', $result);
    }    
}