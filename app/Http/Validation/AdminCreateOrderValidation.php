<?php

namespace App\Http\Validation;

use App\Models\Product;
use App\Models\Promotion;
use App\Models\Promotions\PromotionNotFoundException;
use App\Models\Promotions\UserQualifiedPromotion;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminCreateOrderValidation
{
    public Service|null $service;
    public User|null $user;
    public Collection|null $promotions;
    public Collection|null $products = null;
    public array $validated;

    public function __construct(public Request $request)
    {
        //
        // different type of validation
        //one with promotion
        //one no promotion

        //add on
        //sometime need to validation order product
    }

    /**
     * @throws ValidationException
     */
    public function validate()
    {
        $request = $this->request;
        $data = $request->validate([
            'amount' => 'nullable|decimal:0,4',
            'user_id' => [
                'nullable',
                Rule::requiredIf($this->shouldValidatePromotionIds())
            ],
            'service_id' => 'required',
            'promotion_ids' => 'nullable|array|min:1',
            'product_ids' => 'nullable|array',
        ]);

        $this
            ->validateService()
            ->validateProduct()
            ->validateUser()
            ->validatePromotion()
            ->qualifyPromotion();

        if (!$this->shouldValidatePromotionIds()) {
            $data['amount'] ??= $this->service->price;
        }

        unset($data['isolated']);
        unset($data['product_ids']);
        unset($data['promotion_ids']);
        $this->validated = $data;
        $this->setAmount();


        $productAmount = $this->getProductAmount();
        return array_merge($this->validated, [
            'product_amount' => $productAmount,
            'total_amount' => $this->validated['amount'] + $productAmount
        ]);
    }

    /**
     * validate user
     * @throws ValidationException
     */
    private function validateUser(): static
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
    private function validateService(): static
    {
        $this->service = Service::find($this->request->get('service_id'));
        if (is_null($this->service)) {
            $this->exception('service_id', 'service is invalid');
        }

        return $this;
    }

    /**
     * validate promotions
     * @throws ValidationException
     */
    private function validatePromotion()
    {
        if ($this->shouldValidatePromotionIds()) {
            $promotionIds = $this->request->get('promotion_ids');

            $promotions = Promotion::enabled()
                ->available()
                ->whereIn('id', $promotionIds)
                ->get();

            if (count($promotions) !== count($promotionIds)) {
                $this->exception('promotion_ids', 'promotions are invalid');
            }

            if ($promotions->contains->isIsolated() && $promotions->count() !== 1) {
                $this->exception('promotion_ids', 'isolated promotion is only allow one at a time');
            }

            $this->promotions = $promotions;
        }

        return $this;
    }

    /**
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

    /**
     * check if we need to validated promotion_ids
     * @return bool
     */
    public function shouldValidatePromotionIds()
    {
        return !is_null($this->request->get('promotion_ids'));
    }

    /**
     * try to get the user qualified promotions
     * @throws ValidationException
     */
    private function qualifyPromotion()
    {
        if ($this->shouldValidatePromotionIds()) {

            $exception = new PromotionNotFoundException('', 422);
            $exception->setValidationMessages([
                'promotion_ids' => [$exception->getMessage()]
            ]);
            $qualifyPromotions = new UserQualifiedPromotion($this->user, $this->service, $exception);
            $qualifyPromotions = $qualifyPromotions->filter($this->promotions);


            if ($qualifyPromotions->isEmpty()) {
                $this->exception('promotion_ids', 'Sorry You are not qualified with these promotions');
            }
            $this->promotions = $qualifyPromotions;
        }
    }

    private function validateProduct()
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
            }
        }

        return $this;
    }

    public function hasProducts()
    {
        return !is_null($this->products);
    }

    public function setAmount()
    {
        if ($this->shouldValidatePromotionIds()) {
            $this->validated['amount'] = $this->service->applyDiscount(
                $this->promotions->sum->getDiscount()
            );
        }
    }

    private function getProductAmount()
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
}
