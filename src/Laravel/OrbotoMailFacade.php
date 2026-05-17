<?php

declare(strict_types=1);

namespace Orboto\Mail\Laravel;

use Illuminate\Support\Facades\Facade;
use Orboto\Mail\OrbotoMail;

/**
 * Laravel facade: `OrbotoMail::send([...])` / `OrbotoMail::sendBatch(...)`.
 *
 * @method static \Orboto\Mail\Dto\SendResult       send(array $input)
 * @method static \Orboto\Mail\Dto\SendBatchResult  sendBatch(array $input)
 * @method static \Orboto\Mail\Dto\SendResult       sendTemplate(array $input)
 * @method static \Orboto\Mail\Dto\QuotaState       getQuota()
 *
 * @see OrbotoMail
 */
final class OrbotoMailFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return OrbotoMail::class;
    }
}
