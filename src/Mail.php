<?php

namespace Rapo;

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

class Mail {
    protected $mailer;
    protected $from;

    public function __construct() {
        $dsn = Env::get('MAIL_DSN', 'sendmail://default');
        $transport = Transport::fromDsn($dsn);
        $this->mailer = new Mailer($transport);
        $this->from = Env::get('MAIL_FROM', 'hello@rapo.php');
    }

    public function setFrom($email, $name = null) {
        $this->from = $email; // Symfony Mailer Address handles name separately or in string
        return $this;
    }

    /**
     * Send an email using a Rapo Component as the template
     */
    public function send($to, $subject, $componentClass, array $props = []) {
        if (!class_exists($componentClass)) {
            throw new \Exception("Email component class $componentClass not found");
        }

        $component = new $componentClass($props);
        $html = $component->render();

        $email = (new Email())
            ->from($this->from)
            ->to($to)
            ->subject($subject)
            ->html($html);

        return $this->mailer->send($email);
    }
}
