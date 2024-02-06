<?php

namespace Tests;

trait AdminAuthorization
{
    protected $method = 'postJson';

    /** @test */
    public function only_authenticated_user_can_access()
    {
         $this->{$this->method}($this->endpoint)->assertUnauthorized();
    }

    /** @test */
    public function only_admin_and_staff_user_can_access()
    {
        $this->signIn($this->customer())->{$this->method}($this->endpoint)->assertForbidden();
    }
}
