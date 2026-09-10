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
use PKP\observers\events\MessageSendingFromSite;
use APP\facades\Repo;
use PKP\mail\Mailable;
use Illuminate\Support\Facades\Mail;
use PKP\mail\mailables\RevisedVersionNotify;
use APP\core\Application;
use PKP\security\Role;
use PKP\stageAssignment\StageAssignment;

class MailChange
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            MessageSendingFromContext::class,
            MailChange::class
        );

        $events->listen(
            MessageSendingFromSite::class,
            MailChange::class
        );
    }

    public function handle(MessageSendingFromContext|MessageSendingFromSite $event)
    {
        if ($event->data["submissionId"]) {
            // Substitui a variável $submissionIdCSP pelo ID do CSP em templates de emails
            $submissionId = $event->data["submissionId"];
            $submission = Repo::submission()->get((int) $submissionId);
            $publication = Repo::publication()->get((int) $submission->getData('currentPublicationId'));
            $message = $event->message;
            $htmlBody = $message->getHtmlBody();
            $newHtmlBody = str_replace('{$submissionIdCSP}',$publication->getData('submissionIdCSP'),$htmlBody);
            $request = Application::get()->getRequest();
            $context = $request->getContext();
            // Remove envio de email de notificação para editores quando autor faz submissão de nova versão
            $to = $message->getTo();
            $subject = $message->getSubject();
            $recipients = [];
            $templateRevisedVersionNotify = Repo::emailTemplate()->getByKey($context->getId(), RevisedVersionNotify::getEmailTemplateKey());
            if($templateRevisedVersionNotify->getLocalizedData("subject") == $subject){
                $template = $templateRevisedVersionNotify;
                $assignedEditorIds = StageAssignment::withSubmissionIds([$submissionId])
                    ->withStageIds([$submission->getData('stageId')])
                    ->withRoleIds([Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR])
                    ->get();
                $editors = [3, 5, 6]; // Ed. Chefe, Ed. Associado
                $assignedEditorUserIds = $assignedEditorIds
                    ->filter(fn ($a) => in_array($a->userGroupId, $editors))
                    ->pluck('userId')
                    ->all();
                foreach ($to as $t) {
                    $email = $t->getAddress();
                    $recipients[] = $email;
                    $recipientUser = Repo::user()->getByEmail($email, true);
                    if ($recipientUser && in_array($recipientUser->getId(), $assignedEditorUserIds)) {
                        array_pop($recipients);
                        $skipMail = true;
                    }
                }
                if(empty($recipients)){
                    return false;
                }
            }
            $event->message->addCc($context->getData('supportEmail'));
            if ($skipMail) {
                $mailable = new Mailable();
                $mailable->body($template->getLocalizedData('body'))
                // Inclui submissionIdCSP no assunto do email
                    ->subject($template->getLocalizedData('subject').' - CSP '.$publication->getData('submissionIdCSP'))
                    ->from($event->message->getFrom())
                    ->to($recipients)
                    ->cc($context->getData('supportEmail'));
                Mail::send($mailable);
                return false;
            }else{
                $mailable = new Mailable();
                $mailable->body($newHtmlBody)
                // Inclui submissionIdCSP no assunto do email
                    ->subject($event->message->getSubject().' - CSP '.$publication->getData('submissionIdCSP'))
                    ->from($event->message->getFrom())
                    ->to($event->message->getTo())
                    ->cc($context->getData('supportEmail'));
                Mail::send($mailable);
                return false;
            }
        }
    }
}
