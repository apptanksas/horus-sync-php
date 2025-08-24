# Changelog

# v0.12.2
- Fixed validate parent entity owner id in sync queue actions.
- Added context info in bad request exception by entity max count exceeded.

# v0.12.1
- Added validate to delete actions in restriction to queue actions.

# v0.12.0
- Added configuration to disabled features.

# v0.11.22
- Fix generate data sync job dependency with entity mapper.

# v0.11.21
- Fix sort entities generate data sync job.

# v0.11.20
- Fixed cache search entities.
- Added group entities by owner and sync queue actions.

# v0.11.19
- Fixed issue in queue actions ownership with entities granted.

# v0.11.18
- Added new method in EntityDependsOne to get parent parameter name.

# v0.11.17
- Added user id parameter in get entities hashes endpoint.
- Added user id parameter in validate entities data endpoint.

# v0.11.16
- Fixed validate entity hashes with entity granted.

## v0.11.15
- Implemented upset in eloquent entity repository to update or insert entities.

## v0.11.14
- Fix issue in response format in get actions endpoint.

## v0.11.13
- Fix issue in get queue actions endpoint where retrieve the queue actions of the another users not granted.

## v0.11.12
- Fix prune files with query params url.
- Fix issue when get entity parent owner in sync actions with inserts.

## v0.11.11
- Fix issue when insert a new entity without validation the real owner

## v0.11.10
- Fix issue in entity mapping when the map is nested.

## v0.11.9
- Fix message in operation not permitted exception.

## v0.11.8
- Add information about User auth in operation not permitted exception

## v0.11.7

- Fix generate data sync job.

## v0.11.6

- Flattened the entities data in the synchronization data to make it easier to process.

## v0.11.5

- The file generated in the synchronization data is no longer of type application/json, but is now of type application/nd-json so that it can be processed line by line.

## v0.11.4

- Fix entity data with attributes relations.

## v0.11.3

- Optimized the query to get entities data.

## v0.11.2

- Fix save sync job repository.

## v0.11.1

- Fix endpoint start sync to receive the after parameter to filter the entities that are going to be updated.

## v0.11.0

- Added endpoint to start sync and generate sync data to download.
- Deprecated endpoint to get entities data.

## v0.10.2

- Fixed issue a user wanted to make operations with a primary entity that is not shared.

## v0.10.1

- Added batch insert and delete data processing.

## v0.10.0

- Added cache to get readable entities.

## v0.9.6

- Fixed validation columns in migration entities.

## v0.9.5

- Fixed sorting insert operation in entity repository.

## v0.9.4

- Fixed issue hashing with float values.

## v0.9.3

- Change bind entity repository dependency.

## v0.9.2

- Added validation entities mapper in eloquent entity repository.

## v0.9.1

- Added method setupOnSharedEntities to setup the shared entities when Horus needs it.

## v0.9.0

- Added support to shared entities.
- update prefix api routes to horus/v1.

## v0.8.0

- Added method to push actions in horus queue action client.

## v0.7.0

- Added support to index columns in entity definition.
- Fixed issue to insert data with timestamp null.

## v0.6.0

- Added new column in queue action to store the entity id.
- Added HorusQueueAction to query the queue action.

## v0.5.6

- Fixed migration when an parameter is nullable with entity linked.
- Fixed hashing data with null and boolean values.

## v0.5.5

- Turn off by default delete on cascade in parameter types.

## v0.5.4

- Fixed applying constraint in parameter custom.

## v0.5.3

- Added support to use UUID in readable entity.

## v0.5.2

- Fixed applying constraint in parameter custom.

## v0.5.1

- Fixed option in create entity synchronizable using artisan command.

## v0.5.0

- A new entity restriction is available [FilterEntityRestriction] to filter the data that can be downloaded to client based on certain criteria.
- Added new parameter type to defines a custom type with own regex.

## v0.4.0

- Added support to setup if a foreing entity is delete on cascade.

## v0.3.9

- Fixed when its updating entity without changes.

## v0.3.8

- Fixed entity parsing when executing a delete operation.

## v0.3.7

- Fixed update entity when it has its receives a timestamp.

## v0.3.6

- Fixed dispatch sync events.

## v0.3.5

- Order fixed when dropping entity tables

## v0.3.4

- Data type fixed in entity definition

## v0.3.3

- Fix insert parameter type as timestamp.

## v0.3.2

- Add report when is bad request in sync data endpoint.

## v0.3.1

- Fixed delete release in tag_release workflow.

## v0.3.0

- Added feature to add entity restrictions where it can define a max count of entities.
- Fixed parse expiration days param in prune upload files command.

## v0.2.1

- Fix deleting release in tag_release workflow.

## v0.2.0

- Added support for upload files.
- Added method to setup prefix tables.

## v0.1.8

## v0.1.7

- Added remove releases candidates in github when a release is made.
- Fixed release stable version when a release is made.

## v0.1.6

- Added new workflow to generate pre-release when is merged changes into a release branch.

## v0.1.5

- Added notification to packagist when a release is made on github.
- Updated tag releases workflow to automatically create the release on github.

## v0.1.4

### Fixed

- Tag releases workflow to create tag from release version

## v0.1.3

## v0.1.2

## v0.1.1

## v0.1.0

- Initial release