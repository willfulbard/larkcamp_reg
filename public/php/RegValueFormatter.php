<?php
namespace Responses\Formatter;

use Peridot\ObjectPath\ObjectPath;

class ValueFormatter
{
    protected $payload;
    protected $path;
    protected $config;
    protected $cost;

    public function __construct($payload)
    {
        $this->payload = $payload;
        $this->config = $this->payload->config;

        $this->path = new ObjectPath($payload->json);
    }

    public function getCost() {
        if ($this->cost) return $this->cost; // singleton

        $pricing_logic = $this->config->pricingLogic;
        $reg = $this->payload->json;


        $data = [
            'registration' => $reg,
            'pricing' => $this->config->pricing,
        ];

        $cost = new \stdClass; 

        $cost->campers = array_map(
            function ($c) use ($data, $pricing_logic) {
                $data['camper'] = $c;

                return \JWadhams\JsonLogic::apply(
                    $pricing_logic->camper,
                    $data
                );
            },
            $reg->campers
        );

        $cost->meals = array_map(
            function ($c) use ($data, $pricing_logic) {
                $data['camper'] = $c;

                return \JWadhams\JsonLogic::apply(
                    $pricing_logic->meals,
                    $data
                );
            },
            $reg->campers
        );

        $cost->parking = \JWadhams\JsonLogic::apply(
            $pricing_logic->parking,
            $data
        );

        $cost->shirts = \JWadhams\JsonLogic::apply(
            $pricing_logic->shirts,
            $data
        );

        $cost->donation = \JWadhams\JsonLogic::apply(
            $pricing_logic->donation,
            $data
        );

        $cost->total = (
            array_reduce($cost->campers, function($acc, $c) { return $acc + $c; }, 0) +
            array_reduce($cost->meals, function($acc, $c) { return $acc + $c; }, 0) +
            $cost->parking +
            $cost->shirts +
            $cost->donation
        );

        $this->cost = $cost;

        return $cost;
    }

    public function get($p)
    {
        $obj = $this->path->get($p);

        if (!$obj) return '';
    
        return $obj->getPropertyValue();
    }

    public function get_pricing_logic()
    {
        return $this->config->pricingLogic;
    }

    public function zero()
    {
        return 0;
    }

    public function one()
    {
        return 1;
    }

    public function submission_time()
    {
        $date = new \DateTime('America/Los_Angeles');
        return $date->format('Y-m-d H:i:s');
    }

    public function camper_count()
    {
        return count($this->get('campers'));
    }

    public function age($p)
    {
        $value = $this->get("campers[$p]->age");

        if (!$value) return 'N/A';

        return $value;
    }

    public function parking_passes_qty()
    {
        $count = count($this->get('parking_passes'));
        $total = $this->getCost()->parking;

        return "$count $$total";
    }

    public function accomodations_camp($camp, $p)
    {
        $value = $this->get($p);

        if (!$value) return '';

        if ("Camp $camp" === $value->camp_preference) {
            return "1st Choice";
        }

        return "N/A";
    }

    public function accomodations_camp_1($p) { return $this->accomodations_camp("1", $p); }
    public function accomodations_camp_2($p) { return $this->accomodations_camp("2", $p); }
    public function accomodations_camp_3($p) { return $this->accomodations_camp("3", $p); }

    public function accomodations_lodgepref($pref, $p)
    {
        $value = $this->get($p);

        if (!$value) return '';

        if ($pref === $value->accomodation_preference) {
            return "1st Choice";
        }

        return "N/A";
    }

    public function accomodations_lodgepref_cabin($p)   { return $this->accomodations_lodgepref("Cabin", $p); }
    public function accomodations_lodgepref_tent($p)    { return $this->accomodations_lodgepref("Tent", $p); }
    public function accomodations_lodgepref_vehicle($p) { return $this->accomodations_lodgepref("Vehicle", $p); }
    public function accomodations_lodgepref_offsite($p) { return $this->accomodations_lodgepref("Offsite", $p); }

    public function accomodations_tentarea($pref, $p)
    {
        $value = $this->get($p);

        if (!method_exists($value, 'tenting_area_preference')) {
            return '';
        }

        if ($pref === $value->tenting_area_preference->first_choice) {
            return "1st Choice";
        }

        if ($pref === $value->tenting_area_preference->second_choice) {
            return "2nd Choice";
        }

        if ($pref === $value->tenting_area_preference->third_choice) {
            return "3rd Choice";
        }

        if ($pref === $value->tenting_area_preference->fourth_choice) {
            return "4th Choice";
        }

        return '';
    }

    public function accomodations_tentarea_A($p) { return $this->accomodations_tentarea("A", $p); }
    public function accomodations_tentarea_B($p) { return $this->accomodations_tentarea("B", $p); }
    public function accomodations_tentarea_C($p) { return $this->accomodations_tentarea("C", $p); }
    public function accomodations_tentarea_D($p) { return $this->accomodations_tentarea("D", $p); }
    public function accomodations_tentarea_E($p) { return $this->accomodations_tentarea("E", $p); }
    public function accomodations_tentarea_F($p) { return $this->accomodations_tentarea("F", $p); }
    public function accomodations_tentarea_G($p) { return $this->accomodations_tentarea("G", $p); }
    public function accomodations_tentarea_H($p) { return $this->accomodations_tentarea("H", $p); }
    public function accomodations_tentarea_I($p) { return $this->accomodations_tentarea("I", $p); }
    public function accomodations_tentarea_J($p) { return $this->accomodations_tentarea("J", $p); }
    public function accomodations_tentarea_K($p) { return $this->accomodations_tentarea("K", $p); }
    public function accomodations_tentarea_L($p) { return $this->accomodations_tentarea("L", $p); }

    public function join_field($p, $method)
    {
        $value = $this->get($p);

        if (!$value) return '';

        if (!method_exists($value, $method)) {
            return '';
        }

        return join(', ', $value);
    }

    public function cabinmates($p) { return $this->join_field($p, 'cabinmates'); }
    public function tentmates($p)  { return $this->join_field($p, 'tentmates'); }
    public function parking_passes_names() {
        return join(',', array_map(function($p) {
            return $p->holder;
        }, $this->get('parking_passes')));
    }
    public function camper_number () { return ''; }
    public function camper_email  () { return ''; }

    public function total()
    {

        return $this->getCost()->total;
    }

    public function tuition_full($p)
    {
        if (!array_key_exists($p, $this->getCost()->campers)) return '';

        $cost = $this->getCost()->campers[$p];

        $len_map = [
            'F' => 'Full Camp',
            'A' => 'First Half Camp',
            'B' => 'Second Half Camp',
        ];
        $length = $len_map[$this->get("campers[$p]->session")];

        $dep_map = [
            'full' => 'Full Payment',
            'deposit' => '50 Percent',
        ];
        $deposit = $dep_map[$this->get("payment_full_or_deposit")];

        return "$length $deposit $$cost";
    }

    public function tuition_meals($p)
    {
        if (!array_key_exists($p, $this->getCost()->meals)) return '';

        $cost = $this->getCost()->meals[$p];

        $meal_map = [
            ''  => 'No Meals At This Time',
            'F' => 'Full Meals All Of Camp $' . $cost,
            'D' => 'Second Half Full Meals $' . $cost,
            'A' => 'First Half Full Meals $' . $cost,
            'B' => 'Just Dinners $' . $cost,
        ];

        return $meal_map[$this->get("campers[$p]->meals->meal_plan")];

    }

    public function shirt($type, $size)
    {
        $count = $this->get($type . '_sizes->' . $size);
        $pricing_logic = $this->get_pricing_logic()->shirts;
        // starts at 4?
        $map = [
            'tshirt' => [
                'small'  => 0,
                'medium' => 1,
                'large'  => 2,
                'xl'     => 3,
                'xxl'    => 4,
            ],
            'sweatshirt' => [
                'small'  => 5,
                'medium' => 6,
                'large'  => 7,
                'xl'     => 8,
                'xxl'    => 9,
            ],
        ];

        $cost = \JWadhams\JsonLogic::apply(
            $pricing_logic->{'+'}[$map[$type][$size]],
            $this->payload
        );

        return $cost;
    }

    public function shirt_ts_s()  { return $this->shirt('tshirt', 'small'); }
    public function shirt_ts_m()  { return $this->shirt('tshirt', 'medium'); }
    public function shirt_ts_l()  { return $this->shirt('tshirt', 'large'); }
    public function shirt_ts_x()  { return $this->shirt('tshirt', 'xl'); }
    public function shirt_ts_2x() { return $this->shirt('tshirt', 'xxl'); }
    public function shirt_sw_s()  { return $this->shirt('sweatshirt', 'small'); }
    public function shirt_sw_m()  { return $this->shirt('sweatshirt', 'medium'); }
    public function shirt_sw_l()  { return $this->shirt('sweatshirt', 'large'); }
    public function shirt_sw_x()  { return $this->shirt('sweatshirt', 'xl'); }
    public function shirt_sw_2x() { return $this->shirt('sweatshirt', 'xxl'); }

    public function vehicle_length()
    {
        $total = join(', ', array_filter(array_map(function ($camper) {
            $acc = $camper->accomodations;

            if ($acc->accomodation_preference !== 'Vehicle Camping') return false;

            $length = "unknown length";
            if (method_exists($acc, 'vehicle_length')) $length = $acc->vehicle_length . "'";

            $make = "unknown make";
            if (method_exists($acc, 'vehicle_make')) $make = $acc->vehicle_make;

            return "$length $make";
        }, $this->payload->json->campers)));

        return $total;
    }

    public function hash_id()  { return 'none'; }

    function ip()
    {
        $ip_address = '';

        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED'];
        } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_FORWARDED'])) {
            $ip_address = $_SERVER['HTTP_FORWARDED'];
        } else if (isset($_SERVER['REMOTE_ADDR'])) {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip_address = false;
        }

        return $ip_address;
    }

    public function browser()
    {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $ip_address = $_SERVER['HTTP_USER_AGENT'];
        }

        return 'unknown';
    }

    public function location()
    {
        $ip = $this->ip();

        if (!$ip) return 'unknown';

        $json     = file_get_contents("http://ipinfo.io/$ip/geo");
        $json     = json_decode($json, true);
        $country  = '';
        $region  = '';
        $city  = '';
        if (array_key_exists('country', $json)) $country = $json['country'];
        if (array_key_exists('region', $json)) $region = $json['region'];
        if (array_key_exists('city', $json)) $city = $json['city'];

        return "$city, $region, $country";
    }
}
