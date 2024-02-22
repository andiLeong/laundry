<?php

namespace App\Http\Validation;

use App\Models\Enum\OrderType;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AdminCreateOrderValidation implements OrderValidate
{
    public Service|null $service;
    public User|null $user;
    public Collection|null $products = null;
    public array $validated;
    public array $rules = [];

    public function __construct(public Request $request)
    {
        $this->rules = [
            'amount' => 'nullable|decimal:0,4',
            'payment' => 'required|in:1,2',
            'user_id' => 'nullable',
            'service_id' => 'required',
            'product_ids' => 'nullable|array',
            'issued_invoice' => 'required|boolean',
            'paid' => 'required|boolean',
            'description' => 'nullable|string',
            'image' => 'nullable|array|max:5',
            'image.*' => 'image|max:2048',
            'type' => 'required|in:'. OrderType::ONLINE->value . ',' . OrderType::WALKIN->value,
        ];
    }

    /**
     * @throws ValidationException
     */
    public function validate(): array
    {
        $data = $this->request->validate($this->rules);

        $this
            ->validateService()
            ->validateProduct()
            ->validateUser();

        $data['amount'] ??= $this->service->price;

        unset($data['product_ids']);
        unset($data['image']);
        return $this->afterValidate($data);
    }

    /**
     * validate user
     * @throws ValidationException
     */
    protected function validateUser(): static
    {
        if ($this->request->has('user_id') && !is_null($this->request->get('user_id'))) {
            $this->user = User::find($this->request->get('user_id'));
            if (is_null($this->user)) {
                $this->exception('user_id', 'user is invalid');
            }
        }
        return $this;
    }

    /**
     * validate service
     * @throws ValidationException
     */
    protected function validateService(): static
    {
        $this->service = Service::find($this->request->get('service_id'));
        if (is_null($this->service)) {
            $this->exception('service_id', 'service is invalid');
        }

        return $this;
    }

    /**
     * throwing exception
     * @param $key
     * @param $value
     * @return mixed
     * @throws ValidationException
     */
    protected function exception($key, $value): mixed
    {
        throw ValidationException::withMessages([
            $key => [$value]
        ]);
    }

    protected function validateProduct()
    {
        if ($this->request->has('product_ids')) {
            $productIds = $this->request->get('product_ids');
            $this->products = Product::whereIn('id', array_column($productIds, 'id'))->get();

            if (count($productIds) !== count($this->products)) {
                $this->exception('product_ids', 'products are invalid');
            }

            $tem = [];
            foreach ($this->request->get('product_ids') as $item) {
                $tem[$item['id']] = $item['quantity'] ?? 1;
            }

            foreach ($this->products as $product) {
                if ($product->stock < $tem[$product->id]) {
                    $this->exception('product_ids', 'stock is not enough');
                }

                $product->quantity = $tem[$product->id];
            }
        }

        return $this;
    }

    public function hasProducts()
    {
        return !is_null($this->products);
    }

    protected function setAmount(): static
    {
        if (!isset($this->validated['amount'])) {
            $this->validated['amount'] = $this->service->price;
        }

        return $this;
    }

    /**
     * calculate the product amount if product id is present
     * @return mixed
     */
    protected function getProductAmount(): mixed
    {
        if ($this->hasProducts()) {
            return $this->products->map(function ($product) {
                $quantity = array_values(array_filter(
                    $this->request->get('product_ids'),
                    fn($pro) => $pro['id'] === $product->id
                ))[0]['quantity'] ?? 1;
                return $product->price * $quantity;
            })->sum();
        }
        return 0;
    }

    /**
     * run any merge action after request has been validated
     * @param $data
     * @return array
     */
    protected function afterValidate($data): array
    {
        $this->validated = $data;
        $this->setAmount();

        $productAmount = $this->getProductAmount();
        return array_merge($this->validated, [
            'product_amount' => $productAmount,
            'total_amount' => $this->validated['amount'] + $productAmount
        ]);
    }

}
