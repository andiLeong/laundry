<?php

namespace App\Http\Validation;

use App\Models\Promotion;
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
                Rule::requiredIf($request->has('promotion_ids'))
            ],
            'service_id' => 'required',
            'promotion_ids' => 'nullable|array',
            'isolated' => [
                'nullable',
                'in:0,1',
                Rule::requiredIf($request->has('promotion_ids'))
            ],
        ]);

        $this
            ->validateService()
            ->validateUser()
            ->validatePromotion();

        return $data;
    }

    /**
     * @throws ValidationException
     */
    private function validateUser(): static
    {
        if ($this->request->has('user_id')) {
            $this->user = User::find($this->request->get('user_id'));
            if (is_null($this->user)) {
                $this->exception('user_id','user is invalid');
            }
        }
        return $this;
    }

    /**
     * @throws ValidationException
     */
    private function validateService(): static
    {
        $this->service = Service::find($this->request->get('service_id'));
        if (is_null($this->service)) {
            $this->exception('service_id','service is invalid');
        }

        return $this;
    }

    /**
     * @throws ValidationException
     */
    private function validatePromotion(): void
    {
        if ($this->request->has('promotion_ids')) {
            $promotionIds = $this->request->get('promotion_ids');
            $isolated = $this->request->get('isolated');

            $promotions = Promotion::enabled()
                ->available()
                ->whereIn('id', $promotionIds)
                ->where('isolated', $this->request->get('isolated'))
                ->get();

            if (count($promotions) !== count($promotionIds)) {
                $this->exception('promotion_ids','promotions are invalid');
            }

            if($isolated == 1 && count($promotionIds) !== 1 ){
                $this->exception('promotion_ids','isolated promotion is only allow one at a time');
            }

            foreach ($promotions as $promotion)
            {
                if (!class_exists($promotion['class'])) {
                    $this->exception('promotion_ids','promotion is not implemented');
                }
            }

            $this->promotions = $promotions;
        }
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     * @throws ValidationException
     */
    protected function exception($key,$value): mixed
    {
        throw ValidationException::withMessages([
            $key => [$value]
        ]);
    }
}
