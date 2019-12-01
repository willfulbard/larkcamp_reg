<?php
namespace Responses\Formatter;

use Peridot\ObjectPath\ObjectPath;

class ValueFormatter
{
    protected $payload;
    protected $path;
    protected $config;
    protected $cost;
    public $payment_type;

    public function __construct($payload)
    {
        $this->payload = $payload;
        $this->config = $this->payload->config;

        $this->path = new ObjectPath($payload->json);

        $this->payment_type = substr($this->get("payment_type"), 0, 1);
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

    public function camper_exists($p)
    {
        return $this->get("campers[$p]") ? true : false;
    }

    public function get_pricing_logic()
    {
        return $this->config->pricingLogic;
    }

    public function blank()
    {
        return '';
    }

    public function zero()
    {
        return 0;
    }

    public function one()
    {
        return 1;
    }

    public function wtf_is($p)
    {
        return "What in the world is $p";
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
        if (!$this->camper_exists($p)) return '';

        $value = $this->get("campers[$p]->age");

        if (!$value) return 'N/A';

        return $value;
    }

    public function parking_passes_qty($type)
    {
        $passes = $this->get('parking_passes');

        if (!$passes) return '';

        if ($this->payment_type === $type) return '';

        $count = count($passes);
        $total = $this->getCost()->parking;

        return "$count $$total";
    }

    public function accomodations_camp($p)
    {
        // "Camp 1 = N/A
        //  Camp 2 = N/A
        //  Camp 3 = N/A"
        // ignore if no camper
        if (!$this->camper_exists($p)) return '';

        $value = $this->get("campers[$p]->accomodations->camp_preference");

        if (!$value) return join("\n", [
            "Camp 1 = N/A",
            "Camp 2 = N/A",
            "Camp 3 = N/A",
        ]);

        return join("\n", array_map(function($camp) use ($value) {
            if ("Camp $camp" === $value) {
                return "Camp $camp = 1st Choice";
            }
            return "Camp $camp = 4th Choice";

        }, [1, 2, 3, 4]));
    }

    public function accomodations_lodgepref($p)
    {
        // "Cabin = 4th Choice
        // Tent = 4th Choice
        // Vehicle = 4th Choice
        // Off Site = 1st Choice"

        if (!$this->camper_exists($p)) return '';

        $value = $this->get("campers[$p]->accomodations->accomodation_preference");

        if (!$this->camper_exists($p)) return '';

        if (!$value) return join("\n", [
            "Cabin = N/A",
            "Tent = N/A",
            "Vehicle = N/A",
            "Off Site = N/A",
        ]);

        return join("\n", array_map(function($pref) use ($value) {
            if ($pref === $value) {
                return "$pref = 1st Choice";
            }
            return "$pref = 4th Choice";
        }, [
            "Cabin",
            "Tent",
            "Vehicle",
            "Off Site",
        ]));

    }

    public function accomodations_tentarea($p)
    {
        if (!$this->camper_exists($p)) return '';

        $tenting_area_preference = $this->get("campers[$p]->accomodations->tenting_area_preference");

        if (!$tenting_area_preference) {
            return '';
        }

        $return_value = '';

        if (array_key_exists('first_choice', $tenting_area_preference)) {
            $return_value .= "{$tenting_area_preference->first_choice} = 1st Choice\n";
        } else {
            $return_value .= "? = 1st Choice\n";
        }

        if (array_key_exists('second_choice', $tenting_area_preference)) {
            $return_value .= "{$tenting_area_preference->second_choice} = 2nd Choice\n";
        } else {
            $return_value .= "? = 2nd Choice\n";
        }

        if (array_key_exists('third_choice', $tenting_area_preference)) {
            $return_value .= "{$tenting_area_preference->third_choice} = 3rd Choice\n";
        } else {
            $return_value .= "? = 3rd Choice\n";
        }

        if (array_key_exists('fourth_choice', $tenting_area_preference)) {
            $return_value .= "{$tenting_area_preference->fourth_choice} = 4th Choice\n";
        } else {
            $return_value .= "? = 4th Choice\n";
        }

        return $return_value;
    }

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
        $value = $this->get('parking_passes');

        if (!$value) return '';

        return join(',', array_map(function($p) {
            return $p->holder;
        }, $value));
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

        if ($this->payment_type === 'D') return '';

        return $this->tuition($p);
    }

    public function tuition_discount($p)
    {
        if (!array_key_exists($p, $this->getCost()->campers)) return '';

        if ($this->payment_type === 'F') return '';

        return $this->tuition($p);
    }

    public function tuition($p)
    {
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

    public function tuition_meals_discount($p)
    {
        if ($this->payment_type === 'F') return '';

        return $this->tuition_meals($p);
    }

    public function tuition_meals_full($p)
    {
        if ($this->payment_type === 'D') return '';

        return $this->tuition_meals($p);
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

    public function shirt($pricing, $type, $size)
    {

        if ($this->payment_type !== $pricing) return '';

        $count = $this->get($type . '_sizes->' . $size);

        if (!$count) return '';

        $cost = $this->config->pricing->$type * $count;

        return "$count \$$cost";
    }

    public function shirt_ts_full_s()  { return $this->shirt('F', 'tshirt', 'small'); }
    public function shirt_ts_full_m()  { return $this->shirt('F', 'tshirt', 'medium'); }
    public function shirt_ts_full_l()  { return $this->shirt('F', 'tshirt', 'large'); }
    public function shirt_ts_full_x()  { return $this->shirt('F', 'tshirt', 'xl'); }
    public function shirt_ts_full_2x() { return $this->shirt('F', 'tshirt', 'xxl'); }
    public function shirt_sw_full_s()  { return $this->shirt('F', 'sweatshirt', 'small'); }
    public function shirt_sw_full_m()  { return $this->shirt('F', 'sweatshirt', 'medium'); }
    public function shirt_sw_full_l()  { return $this->shirt('F', 'sweatshirt', 'large'); }
    public function shirt_sw_full_x()  { return $this->shirt('F', 'sweatshirt', 'xl'); }
    public function shirt_sw_full_2x() { return $this->shirt('F', 'sweatshirt', 'xxl'); }

    public function shirt_ts_discount_s()  { return $this->shirt('D', 'tshirt', 'small'); }
    public function shirt_ts_discount_m()  { return $this->shirt('D', 'tshirt', 'medium'); }
    public function shirt_ts_discount_l()  { return $this->shirt('D', 'tshirt', 'large'); }
    public function shirt_ts_discount_x()  { return $this->shirt('D', 'tshirt', 'xl'); }
    public function shirt_ts_discount_2x() { return $this->shirt('D', 'tshirt', 'xxl'); }
    public function shirt_sw_discount_s()  { return $this->shirt('D', 'sweatshirt', 'small'); }
    public function shirt_sw_discount_m()  { return $this->shirt('D', 'sweatshirt', 'medium'); }
    public function shirt_sw_discount_l()  { return $this->shirt('D', 'sweatshirt', 'large'); }
    public function shirt_sw_discount_x()  { return $this->shirt('D', 'sweatshirt', 'xl'); }
    public function shirt_sw_discount_2x() { return $this->shirt('D', 'sweatshirt', 'xxl'); }

    public function vehicle_length()
    {

        $strs = array_map(function ($camper) {
            $acc = $camper->accomodations;

            if ($acc->accomodation_preference !== 'Vehicle Camping') return false;

            $length = "unknown length";
            if (array_key_exists('vehicle_length', $acc)) $length = $acc->vehicle_length . "'";

            $make = "unknown make";
            if (array_key_exists('vehicle_make', $acc)) $make = $acc->vehicle_make;

            return "$length $make";
        }, $this->payload->json->campers);

        $total = join(', ', array_filter($strs));

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

        if (!$ip) return 'unknown location';

        $json = @file_get_contents("http://ipinfo.io/$ip/geo");

        if (!$json) return 'unknown location';

        $json = json_decode($json, true);

        $country = '';
        $region  = '';
        $city    = '';
        if (array_key_exists('country', $json)) $country = $json['country'];
        if (array_key_exists('region', $json)) $region = $json['region'];
        if (array_key_exists('city', $json)) $city = $json['city'];

        return "$city, $region, $country";
    }
}
