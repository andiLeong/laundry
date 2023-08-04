<?php

namespace App\Http\Validation;

class AdminCreateBulkOrderValidation
{
    public function __construct(protected OrderValidate $validate)
    {
        //
    }

    public function validate()
    {
        $this->validate->rules = array_merge($this->validate->rules,[
            'bulk' => 'required|numeric|gt:0'
        ]);

        $data = $this->validate->validate();
        unset($data['bulk']);
        return $data;
    }
}
