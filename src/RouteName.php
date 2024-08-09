<?php

namespace AppTank\Horus;

enum RouteName: string
{
    case GET_MIGRATIONS = "horus.migrations";
    case POST_SYNC_QUEUE_ACTIONS = "horus.sync.queue.actions";
}