<?php

namespace App\Http\Validation;

use App\Models\Service;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerCreateOrderValidation extends AdminCreateOrderWithPromotionValidation
{

    public array $rules = [];

    public function __construct(public Request $request)
    {
        parent::__construct($this->request);
        $this->rules = [
//            'promotion_ids' => 'nullable|array',
            'address_id' => [
                'required',
                Rule::exists('addresses', 'id')
                    ->where(fn(Builder $query) => $query->where('user_id', auth()->id()))
            ],
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
    }

    /**
     * @throws ValidationException
     */
    public function validate(): array
    {
        $data = $this->request->validate($this->rules);
        $this->validateProduct();

        $this->service = Service::mostRequested();
        $data['service_id'] = $this->service->id;
        $data['product'] = $this->products;
        return $this->afterValidate($data);
    }

    protected function setAmount(): static
    {
        $this->validated['amount'] = $this->service->price;
        return $this;
    }

}
