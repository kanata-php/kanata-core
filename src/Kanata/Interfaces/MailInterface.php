<?php

namespace Kanata\Interfaces;

interface MailInterface
{
    public function to(string $email, string $name = '');
    public function cc(string $email, string $name = '');
    public function bcc(string $email, string $name = '');
    public function send(string $subject, string $view, string $altView = ''): bool;
}