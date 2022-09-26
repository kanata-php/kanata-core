
# Change Log

## [0.7.10] - 2022-09-25

### Added

- Updated Socket Conveyor version
- Adjustments to accept sqlite as well as other db drivers also accepted by database/illuminate (laravel eloquent).


## [0.7.8] - 2022-09-01

### Added

- Enhanced views - view can be published with command `Kanata\Commands\PublishPluginCommand` and overwritten.
- Added tests to publish command.
- Enhanced helpers - added proxy class that proxy static calls to helper functions, useful to make code elegant and for test mocks. (`Kanata\Services\Helpers`)
