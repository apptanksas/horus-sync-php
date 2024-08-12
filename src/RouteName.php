<?php

namespace AppTank\Horus;

enum RouteName: string
{
    case GET_MIGRATIONS = "horus.migrations";
    case POST_SYNC_QUEUE_ACTIONS = "horus.sync.queue.actions";

    case GET_SYNC_QUEUE_ACTIONS = "horus.get.sync.queue.actions";

    case GET_DATA_ENTITIES = "horus.data.entities";

    case SEARCH_ENTITIES = "horus.search.entities";

    case GET_HASHES_ENTITY = "horus.get.hashes.entity";
    case GET_SYNC_QUEUE_LAST_ACTION = "horus.get.sync.queue.last.action";

    case POST_VALIDATE_DATA = "horus.post.validate.data";

    case POST_VALIDATE_HASHING = "horus.post.validate.hashing";

}