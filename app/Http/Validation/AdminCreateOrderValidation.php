<?php

namespace App\Http\Validation;

use App\Models\Promotion;
use App\Models\Promotions\PromotionNotFoundException;
use App\Models\Promotions\UserQualifiedPromotion;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminCreateOrderValidation
{
    public Service|null $service;
    public User|null $user;
    public $promotions;

    public function __construct(public Request $request)
    {
        //
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
            'isolated' => [
                'nullable',
                'in:0,1',
                Rule::requiredIf($this->shouldValidatePromotionIds())
            ],
        ]);

        $this
            ->validateService()
            ->validateUser()
            ->validatePromotion()
            ->qualifyPromotion();

        if (!$this->shouldValidatePromotionIds()) {
            $data['amount'] ??= $this->service->price;
        }

        unset($data['isolated']);
        unset($data['promotion_ids']);
        return $data;
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
            $isolated = $this->request->get('isolated');

            $promotions = Promotion::enabled()
                ->available()
                ->whereIn('id', $promotionIds)
                ->where('isolated', $this->request->get('isolated'))
                ->get();

            if (count($promotions) !== count($promotionIds)) {
                $this->exception('promotion_ids', 'promotions are invalid');
            }

            if ($isolated == 1 && count($promotionIds) !== 1) {
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
}
