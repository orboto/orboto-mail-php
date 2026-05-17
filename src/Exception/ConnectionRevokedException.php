<?php

declare(strict_types=1);

namespace Orboto\Mail\Exception;

/**
 * Thrown when a 401 comes back with reason='connection_revoked' — the
 * customer's OAuth-issued API key has been withdrawn from the linked
 * application via the OCP Customer-Portal. Catch this separately to
 * surface the right disable-this-integration UX rather than a generic
 * "auth failed" message.
 */
class ConnectionRevokedException extends OrbotoMailException
{
}
