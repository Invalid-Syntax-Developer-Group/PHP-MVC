<?php
declare(strict_types=1);
namespace PhpMVC\Email\Driver;

use Swift_Mailer;
use Swift_Message;
use Postmark\Transport;
use PhpMVC\Email\Exception\CompositionException;

final class PostmarkDriver extends Driver
{
    private array $config;
    private Swift_Mailer $mailer;
    private string $to = '';
    private string $subject = '';
    private string $text = '';
    private string $html = '';

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function to(string $to): static
    {
        $this->to = $to;
        return $this;
    }

    public function subject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function text(string $text): static
    {
        $this->text = $text;
        return $this;
    }

    public function html(string $html): static
    {
        $this->html = $html;
        return $this;
    }

    public function send(): void
    {
        if (!isset($this->to)) {
            throw new CompositionException('Recipient address is required.');
        }

        if (!isset($this->text) && !isset($this->html)) {
            throw new CompositionException('At least one of text or HTML body is required.');
        }

        $fromName = $this->config['from']['name'];
        $fromEmail = $this->config['from']['email'];
        $subject = $this->subject ?? "Message from {$fromName}";
        $message = (new Swift_Message($subject))
            ->setFrom([$fromEmail => $fromName])
            ->setTo([$this->to]);
        
        if (!isset($this->text) && !isset($this->html)) {
            $message->setBody($this->text, 'text/plain');
        }

        if (!isset($this->text) && isset($this->html)) {
            $message->setBody($this->html, 'text/html');
        }

        if (isset($this->text, $this->html)) {
            $message
                ->setBody($this->html, 'text/html')
                ->addPart($this->text, 'text/plain');
        }

        $this->mailer()->send($message);
    }

    private function mailer()
    {
        if (!isset($this->mailer)) {
            $transport = new Transport($this->config['token']);
            $this->mailer = new Swift_Mailer($transport);
        }

        return $this->mailer;
    }
}