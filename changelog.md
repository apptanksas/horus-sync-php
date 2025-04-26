# Changelog

## v0.5.6
- Fixed migration when an parameter is nullable with entity linked.

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
- Added new workflow to generate pre-release  when is merged changes into a release branch.

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