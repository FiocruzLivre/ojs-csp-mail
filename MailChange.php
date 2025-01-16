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
use PKP\mail\mailables\ReviewCompleteNotifyEditors;
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
            $symfonyMessage = $event->data["message"]->getSymfonyMessage();
            $symfonyMessage->html($newHtmlBody);
            $request = Application::get()->getRequest();
            $context = $request->getContext();
            // Remove envio de email de notificação para editores quando autor faz submissão de nova versão
            $message = $event->message;
            $data = $event->data;
            $to = $message->getTo();
            $subject = $message->getSubject();
            $recipients = [];
            $submission = Repo::submission()->get((int) $data["submissionId"]);
            $templateRevisedVersionNotify = Repo::emailTemplate()->getByKey($context->getId(), RevisedVersionNotify::getEmailTemplateKey());
            $templateReviewCompleteNotifyEditors = Repo::emailTemplate()->getByKey($context->getId(), ReviewCompleteNotifyEditors::getEmailTemplateKey());
            if($templateRevisedVersionNotify->getLocalizedData("subject") == $subject){
                $template = $templateRevisedVersionNotify;
                $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
                $assignedEditorIds = $stageAssignmentDao->getEditorsAssignedToStage($data["submissionId"], $submission->getData('stageId'));
                $editors = [3, 5, 6]; // Ed. Chefe, Ed. Associado
                $i = 0;
                foreach ($to as $t) {
                    $email = $t->getAddress();
                    $recipients[] = $email;
                    if (in_array($assignedEditorIds[$i]->getData('userGroupId'), $editors)) {
                            array_pop($recipients);
                            $skipMail = true;
                    }
                    $i++;
                }
                if(empty($recipients)){
                    return false;
                }
            }

            // Remove envio de email para editores e secretaria, quando uma avaliação é concluída.
            if ($templateReviewCompleteNotifyEditors->getLocalizedData("subject") == $subject) {
                $template = $templateReviewCompleteNotifyEditors;
                $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
                $assignedEditorIds = $stageAssignmentDao->getEditorsAssignedToStage($data["submissionId"], $submission->getData('stageId'));
                $editors = [3, 19]; // Ed. Chefe e Secretaria
                foreach ($to as $t) {
                    $email = $t->getAddress();
                    $recipients[] = $email;
                    $user = Repo::user()->getByEmail($email, true);
                    foreach ($assignedEditorIds as $assignedEditorId) {
                        if ($user->getId() == $assignedEditorId->getData('userId') && in_array($assignedEditorId->getData('userGroupId'), $editors)){
                            array_pop($recipients);
                            $skipMail = true;
                        }
                    }
                }
                if(empty($recipients)){
                    return false;
                }
            }
            $event->message->addCc('contato@fiocruz.br');
            $event->message->addCc('livia.carolina@fiocruz.br');
            if ($skipMail) {
                $mailable = new Mailable();
                $mailable->body($template->getLocalizedData('body'))
                    ->subject($template->getLocalizedData('subject'))
                    ->from('cadernos@fiocruz.br')
                    ->to($recipients)
                    ->cc('contato@fiocruz.br')
                    ->cc('livia.carolina@fiocruz.br');
                Mail::send($mailable);
                return false;
            }else{
                $mailable = new Mailable();
                $mailable->body($event->message->getHtmlBody())
                    ->subject($event->message->getSubject())
                    ->from('cadernos@fiocruz.br')
                    ->to($event->message->getTo())
                    ->cc('contato@fiocruz.br')
                    ->cc('livia.carolina@fiocruz.br');
                Mail::send($mailable);
                return false;
            }
        }
    }
}
