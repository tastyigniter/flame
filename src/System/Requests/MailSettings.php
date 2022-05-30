<?php

namespace Igniter\System\Requests;

use Igniter\System\Classes\FormRequest;

class MailSettings extends FormRequest
{
    public function attributes()
    {
        return [
            'sender_name' => lang('igniter::system.settings.label_sender_name'),
            'sender_email' => lang('igniter::system.settings.label_sender_email'),
            'protocol' => lang('igniter::system.settings.label_protocol'),

            'mail_logo' => lang('igniter::system.settings.label_mail_logo'),
            'sendmail_path' => lang('igniter::system.settings.label_sendmail_path'),

            'smtp_host' => lang('igniter::system.settings.label_smtp_host'),
            'smtp_port' => lang('igniter::system.settings.label_smtp_port'),
            'smtp_encryption' => lang('igniter::system.settings.label_smtp_encryption'),
            'smtp_user' => lang('igniter::system.settings.label_smtp_user'),
            'smtp_pass' => lang('igniter::system.settings.label_smtp_pass'),

            'mailgun_domain' => lang('igniter::system.settings.label_mailgun_domain'),
            'mailgun_secret' => lang('igniter::system.settings.label_mailgun_secret'),

            'postmark_token' => lang('igniter::system.settings.label_postmark_token'),

            'ses_key' => lang('igniter::system.settings.label_ses_key'),
            'ses_secret' => lang('igniter::system.settings.label_ses_secret'),
            'ses_region' => lang('igniter::system.settings.label_ses_region'),
        ];
    }

    public function rules()
    {
        return [
            'sender_name' => ['required', 'string'],
            'sender_email' => ['required', 'email:filter'],
            'protocol' => ['required', 'string'],

            'mail_logo' => ['string'],
            'sendmail_path' => ['required_if:protocol,sendmail', 'string'],

            'smtp_host' => ['string'],
            'smtp_port' => ['string'],
            'smtp_user' => ['string'],
            'smtp_pass' => ['string'],

            'mailgun_domain' => ['required_if:protocol,mailgun', 'string'],
            'mailgun_secret' => ['required_if:protocol,mailgun', 'string'],

            'postmark_token' => ['required_if:protocol,postmark', 'string'],

            'ses_key' => ['required_if:protocol,ses', 'string'],
            'ses_secret' => ['required_if:protocol,ses', 'string'],
            'ses_region' => ['required_if:protocol,ses', 'string'],
        ];
    }
}
