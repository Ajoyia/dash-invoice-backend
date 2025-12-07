<?php

namespace App\Services\Company;

use App\Services\Queue\QueueServiceInterface;

class CompanyRegistrationMailService implements CompanyRegistrationMailServiceInterface
{
    public function __construct(
        private QueueServiceInterface $queueService
    ) {}

    public function sendRegistrationMail(array $userData): void
    {
        $mailData = [
            'mail' => $userData['email'],
            'password' => $userData['password'] ?? 'fwed2uh345ert',
            'mail_template_id' => $userData['mail_template_id'] ?? null,
            'from_mail' => $userData['from_mail'] ?? null,
            'first_name' => $userData['firstName'] ?? $userData['first_name'] ?? null,
            'last_name' => $userData['lastName'] ?? $userData['last_name'] ?? null,
            'cc' => $userData['cc'] ?? null,
            'bcc' => $userData['bcc'] ?? null,
            'only_new' => $userData['only_new'] ?? true,
        ];

        $this->queueService->pushToQueue('users_queue', $mailData);
    }
}
