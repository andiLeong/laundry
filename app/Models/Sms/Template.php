<?php

namespace App\Models\Sms;

use Illuminate\Support\Str;
use InvalidArgumentException;

class Template
{
    protected array $templates = [
        'verification' => 'Hello, thanks for signing up for XXX, here is your phone verification code [%], this will expire in 5 minutes',
    ];

    /**
     * fetch a sms template
     * @param string $template
     * @param ...$replacements
     * @return array|string|string[]
     */
    public function get(string $template = 'verification', ...$replacements): array|string
    {
        if (!isset($this->templates[$template])) {
            throw new InvalidArgumentException('sms template {' . $template . '} not found');
        }

        return Str::replaceArray('%', $replacements, $this->templates[$template]);
    }

    /**
     * add a template at run time
     * @param $key
     * @param $value
     * @return Template
     */
    public function add($key, $value): static
    {
        $this->templates[$key] = $value;
        return $this;
    }
}
