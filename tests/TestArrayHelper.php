<?php
use Carbon\Carbon;

class TestArrayHelper extends Orchestra\Testbench\TestCase
{
    public function testStringIsInArray()
    {
        $this->assertTrue(in_array('adfasdf', ['adfasdf', 'fsdaadfasd']));
    }
        

    public function testStringIsNotInArray()
    {
        $this->assertFalse(in_array('adfasdf', ['adfasdaf', 'xzcvxzcvzxvc']));
    }
}
