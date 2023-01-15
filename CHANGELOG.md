
# Change Log

## [0.8.10] - 2023-01-15

### Added

- Added refresh to ws server.

## [0.8.9] - 2023-01-14

### Added

- Updated dependencies.

## [0.8.8] - 2022-01-07

### Added

- Updated dependencies.

## [0.8.7] - 2022-01-07

### Added

- Updated dependencies.

## [0.8.5] - 2022-12-27

### Fixed

- Updated socket conveyor.

## [0.8.4] - 2022-12-26

### Added

- Removed deprecated timer call.

## [0.8.3] - 2022-11-20

### Removed

- Unnecessary dependencies.

## [0.8.2] - 2022-11-20

### Added

- Added commands around plugins functionalities / management.

### Fixed

- Fixed plugin related commands.

## [0.7.11] - 2022-09-25

### Fixed

- The latest version of Socket Conveyor require socket persistence to never be within the same class (channel, listeners and associations). This affected the previous implementation of the WebSocket Command.

## [0.7.10] - 2022-09-25

### Added

- Updated Socket Conveyor version
- Adjustments to accept sqlite as well as other db drivers also accepted by database/illuminate (laravel eloquent).


## [0.7.8] - 2022-09-01

### Added

- Enhanced views - view can be published with command `Kanata\Commands\PublishPluginCommand` and overwritten.
- Added tests to publish command.
- Enhanced helpers - added proxy class that proxy static calls to helper functions, useful to make code elegant and for test mocks. (`Kanata\Services\Helpers`)
