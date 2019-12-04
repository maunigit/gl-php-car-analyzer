<?php

namespace Analyzer;

use PHPUnit\Framework\TestCase;
use Analyzer\CarData;

final class CarDataTest extends TestCase {
    
    public function testRun(): void {
        echo PHP_EOL . "testRun" . PHP_EOL;
        $analizer = new CarData();
        $result= $analizer->run();
        //$this->assertEquals('X', $result);
    }    
}