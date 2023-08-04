<?php

namespace App\Http\Validation;

use App\Models\Promotion;
use App\Models\Promotions\PromotionNotFoundException;
use App\Models\Promotions\UserQualifiedPromotion;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AdminCreateOrderWithPromotionValidation extends AdminCreateOrderValidation
{
    public Collection|null $promotions;

    public function __construct(public Request $request)
    {
        parent::__construct($this->request);
    }

    /**
     * @throws ValidationException
     */
    public function validate(): array
    {
        $request = $this->request;
        $data = $request->validate([
            'user_id' => 'required',
            'service_id' => 'required',
            'promotion_ids' => 'required|array|min:1',
            'product_ids' => 'nullable|array',
        ]);

        $this
            ->validateService()
            ->validateProduct()
            ->validateUser()
            ->validatePromotion()
            ->qualifyPromotion();

        unset($data['product_ids']);
        unset($data['promotion_ids']);

        return $this->afterValidate($data);
    }

    /**
     * validate promotions
     * @throws ValidationException
     */
    private function validatePromotion()
    {
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

        return $this;
    }

    /**
     * try to get the user qualified promotions
     * @throws ValidationException
     */
    private function qualifyPromotion()
    {
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

    protected function setAmount(): static
    {
        $this->validated['amount'] = $this->service->applyDiscount(
            $this->promotions->sum->getDiscount()
        );

        return $this;
    }

}
