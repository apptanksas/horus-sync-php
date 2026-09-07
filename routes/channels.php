<?php

use AppTank\Horus\Horus;
use AppTank\Horus\Core\Config\FeatureName;
use AppTank\Horus\Illuminate\Websocket\QueueActionWebsocket;
use Illuminate\Support\Facades\Broadcast;

$authorize = function ($user, string|int $ownerId): bool {

    if (!Horus::getInstance()->getConfig()->isFeatureEnabled(FeatureName::WEBSOCKET)) {
        return false;
    }

    $userAuth = Horus::getInstance()->getUserAuthenticated();

    if ($userAuth === null) {
        return false;
    }

    $allowedOwnerIds = array_merge([$userAuth->userId], $userAuth->getUserOwnersId());

    if (!in_array($ownerId, $allowedOwnerIds, true) && !in_array((string) $ownerId, array_map('strval', $allowedOwnerIds), true)) {
        return false;
    }

    $checkpointEventId = request()->input('checkpoint_event_id')
        ?? request()->query('checkpoint_event_id')
        ?? request()->header('X-Horus-Checkpoint-Event-Id');

    app(QueueActionWebsocket::class)->replay($ownerId, $checkpointEventId);

    return true;
};

Broadcast::channel(QueueActionWebsocket::CHANNEL . '.{ownerId}', $authorize);