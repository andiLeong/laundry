<?php

namespace App\Http\Validation;

use App\Models\Address;
use App\Models\Branch;
use App\Models\DeliveryFeeCalculator;
use App\Models\Place;
use App\Models\Service;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CustomerCreateOrderValidation extends AdminCreateOrderValidation
{

    public array $rules = [];
    private mixed $address;

    public function __construct(public Request $request)
    {
        parent::__construct($this->request);
    }

    protected function setRule(): static
    {
        $this->rules = [
//            'promotion_ids' => 'nullable|array',
            'address_id' => ['required'],
            'product_ids' => 'nullable|array',
            'delivery' => ['bail', 'nullable', 'date',
                function (string $attribute, mixed $value, Closure $fail) {
                    $date = Carbon::parse($value);
                    if ($date->isPast()) {
                        $fail('Delivery date cant in the past.');
                    }
                }
            ],
            'pickup' => ['bail', 'required', 'date',
                function (string $attribute, mixed $value, Closure $fail) {
                    $date = Carbon::parse($value);
                    if ($date->isPast()) {
                        $fail('Pickup date cant in the past.');
                    }
//                    dump($date->diff(now()));
                    if ($date->diffInHours(now()) < 1) {
                        $fail('Pickup date at least one hour from now.');
                    }
                }
            ],
            'add_products' => 'nullable|in:0,1',
            'description' => 'nullable|string|max:255',
            'image' => 'nullable|array|max:2',
            'image.*' => 'image|max:2048',
        ];

        return $this;
    }

    /**
     * @throws ValidationException
     */
    public function validate(): array
    {
        $data = $this->request->validate($this->rules);
        $this->validateAddress()->validateProduct();

        $place = Place::find($this->address->place_id);
        $calculator = new DeliveryFeeCalculator($place, Branch::first());

        $this->service = Service::mostRequested();
        $data['service_id'] = $this->service->id;
        $data['product'] = $this->products;
        $data['delivery_fee'] = $calculator->calculate();
        return $this->afterValidate($data);
    }

    public function validateAddress(): static
    {
        $address = Address::where('id', $this->request->address_id)->where('user_id', auth()->id())->first();
        if (is_null($address)) {
            $this->exception('address_id', 'We\'re sorry, we could not find your address');
        }
        $this->address = $address;
        return $this;
    }

    protected function setTotalAmount(): static
    {
        $this->validated['total_amount'] = $this->validated['amount']
            + $this->validated['product_amount']
            + $this->validated['delivery_fee'];
        return $this;
    }
}
