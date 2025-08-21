# Changelog

## v1.1.0

* **Added Laravel 12 Support**

## v1.0.2

### Fixed
* Rate limits are now respected when using queue workers without the --once option.

### Improved
* Added logging of queue names for test jobs created by the `queue:fill` command for better debugging.

### Changed
* Throttle group definitions in config.queue.throttle now support comma-separated queue names, aligning with Laravel's standard conventions.

---
## v1.0.1

### Fixed
* Correctly handle empty/absent `throttle` value in the `config/queue.php` configuration file.

---
## v1.0.0

Initial commit.
