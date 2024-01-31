<?php

namespace Tests;

trait TestAuthEndpoint
{
    protected $method = 'postJson';

    /** @test */
    public function it_gets_401_if_no_log_in(): void
    {
        $this->{$this->method}($this->endpoint)->assertUnauthorized();
    }
}
