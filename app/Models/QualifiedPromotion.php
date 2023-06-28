<?php

namespace App\Models;

use Illuminate\Support\Collection;

class QualifiedPromotion
{
    protected $columns = ['*'];

    public function __construct(
        protected User    $user,
        protected Service $service
    )
    {
        //
    }

    public function get()
    {
        $promotions = Promotion::query()
            ->enabled()
            ->available()
            ->get($this->getColumns());

        return $this->filter($promotions);
    }

    public function filter(Collection $promotions) :Collection
    {
        return $promotions
            ->map(function ($promotion) {
                if (!class_exists($promotion['class'])) {
                    throw new PromotionNotFoundException('promotion is not implemented');
                }
                return new $promotion['class']($this->user, $this->service, $promotion);
            })
            ->filter
            ->qualify();
    }

    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param string[] $columns
     * @return QualifiedPromotion
     */
    public function setColumns(array $columns): QualifiedPromotion
    {
        $this->columns = $columns;
        return $this;
    }

}
