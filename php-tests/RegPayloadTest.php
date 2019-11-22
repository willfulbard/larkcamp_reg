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
        $this->assertContains($arr[32], ['1st Choice', '2nd Choice', '3rd Choice', 'N/A'], $arr[29] . " isn't correct");
        $this->assertContains($arr[33], ['1st Choice', '2nd Choice', '3rd Choice', 'N/A'], $arr[30] . " isn't correct");
        $this->assertContains($arr[34], ['1st Choice', '2nd Choice', '3rd Choice', 'N/A'], $arr[31] . " isn't correct");

        $accomodations_map = [
            'Camp 1' => 32,
            'Camp 2' => 33,
            'Camp 3' => 34,
        ];
        $value = $json->campers[0]->accomodations->camp_preference;
        $key = $accomodations_map[$value];
        $this->assertEquals($arr[$key], '1st Choice');

        foreach($arr as $value) {
            $value = print_r($value, true); 
            $this->assertStringNotMatchesFormat('NOMETHOD %a', $value, "$value");
        }

        $lengths = join([
            'Full Camp',
            'First Half Camp',
            'Second Half Camp',
        ], '|');

        $deposits = join([
            'Full Payment',
            '50 Percent',
        ], '|');

        $meal_options = join([
            'No Meals At This Time',
            'Full Meals All Of Camp \$[0-9]+',
            'Second Half Full Meals \$[0-9]+',
            'First Half Full Meals \$[0-9]+',
            'Just Dinners \$[0-9]+',
        ], '|');

        // Test values
        $truths = [
            [14, '/^(Full Price|Discount Price) - /'],
            [17, '/^[0-9]+ \$[0-9]+$/'],
            [31, '/^(N\/A|[0-9]+)$/'],
            [55, "/^($lengths) ($deposits) \\$[0-9]+/"], // we can only be sure there is one camper
            [57, "/^($meal_options)$/"], // we can only be sure there is one camper
        ];

        foreach($truths as list($index, $regex)) {
            $value = $arr[$index];
            $desc = $rp->csv_config[$index][1]; // description out of json-to-csv.json

            $msg = "value `$value` doesn't match $regex ([$index] $desc)";
            $this->assertRegExp("$regex", $value, $msg);
        }
    }

    public function testOnlyOneCamper(): void
    {
        $json_body_string = exec(getcwd() . '/php-tests/generate-random-reg.js');
        $json_parser = new \JSON\JSON();
        $json = $json_parser->parse($json_body_string);

        // Just first camper
        $json->campers = [ $json->campers[0] ];

        // should handle no parking passes
        $json->parking_passes = null;

        $rp = new \Responses\RegPayload(json_encode($json));

        $csv = $rp->toCSV();
        $arr = $rp->toCSVArray();

        $path = new ObjectPath($json);
        // var_dump($json);
        // var_dump($arr);

        $this->assertIsString($csv);
        $this->assertEquals($arr[260], 1, 'CSV index 245 (box should be ticked...) should be 1');
        $this->assertTrue($arr[259] > 0, 'Total should be greater than 0');

        // Check a couple values
        $map = [
            'payer_first_name' => 2,
            'payer_last_name' => 3,
            'payment_type' => 14,
            'campers[0]->first_name' => 21,
            'campers[0]->gender' => 30,
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
