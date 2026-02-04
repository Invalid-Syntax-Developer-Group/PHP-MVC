<?php
declare(strict_types=1);
namespace PhpMVC\Email\Driver;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PhpMailerException;
use PhpMVC\Email\Exception\CompositionException;
use PhpMVC\Email\Exception\DriverException;

final class PhpMailerDriver implements Driver
{
    private array $config;
    private string $to = '';
    private string $from = '';
    private string $bcc = '';
    private string $subject = '';
    private string $text = '';
    private string $html = '';
    private array $attachments = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function to(string $to): static
    {
        $this->to = $to;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function from(string $from): static
    {
        $this->from = $from;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function bcc(string $bcc): static
    {
        $this->bcc = $bcc;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function subject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function text(string $text): static
    {
        $this->text = $text;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function html(string $html): static
    {
        $this->html = $html;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function attachments(array $attachments): static
    {
        $this->attachments = $attachments;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function send(): void
    {
        if ($this->to === '') {
            throw new CompositionException('Recipient address is required.');
        }

        if ($this->text === '' && $this->html === '') {
            throw new CompositionException('At least one of text or HTML body is required.');
        }

        $fromName = (string)($this->config['from']['name'] ?? '');
        $fromEmail = (string)($this->config['from']['email'] ?? '');
        if ($fromEmail === '') {
            throw new CompositionException('Sender address is required.');
        }

        $subject = $this->subject !== '' ? $this->subject : "Message from {$fromName}";

        try {
            $mailer = $this->mailer();
            $mailer->setFrom($fromEmail, $fromName);
            $mailer->addAddress($this->to);

            if (!empty($this->config['reply_to']['email'])) {
                $replyToName = (string)($this->config['reply_to']['name'] ?? '');
                $mailer->addReplyTo((string)$this->config['reply_to']['email'], $replyToName);
            }

            // BCC if provided
            if ($this->bcc !== '') {
                $mailer->addBCC($this->bcc);
            }

            // Subject
            $mailer->Subject = $subject;

            // Body
            if ($this->html !== '') {
                $mailer->isHTML(true);
                $mailer->Body = $this->html;
                if ($this->text !== '') {
                    $mailer->AltBody = $this->text;
                }
            } else {
                $mailer->isHTML(false);
                $mailer->Body = $this->text;
            }

            // Attachments
            foreach ($this->attachments as $attachment) {
                $mailer->addAttachment($attachment);
            }

            if (!$mailer->send()) {
                throw new DriverException('Email delivery failed.');
            }
        } catch (PhpMailerException $exception) {
            throw new DriverException($exception->getMessage(), (int)$exception->getCode(), $exception);
        }
    }

    private function mailer(): PHPMailer
    {
        $mailer = new PHPMailer(true);
        $mailer->CharSet = (string)($this->config['charset'] ?? 'UTF-8');

        $transport = (string)($this->config['transport'] ?? 'smtp');
        $smtpConfig = (array)($this->config['smtp'] ?? []);

        $host = (string)($smtpConfig['host'] ?? $this->config['host'] ?? '');
        $port = (int)($smtpConfig['port'] ?? $this->config['port'] ?? 587);
        $username = (string)($smtpConfig['username'] ?? $this->config['username'] ?? '');
        $password = (string)($smtpConfig['password'] ?? $this->config['password'] ?? '');
        $encryption = (string)($smtpConfig['encryption'] ?? $this->config['encryption'] ?? '');
        $auth = (bool)($smtpConfig['auth'] ?? $this->config['auth'] ?? true);
        $timeout = (int)($smtpConfig['timeout'] ?? $this->config['timeout'] ?? 10);
        $debug = (int)($smtpConfig['debug'] ?? $this->config['debug'] ?? 0);

        if ($transport === 'smtp' || $host !== '') {
            $mailer->isSMTP();
            $mailer->Host = $host;
            $mailer->Port = $port;
            $mailer->SMTPAuth = $auth;

            if ($username !== '') $mailer->Username = $username;
            if ($password !== '') $mailer->Password = $password;
            if ($encryption !== '') $mailer->SMTPSecure = $encryption;

            if (!empty($smtpConfig['options']) && is_array($smtpConfig['options'])) {
                $mailer->SMTPOptions = $smtpConfig['options'];
            }
            $mailer->SMTPDebug = $debug;
            $mailer->Timeout = $timeout;
        } elseif ($transport === 'sendmail') {
            $mailer->isSendmail();
        } else {
            $mailer->isMail();
        }

        return $mailer;
    }
}
