<?php

namespace AuroraWebSoftware\FilamentLoginKit\Contracts;

interface SmsServiceInterface
{
    public function send(string $phone, string $message): bool;
}
