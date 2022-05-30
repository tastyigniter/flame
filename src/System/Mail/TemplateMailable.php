<?php

namespace Igniter\System\Mail;

use Igniter\Flame\Database\Model;
use Igniter\System\Classes\MailManager;
use Igniter\System\Helpers\ViewHelper;
use Igniter\System\Models\MailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;

class TemplateMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $templateCode;

    protected $rawContent;

    public static function create($templateCode)
    {
        $instance = new static;

        $instance->templateCode = $templateCode;

        return $instance;
    }

    public static function createFromRaw($raw)
    {
        $instance = new static;

        $instance->rawContent = $raw;

        return $instance;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(MailManager $manager)
    {
        if (!$template = $this->makeTemplate($manager))
            return $this;

        $data = $this->gatherViewData();
        $text = $manager->renderTextTemplate($template, $data);

        $this
            ->html($manager->renderTemplate($template, $data))
            ->subject($manager->renderView($template->subject, $data))
            ->withSymfonyMessage(function (Email $message) use ($text) {
                $message->text($text);
            });

        return $this;
    }

    protected function makeTemplate($manager)
    {
        if ($this->templateCode)
            return $manager->getTemplate($this->templateCode);

        if (!$this->rawContent)
            return null;

        $template = new MailTemplate();
        $template->fillFromContent($this->rawContent);

        return $template;
    }

    protected function gatherViewData()
    {
        $data = $this->buildViewData();

        $globalVars = ViewHelper::getGlobalVars();
        if (!empty($globalVars)) {
            $data += $globalVars;
        }

        return $data;
    }

    public function with($key, $value = null)
    {
        if (is_array($key)) {
            $key = array_filter($key, function ($v) {
                return !$v instanceof Model;
            });
        }

        return parent::with($key, $value);
    }
}
