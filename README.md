# PHP-MVC Framework

A lightweight, dependency-aware PHP MVC framework with a small, composable core for routing, views, validation, caching, queues, logging, and more.

## Features

- Application container and service providers
- Routing with controllers and responses
- View rendering engine(s)
- Database models, relationships, and query builder
- Validation rules and manager
- Cache, session, logging, filesystem, email, and queue drivers
- Config support with provider-based bootstrapping

## Requirements

- PHP 8.1+ (recommended)
- Composer

## Expected Client Project Structure

Client applications using the framework are expected to have the following structure:

```
root/
	/config
	/storage
	/controllers
	/models
	/views
```

## Getting Started (High-Level)

1. Define configuration files in `/config`.
2. Create controllers in `/controllers`.
3. Define models in `/models`.
4. Create view templates in `/views`.
5. Add writable directories under `/storage` for logs, cache, and sessions as needed.

## Framework Layout

Core framework code lives in `src/` with modules such as:

- `Application` and `Container`
- `Routing` (Router, Route)
- `View` (View manager and engines)
- `Database` (Model, QueryBuilder, Relationships)
- `Validation` (Rules, Manager)
- `Cache`, `Session`, `Logging`, `Queue`, `Email`, `FileSystem`

## Configuration

Configuration is loaded via providers. Typical settings include:

- Database connections
- Cache and session drivers
- Logging output
- View engine configuration

## Development Notes

- Keep secrets out of version control (use environment-specific config).
- Ensure `/storage` is writable by the web server.
- Use Composer autoloading for your app classes.

## License

Choose and add a license for your project.
