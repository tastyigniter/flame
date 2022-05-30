<?php

namespace Igniter\System\Traits;

use Illuminate\Support\Facades\Mail;

trait SendsMailTemplate
{
    public function mailGetRecipients($type)
    {
        return [];
    }

    public function mailGetData()
    {
        return [];
    }

    public function mailSend($view, $recipientType = null)
    {
        $vars = $this->mailGetData();

        $result = $this->fireEvent('model.mailGetData', [$view, $recipientType]);
        if ($result && is_array($result))
            $vars = array_merge(...$result) + $vars;

        if ($recipients = $this->mailBuildMessageTo($recipientType))
            Mail::queueTemplate($view, $vars, $recipients);
    }

    protected function mailBuildMessageTo($recipientType = null)
    {
        $recipients = [];
        foreach ($this->mailGetRecipients($recipientType) as $recipient) {
            [$email, $name] = $recipient;
            $recipients[] = ['name' => $name, 'email' => $email];
        }

        return $recipients;
    }
}
