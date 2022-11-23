<?php

namespace Tests\System\Classes;

use Igniter\System\Classes\MailManager;

it('registers an extension', function () {
    $manager = resolve(MailManager::class);
    $template = $manager->getTemplate('_mail.test_template');

    expect((string)$manager->renderTextTemplate($template))
        ->toContain('PLAIN TEXT CONTENT');

    expect((string)$manager->renderTemplate($template))
        ->toContain('HTML CONTENT');

    expect((string)$manager->renderView($template->subject))
        ->toContain('Test mail template subject');
});
