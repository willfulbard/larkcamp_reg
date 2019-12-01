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

        // test gender
        $this->assertContains($arr[29], ['M', 'F', 'O'], $arr[30] . " isn't correct for camper 0 gender");

        // test accomdations formating
        //
        $value = $json->campers[0]->accomodations->camp_preference;

        $this->assertStringContainsString("$value = 1st Choice", $arr[31]);

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
            [14, "1\t/^(Full Price|Discount Price) - /"],
            [15, "0\t/^[0-9]+ \\$[0-9]+$/"], // parking passes
            [16, "0\t/^[0-9]+ \\$[0-9]+$/"], // parking passes
            // [31, "1\t/^(N\/A|[0-9]+)$/"], // 
            [37, "0\t/^($lengths) ($deposits) \\$[0-9]+/"], // we can only be sure there is one camper
            [38, "0\t/^($lengths) ($deposits) \\$[0-9]+/"], // we can only be sure there is one camper
            [39, "0\t/^($meal_options)$/"], // we can only be sure there is one camper
            [40, "0\t/^($meal_options)$/"], // we can only be sure there is one camper
            [173, "1\t/^1$/"], // we can only be sure there is one camper
        ];

        foreach($truths as list($index, $test)) {
            $value = $arr[$index];
            $desc = $rp->csv_config[$index][1]; // description out of json-to-csv.json

            list($required, $regex) = explode("\t", $test);

            if (!$required && !$value) continue;

            $msg = "value `$value` doesn't match $regex ([$index] $desc)";
            $this->assertRegExp("$regex", $value, $msg);
        }
    }

    public function testOnlyOneCamper(): void
    {
        $json_body_string = exec(getcwd() . '/php-tests/generate-random-reg.js');
        $json_parser = new \JSON\JSON();
        $json = $json_parser->parse($json_body_string);

        $camper = $json->campers[0];

        $camper->accomodations->accomodation_preference = 'Vehicle Camping';
        $camper->accomodations->vehicle_length = 12;
        $camper->accomodations->vehicle_make = 'Honda';

        // Just first camper
        $json->campers = [ $camper ];

        // should handle no parking passes
        $json->parking_passes = null;

        $rp = new \Responses\RegPayload(json_encode($json));

        $csv = $rp->toCSV();
        $arr = $rp->toCSVArray();

        $path = new ObjectPath($json);
        // var_dump($json);
        // var_dump($arr);

        $this->assertIsString($csv);
        $this->assertTrue($arr[173] > 0, 'Total should be greater than 0');

        $this->assertEquals($arr[174], '12\' Honda', 'Should have created the correct vehicle description');

        // Check a couple values
        $map = [
            'payer_first_name' => 2,
            'payer_last_name' => 3,
            'payment_type' => 14,
            'campers[0]->first_name' => 20,
            'campers[0]->gender' => 29,
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
