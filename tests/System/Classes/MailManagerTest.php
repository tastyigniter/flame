<?php

namespace Tests\System\Classes;

use Igniter\System\Mail\TemplateMailable;
use Illuminate\Support\Facades\Mail;

it('sends registered mail template', function () {
    Mail::mailer('log')->queue(TemplateMailable::create('igniter.admin::_mail.order_update'), []);
})->skip();
