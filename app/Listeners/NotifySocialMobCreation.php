<?php

namespace App\Listeners;

use App\Events\SocialMobCreated;
use Illuminate\Support\Facades\Http;

class NotifySocialMobCreation extends WebHookNotificationEventListener
{
    public function handle(SocialMobCreated $event)
    {
        if ($this->isWithinWebHookNotificationWindow()
            && config('webhooks.created_today')
            && today()->isSameDay($event->socialMob->date)) {

            Http::post(config('webhooks.created_today'), $event->socialMob->toArray());
        }
    }
}
