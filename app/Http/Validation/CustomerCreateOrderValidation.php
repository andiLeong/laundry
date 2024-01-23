<?php

namespace App\Http\Validation;

use App\Models\Service;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
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
            'delivery' => 'nullable|date',
            'pickup' => 'required|date', //todo add more validation logic on the pickup date
//            'product_apply_each_load' => 'required_if:product_ids|boolean',
            'description' => 'nullable|string|max:255',
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
