<?php

/**
 * @file plugins /generic/cspMail/CspMailPlugin.inc.php
 *
 * Copyright (c) 2024 Lívia Gouvêa
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CspUserPlugin
 * @brief Customizes User profile fields
 */

namespace APP\plugins\generic\cspMail;

use Illuminate\Events\Dispatcher;
use PKP\observers\events\MessageSendingFromContext;

class MailChange
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            MessageSendingFromContext::class,
            MailChange::class
        );
    }

    public function handle(MessageSendingFromContext $event)
    {
        $body = $event->message->getTextBody();
        $event->message->setBody($body);
        // if (!is_a($event->decisionType, SendToProduction::class)) {
        //     return;
        // }

        // $submissionFiles = Repo::submissionFile()
        //     ->getCollector()
        //     ->filterBySubmissionIds([$event->submission->getId()])
        //     ->filterByFileStages([
        //         SubmissionFile::SUBMISSION_FILE_PRODUCTION_READY,
        //     ])
        //     ->getMany();

        // if ($submissionFiles->count()) {
        //     // Send files to third-party service.
        // }
    }
}