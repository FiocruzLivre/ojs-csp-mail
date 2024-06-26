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
use APP\facades\Repo;
use PKP\mail\Mailable;
use Illuminate\Support\Facades\Mail;
use PKP\mail\mailables\RevisedVersionNotify;
use PKP\db\DAORegistry;
use APP\core\Application;

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
        // Remove envio de email de notificação para editores quando autor faz submissão de nova versão
        $request = Application::get()->getRequest();
        $message = $event->message;
        $data = $event->data;
        $context = $event->context;
        $to = $message->getTo();
        $subject = $message->getSubject();
        $recipients = [];
        $submission = Repo::submission()->get((int) $request->getUservar('submissionId'));
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
        $assignedEditorIds = $stageAssignmentDao->getEditorsAssignedToStage($data["submissionId"], $submission->getData('stageId'));
        $i = 0;
        $editors = [3, 5]; // Ed. Chefe e Ed. Associado
        foreach ($to as $t) {
            $email = $t->getAddress();
            $recipients[] = $email;
            $x = $assignedEditorIds[$i]->getData('userGroupId');
            if (in_array($assignedEditorIds[$i]->getData('userGroupId'), $editors)) {
                if (($subject == "Envio de versão atualizada")) {
                    array_pop($recipients);
                    $skipMail = true;
                }
            }
            $i++;
        }
        if ($skipMail) {
            $mailable = new Mailable();
            $template = Repo::emailTemplate()->getByKey($context->getId(), RevisedVersionNotify::getEmailTemplateKey());
            $mailable->body($template->getLocalizedData('body'))
                ->subject($template->getLocalizedData('subject'))
                ->from($context->getData('contactEmail'))
                ->to($recipients);
            Mail::send($mailable);

            return false;
        }
    }
}
