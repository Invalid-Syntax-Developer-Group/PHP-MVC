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
    private string $replyTo = '';
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
    public function replyTo(string $replyTo): static
    {
        $this->replyTo = $replyTo;
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
    public function send(): bool
    {
        if (empty($this->to)) {
            throw new CompositionException('Recipient address is required.');
        }
        if (empty($this->from)) {
            throw new CompositionException('Sender address is required.');
        }
        if (empty($this->text) && empty($this->html)) {
            throw new CompositionException('At least one of text or HTML body is required.');
        }

        try {
            $mailer = $this->mailer();

            if (!empty($this->config['from']['email'])) {
                $configFromName = (string)($this->config['from']['name'] ?? '');
                $configFromEmail = (string)($this->config['from']['email'] ?? '');
                $mailer->setFrom($configFromEmail, $configFromName);
            } else {
                $mailer->setFrom($this->from);
            }

            $mailer->addAddress($this->to);

            if (!empty($this->config['reply_to']['email'])) {
                $replyToEmail = (string)($this->config['reply_to']['email'] ?? '');
                $replyToName = (string)($this->config['reply_to']['name'] ?? '');
                $mailer->addReplyTo($replyToEmail, $replyToName);
            } else if (!empty($this->replyTo)) {
                $replyTos = explode(';', $this->replyTo);
                foreach ($replyTos as $replyTo) {
                    $mailer->addReplyTo($replyTo);
                }
            }

            // BCC if provided
            if (!empty($this->bcc)) {
                $mailer->addBCC($this->bcc);
            }

            // Subject
            $mailer->Subject = $this->subject;

            // Body
            if (!empty($this->html)) {
                $mailer->isHTML(true);
                $mailer->Body = $this->html;
                if (!empty($this->text)) {
                    $mailer->AltBody = $this->text;
                }
            } else {
                $mailer->isHTML(false);
                $mailer->Body = $this->text;
            }

            // Attachments
            if (!empty($this->attachments)) {
                foreach ($this->attachments as $attachment) {
                    $mailer->addAttachment($attachment);
                }
            }

            return $mailer->send();
        } catch (PhpMailerException $exception) {
            throw new DriverException($exception->getMessage(), (int)$exception->getCode(), $exception);
        }
    }

    private function mailer(): PHPMailer
    {
        try {
            $mailer = new PHPMailer(true);
            $mailer->CharSet = (string)($this->config['charset'] ?? 'UTF-8');
            $mailer->Host    = (string)($this->config['host'] ?? 'localhost');
            $mailer->Port    = (int)($this->config['port'] ?? 25);

            $transport  = (string)($this->config['transport'] ?? '');
            switch ($transport) {
                case 'smtp':
                    $mailer->isSMTP();
                    $mailer->Username   = (string)($this->config['username'] ?? '');
                    $mailer->Password   = (string)($this->config['password'] ?? '');
                    $mailer->SMTPSecure = (string)($this->config['encryption'] ?? '');
                    $mailer->SMTPAuth   = (bool)($this->config['auth'] ?? true);
                    $mailer->Timeout    = (int)($this->config['timeout'] ?? 10);
                    $mailer->SMTPDebug  = (int)($this->config['debug'] ?? 0);

                    if (!empty($this->config['smtp_options']) &&
                        is_array($this->config['smtp_options'])) {
                        $mailer->SMTPOptions = $this->config['smtp_options'];
                    }
                    break;
                case 'sendmail':
                    $mailer->isSendmail();
                    break;
                default:
                    $mailer->isMail();
                    break;
            }

            return $mailer;
        }
        catch (PhpMailerException $exception) {
            throw new DriverException($exception->getMessage(), (int)$exception->getCode(), $exception);
        }
    }
}
