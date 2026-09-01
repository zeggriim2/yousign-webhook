<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\Enum;

/**
 * Catalogue of the webhook events documented by Yousign (YouTrust).
 *
 * New events may be added by Yousign without a major API version bump, so the
 * bundle never parses an event name through `from()`: an unknown name simply
 * yields `null` and the raw payload stays available.
 *
 * @see https://developers.youtrust.com/docs/webhooks
 */
enum YousignEvent: string
{
    case SIGNATURE_REQUEST_ACTIVATED = 'signature_request.activated';
    case SIGNATURE_REQUEST_APPROVED = 'signature_request.approved';
    case SIGNATURE_REQUEST_AUTOMATIC_REMINDER_EXECUTED = 'signature_request.automatic_reminder_executed';
    case SIGNATURE_REQUEST_CANCELED = 'signature_request.canceled';
    case SIGNATURE_REQUEST_DECLINED = 'signature_request.declined';
    case SIGNATURE_REQUEST_DELETED = 'signature_request.deleted';
    case SIGNATURE_REQUEST_DONE = 'signature_request.done';
    case SIGNATURE_REQUEST_EXPIRED = 'signature_request.expired';
    case SIGNATURE_REQUEST_PAUSED = 'signature_request.paused';
    case SIGNATURE_REQUEST_PERMANENTLY_DELETED = 'signature_request.permanently_deleted';
    case SIGNATURE_REQUEST_REACTIVATED = 'signature_request.reactivated';
    case SIGNATURE_REQUEST_REJECTED = 'signature_request.rejected';
    case SIGNATURE_REQUEST_REMINDER_EXECUTED = 'signature_request.reminder_executed';
    case SIGNATURE_REQUEST_RESUMED = 'signature_request.resumed';

    case SIGNER_DECLINED = 'signer.declined';
    case SIGNER_DONE = 'signer.done';
    case SIGNER_ERROR = 'signer.error';
    case SIGNER_IDENTIFICATION_BLOCKED = 'signer.identification_blocked';
    case SIGNER_IDENTIFICATION_EXPIRED = 'signer.identification_expired';
    case SIGNER_IDENTIFICATION_FAILED = 'signer.identification_failed';
    case SIGNER_IDENTIFICATION_SUCCEEDED = 'signer.identification_succeeded';
    case SIGNER_IDENTITY_SAVED = 'signer.identity_saved';
    case SIGNER_LINK_OPENED = 'signer.link_opened';
    case SIGNER_NOTIFICATION_DELIVERY_FAILED = 'signer.notification_delivery_failed';
    case SIGNER_NOTIFIED = 'signer.notified';
    case SIGNER_SENDER_CONTACTED = 'signer.sender_contacted';

    case APPROVER_APPROVED = 'approver.approved';
    case APPROVER_NOTIFICATION_DELIVERY_FAILED = 'approver.notification_delivery_failed';
    case APPROVER_NOTIFIED = 'approver.notified';
    case APPROVER_REJECTED = 'approver.rejected';

    case CONTACT_CREATED = 'contact.created';
    case USER_COMPLETED = 'user.completed';

    case ELECTRONIC_SEAL_DONE = 'electronic_seal.done';
    case ELECTRONIC_SEAL_ERROR = 'electronic_seal.error';

    case VERIFICATION_BANK_ACCOUNT_CONNECTION_DONE = 'verification.bank_account_connection.done';
    case VERIFICATION_BANK_ACCOUNT_DONE = 'verification.bank_account.done';
    case VERIFICATION_BANK_ACCOUNT_LOOKUP_DONE = 'verification.bank_account_lookup.done';
    case VERIFICATION_COMPANY_DONE = 'verification.company.done';
    case VERIFICATION_IDENTITY_DOCUMENT_DONE = 'verification.identity_document.done';
    case VERIFICATION_IDENTITY_VIDEO_DONE = 'verification.identity_video.done';
    case VERIFICATION_PROOF_OF_ADDRESS_DONE = 'verification.proof_of_address.done';
    case VERIFICATION_WATCHLIST_DONE = 'verification.watchlist.done';

    case MONITORING_NATURAL_PERSON_ACTIVATED = 'monitoring.natural_person.activated';
    case MONITORING_NATURAL_PERSON_CANCELED = 'monitoring.natural_person.canceled';
    case MONITORING_NATURAL_PERSON_INCONCLUSIVE = 'monitoring.natural_person.inconclusive';
    case MONITORING_NATURAL_PERSON_UPDATED = 'monitoring.natural_person.updated';

    case DOCUMENT_ANALYSIS_DONE = 'document_analysis.done';

    case WORKFLOW_ACTION_GROUP_BLOCKED = 'workflow_action_group.blocked';
    case WORKFLOW_ACTION_GROUP_DONE = 'workflow_action_group.done';
    case WORKFLOW_ACTION_GROUP_STARTED = 'workflow_action_group.started';
    case WORKFLOW_SESSION_BLOCKED = 'workflow_session.blocked';
    case WORKFLOW_SESSION_DONE = 'workflow_session.done';
    case WORKFLOW_SESSION_STARTED = 'workflow_session.started';

    case APPLICANT_NOTIFIED = 'applicant.notified';
    case APPLICANT_PROCESSED = 'applicant.processed';
    case APPLICANT_SESSION_BLOCKED = 'applicant.session_blocked';

    /**
     * Resource the event belongs to, e.g. "signature_request" for
     * "signature_request.done" or "verification" for
     * "verification.company.done".
     */
    public function group(): string
    {
        return substr($this->value, 0, (int) strpos($this->value, '.'));
    }

    /**
     * Last segment of the event name, e.g. "done" for "signer.done".
     */
    public function action(): string
    {
        return substr($this->value, (int) strrpos($this->value, '.') + 1);
    }
}
