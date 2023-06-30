<?php

namespace Tests\Unit;

use App\Models\Sms\Template;
use PHPUnit\Framework\TestCase;

class SmsTemplateTest extends TestCase
{
    /** @test */
    public function it_cen_replace_sms_template(): void
    {
        $templates = new Template();
        $templates->add('foo','bar..%..%.');
        $templates->add('foo2','bar..%..%....%');
        $foo = $templates->get('foo','replace1','replace2');
        $foo2 = $templates->get('foo2','1','2','3');

        $this->assertEquals('bar..replace1..replace2.', $foo);
        $this->assertEquals('bar..1..2....3', $foo2);
    }

    /** @test */
    public function it_throws_exception_if_template_not_found(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $templates = new Template();
        $templates->get('foo','replace1','replace2');
    }
}
