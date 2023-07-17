<?php

namespace App\Models\Promotions;

use Illuminate\Validation\ValidationException;

class PromotionNotFoundException extends \Exception
{
    protected int $defaultCode = 503;
    protected string $defaultMessage = 'promotion is not implemented';
    protected array $validationMessages;

    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        if(empty($message)){
            $message = $this->defaultMessage;
        }

        if($code === 0){
            $code = $this->defaultCode;
        }

        parent::__construct($message, $code, $previous);
    }

    public function throw()
    {
        if ($this->code === 503) {
            abort($this->code, $this->getMessage());
        }

        throw ValidationException::withMessages($this->validationMessages);
    }

    /**
     * @param array $validationMessages
     * @return PromotionNotFoundException
     */
    public function setValidationMessages(array $validationMessages): PromotionNotFoundException
    {
        $this->validationMessages = $validationMessages;
        return $this;
    }
}
