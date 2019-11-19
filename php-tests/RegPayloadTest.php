<?php
namespace LarkRegistration\Tests;

use PHPUnit\Framework\TestCase;
use Peridot\ObjectPath\ObjectPath;

require 'public/php/RegPayload.php';

class RegistrationPayloadClassTest extends TestCase
{
    public function testCreate(): void
    {
        $rp = new \Responses\RegPayload('"test"');
        $this->assertInstanceOf(\Responses\RegPayload::class, $rp);
    }

    public function testCsv(): void
    {
        $json_body_string = exec(getcwd() . '/php-tests/generate-random-reg.js');
        $json_parser = new \JSON\JSON();
        $json = $json_parser->parse($json_body_string);

        $rp = new \Responses\RegPayload($json_body_string);

        $csv = $rp->toCSV();
        $arr = $rp->toCSVArray();

        $this->assertIsString($csv);

        // test accomdations formating
        $this->assertContains($arr[29], ['1st Choice', '2nd Choice', '3rd Choice', 'N/A'], $arr[29] . " isn't correct");
        $this->assertContains($arr[30], ['1st Choice', '2nd Choice', '3rd Choice', 'N/A'], $arr[30] . " isn't correct");
        $this->assertContains($arr[31], ['1st Choice', '2nd Choice', '3rd Choice', 'N/A'], $arr[31] . " isn't correct");

        $accomodations_map = [
            'Camp 1' => 29,
            'Camp 2' => 30,
            'Camp 3' => 31,
        ];
        $value = $json->campers[0]->accomodations->camp_preference;
        $key = $accomodations_map[$value];
        $this->assertEquals($arr[$key], '1st Choice');

        foreach($arr as $value) {
            $value = print_r($value, true); 
            $this->assertStringNotMatchesFormat('NOMETHOD %a', $value, "$value");
        }
    }

    public function testOnlyOneCamper(): void
    {
        $json_body_string = exec(getcwd() . '/php-tests/generate-random-reg.js');
        $json_parser = new \JSON\JSON();
        $json = $json_parser->parse($json_body_string);
        $json->campers = [ $json->campers[0] ];

        $rp = new \Responses\RegPayload(json_encode($json));

        $csv = $rp->toCSV();
        $arr = $rp->toCSVArray();

        $path = new ObjectPath($json);
        // var_dump($json);
        // var_dump($arr);

        $this->assertIsString($csv);
        $this->assertEquals($arr[245], 1, 'CSV index 245 should be 1');
        $this->assertTrue($arr[245] > 0, 'Total should be greater than 0');

        // Check a couple values
        $map = [
            'payer_first_name' => 1,
            'payer_last_name' => 2,
            'payment_type' => 13,
            'campers[0]->first_name' => 18,
            'campers[0]->gender' => 27,
        ];

        foreach($map as $p => $index) {
            $json_value = '';
            $obj = $path->get($p);
            if ($obj) {
                $json_value = $obj->getPropertyValue();
            }

            $this->assertEquals($json_value, $arr[$index], "$p should be at CSV index $index");
        }


    }
}
