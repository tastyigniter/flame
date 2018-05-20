<?php

namespace Igniter\Flame\Notifications\Channels;

use Illuminate\Notifications\Channels\MailChannel as BaseMailChannel;
use Str;

class MailChannel extends BaseMailChannel
{
    /**
     * Build the mail message.
     *
     * @param  \Illuminate\Mail\Message $mailMessage
     * @param  mixed $notifiable
     * @param  \Illuminate\Notifications\Notification $notification
     * @param  \Illuminate\Notifications\Messages\MailMessage $message
     *
     * @return void
     */
    protected function buildMessage($mailMessage, $notifiable, $notification, $message)
    {
        $this->addressMessage($mailMessage, $notifiable, $message);

        if (method_exists($notification, 'applySubject') AND $notification->applySubject()) {
            $mailMessage->subject($message->subject ?: Str::title(
                Str::snake(class_basename($notification), ' ')
            ));
        }

        $this->addAttachments($mailMessage, $message);

        if (!is_null($message->priority)) {
            $mailMessage->setPriority($message->priority);
        }
    }
}