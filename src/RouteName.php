<?php

namespace AppTank\Horus;

/**
 * Enum representing the various route names used in the application.
 */
enum RouteName: string
{
    /**
     * Route for retrieving migration information.
     */
    case GET_MIGRATIONS = "horus.migrations";

    /**
     * Route for posting actions to the synchronization queue.
     */
    case POST_SYNC_QUEUE_ACTIONS = "horus.sync.queue.actions";

    /**
     * Route for retrieving actions from the synchronization queue.
     */
    case GET_SYNC_QUEUE_ACTIONS = "horus.get.sync.queue.actions";

    /**
     * Route for retrieving data entities.
     */
    case GET_DATA_ENTITIES = "horus.data.entities";

    /**
     * Route for retrieving entity data based on search criteria.
     */
    case GET_ENTITY_DATA = "horus.search.entities";

    /**
     * Route for retrieving hashes of entities.
     */
    case GET_ENTITY_HASHES = "horus.get.entity.hashes";

    /**
     * Route for retrieving the last action in the synchronization queue.
     */
    case GET_SYNC_QUEUE_LAST_ACTION = "horus.get.sync.queue.last.action";

    /**
     * Route for validating data.
     */
    case POST_VALIDATE_DATA = "horus.post.validate.data";

    /**
     * Route for validating hashing of data.
     */
    case POST_VALIDATE_HASHING = "horus.post.validate.hashing";
}
