<?php

namespace fkooman\WebMention\Plugin;

use Swift_Message;
use Swift_MailTransport;
use Swift_Mailer;
use fkooman\WebMention\PluginInterface;

class MailPlugin implements PluginInterface
{
    /** @var string */
    private $from;

    /** @var string */
    private $to;

    public function __construct($from, $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    public function execute($source, $target)
    {
        $transport = Swift_MailTransport::newInstance();
        $mailer = Swift_Mailer::newInstance($transport);
        $message = Swift_Message::newInstance('Webmention Received');
        $message->setFrom($this->from);
        $message->setTo($this->to);
        $message->setBody(
            sprintf(
                'Your page "%s" was mentioned on "%s"!',
                $target,
                $source
            )
        );

        $mailer->send($message);
    }
}
