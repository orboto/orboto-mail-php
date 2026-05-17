<?php

declare(strict_types=1);

namespace Orboto\Mail\Exception;

/**
 * Thrown by `send()` when the recipient is on the customer's
 * suppression list (hard-bounce / complaint / manual). The 400 body's
 * reason is `recipient_suppressed`.
 *
 * Catch this to skip the send + log without alarming — the API
 * deliberately refused the send to protect sending reputation.
 */
class SuppressedRecipientException extends OrbotoMailException
{
}
