<?php

namespace App\Http\Validation;

use App\Models\Branch;
use App\Models\Coordinate;
use App\Models\DistanceCalculator;
use App\Models\GooglePlaces;
use App\Models\Place;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;

class AddressValidation
{
    private $place;

    /**
     * the place config
     * @var mixed|Repository|Application|\Illuminate\Foundation\Application
     */
    private mixed $config;

    /**
     * google api return payload contains place detail information
     * @var mixed
     */
    private array $payload;

    public function __construct(private readonly GooglePlaces $google)
    {
        $this->config = config('place');
    }

    /**
     * @throws \Exception
     */
    public function validate($placeId)
    {
        $place = Place::where('place_id', $placeId)->first();

        if (!is_null($place)) {
            $this->place = $place;
            return true;
        }

        if (!$this->google->get($placeId)) {
            throw new \Exception('Error on getting place, please contact support!');
        }

        $this->payload = $this->google->body;
        $this->checkDistance()->checkLocation();

        $this->place = Place::create([
            'place_id' => $placeId,
            'name' => $this->name,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ]);

        return true;
    }

    /**
     * @return mixed
     */
    public function getPlace()
    {
        return $this->place;
    }

    protected function checkLocation(): static
    {
        foreach ($this->payload['addressComponents'] as $component) {

            $types = $component['types'];

            if (in_array('country', $types) && !in_array($component['longText'], $this->config['country'])) {
                throw new \Exception('place is not in the service country');
            }

            if (in_array('administrative_area_level_1', $types) && !in_array($component['longText'], $this->config['province'])) {
                throw new \Exception('place is not in the service province');
            }

            if (in_array('locality', $types) && !in_array($component['longText'], $this->config['city'])) {
                throw new \Exception('place is not in the service city');
            }
        }

        return $this;
    }

    private function checkDistance(): static
    {
        $branch = Branch::first();
        $location = $this->payload['location'];
        $to = new Coordinate($location['longitude'], $location['latitude']);
        $from = new Coordinate($branch->longitude, $branch->latitude);
        $distance = (new DistanceCalculator($from, $to))->calculate();

        if ($distance >= $this->config['max_distance']) {
            throw new \Exception('The place seem like too far way from our branch');
        }
        return $this;
    }

    public function __get(string $name)
    {
        $attributes = [
            'address' => $this->payload['formattedAddress'],
            'name' => $this->payload['displayName']['text'],
            'latitude' => $this->payload['location']['latitude'],
            'longitude' => $this->payload['location']['longitude'],
        ];

        return $attributes[$name];
    }
}
