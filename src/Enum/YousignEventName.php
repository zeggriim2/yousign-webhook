<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\Enum;

enum YousignEventName: string
{
    // Signature Request
    case SIGNATURE_REQUEST_ACTIVATED = 'signature_request.activated';
    case SIGNATURE_REQUEST_DONE = 'signature_request.done';
    case SIGNATURE_REQUEST_CANCELED = 'signature_request.canceled';
    case SIGNATURE_REQUEST_EXPIRED = 'signature_request.expired';
    case SIGNATURE_REQUEST_REACTIVATED = 'signature_request.reactivated';
    case SIGNATURE_REQUEST_REMINDER_EXECUTED = 'signature_request.reminder_executed';
    case SIGNATURE_REQUEST_AUTOMATIC_REMINDER_EXECUTED = 'signature_request.automatic_reminder_executed';
    case SIGNATURE_REQUEST_APPROVED = 'signature_request.approved';
    case SIGNATURE_REQUEST_DECLINED = 'signature_request.declined';
    case SIGNATURE_REQUEST_REJECTED = 'signature_request.rejected';
    case SIGNATURE_REQUEST_PERMANENTLY_DELETED = 'signature_request.permanently_deleted';

    // Signer
    case SIGNER_DONE = 'signer.done';
    case SIGNER_DECLINED = 'signer.declined';
    case SIGNER_LINK_OPENED = 'signer.link_opened';
    case SIGNER_IDENTIFICATION_SUCCEEDED = 'signer.identification_succeeded';
    case SIGNER_NOTIFIED = 'signer.notified';
    case SIGNER_NOTIFICATION_DELIVERY_FAILED = 'signer.notification_delivery_failed';

    // Approver
    case APPROVER_APPROVED = 'approver.approved';
    case APPROVER_REJECTED = 'approver.rejected';
    case APPROVER_NOTIFIED = 'approver.notified';
    case APPROVER_NOTIFICATION_DELIVERY_FAILED = 'approver.notification_delivery_failed';

    // Electronic Seal
    case ELECTRONIC_SEAL_DONE = 'electronic_seal.done';
    case ELECTRONIC_SEAL_ERROR = 'electronic_seal.error';
}
