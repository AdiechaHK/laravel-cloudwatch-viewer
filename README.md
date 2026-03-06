# laravel-cloudwatch-viewer

A beautiful, zero-dependency UI for querying and viewing AWS CloudWatch logs directly inside your Laravel application.

> **This is a viewer, not a logger.** Every existing Laravel/CloudWatch package pushes logs *to* CloudWatch. This package reads logs *from* CloudWatch using CloudWatch Insights queries — giving you a searchable, paginated log viewer accessible from your own app URL.

![CloudWatch Viewer UI](https://via.placeholder.com/1200x630/0a0a0f/00ff88?text=CloudWatch+Viewer+Screenshot)

---

## Features

- Dark-theme UI built with IBM Plex Mono / IBM Plex Sans — no frontend build step
- Multi-group selection — query up to 50 CloudWatch log groups in a single Insights query
- Filter by level (ALL / ERROR / WARNING / INFO / DEBUG), message, user ID, request ID, URL, and date range
- Client-side pagination (25 rows per page) with smart ellipsis
- Click any row message to open a full detail modal
- Click a Request ID to re-search filtered by that request
- All selected log groups are validated against your config — no arbitrary group querying

---

## Requirements

- PHP **^8.1**
- Laravel **^10.0 | ^11.0 | ^12.0**
- `aws/aws-sdk-php` **^3.0**
- AWS credentials available to the application (IAM role, environment variables, or `~/.aws/credentials`)

---

## Installation

### 1. Install the package

```bash
composer require adiechahk/laravel-cloudwatch-viewer
```

The service provider is auto-discovered via Laravel's package auto-discovery.

### 2. Publish the config

```bash
php artisan vendor:publish --tag=cloudwatch-viewer-config
```

### 3. Add your log groups

Edit `config/cloudwatch-viewer.php` and add the CloudWatch log groups you want to expose:

```php
'groups' => [
    [
        'name'    => 'Production App',
        'value'   => '/aws/apprunner/my-app/production/application',
        'enabled' => true,
    ],
    [
        'name'    => 'Staging App',
        'value'   => '/aws/apprunner/my-app/staging/application',
        'enabled' => true,
    ],
],
```

### 4. Configure IAM permissions

Attach the following permissions to your application's IAM role or user (see [IAM Permissions](#iam-permissions) below).

### 5. Visit the viewer

```
https://yourapp.com/cloudwatch-logs
```

---

## Configuration

All configuration lives in `config/cloudwatch-viewer.php` after publishing.

| Key | Default | Description |
|-----|---------|-------------|
| `route_prefix` | `cloudwatch-logs` | URI prefix for the viewer. Change via `CLOUDWATCH_VIEWER_PREFIX` env var. |
| `middleware` | `['web']` | Array of middleware applied to all viewer routes. Add `auth` or custom middleware here. |
| `region` | `us-east-1` | AWS region. Reads from `AWS_DEFAULT_REGION` env var. |
| `query_limit` | `500` | Max log entries returned per query. CloudWatch Insights max is 10,000. |
| `default_hours` | `24` | Hours to look back when no date range is specified. |
| `groups` | `[]` | Array of log group definitions (see below). |

### Log group definition

```php
[
    'name'    => 'Friendly Display Name',  // Shown in the sidebar UI
    'value'   => '/aws/your/log/group',    // Exact CloudWatch log group path
    'enabled' => true,                      // false to hide without removing
]
```

---

## IAM Permissions

Your application needs the following IAM permissions to run CloudWatch Insights queries:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "logs:StartQuery",
                "logs:GetQueryResults",
                "logs:DescribeLogGroups"
            ],
            "Resource": "*"
        }
    ]
}
```

For tighter security, restrict the `Resource` to specific log group ARNs:

```json
"Resource": [
    "arn:aws:logs:us-east-1:123456789012:log-group:/aws/apprunner/my-app/*"
]
```

---

## Customization

### Restrict access with middleware

Edit `config/cloudwatch-viewer.php`:

```php
'middleware' => ['web', 'auth', 'can:view-cloudwatch-logs'],
```

### Change the URL prefix

```php
// config/cloudwatch-viewer.php
'route_prefix' => 'admin/logs',

// or via .env
CLOUDWATCH_VIEWER_PREFIX=admin/logs
```

### Customize the view

Publish the view to your application:

```bash
php artisan vendor:publish --tag=cloudwatch-viewer-views
```

This copies the view to `resources/views/vendor/cloudwatch-viewer/index.blade.php` where you can freely modify it.

---

## How It Works

1. The UI sends a `GET /cloudwatch-logs/fetch` request with your selected filters.
2. The controller validates that all requested log groups are in your config (no arbitrary group access).
3. A CloudWatch Insights query is constructed and submitted via `StartQuery`.
4. The controller polls `GetQueryResults` until the query completes (up to 10 seconds).
5. Results are flattened from CloudWatch's field/value pair format into plain objects and returned as JSON.
6. The UI paginates and renders the results client-side.

### Expected log structure

The package queries these fields by default (matching Monolog's `JsonFormatter` output):

```
@timestamp, @logStream, level_name, message,
context.request_id, context.user_id, context.url,
context.method, context.ip, context.environment
```

It works with any log format that writes these fields to CloudWatch. Fields that don't exist in a log entry are simply empty.

---

## License

MIT — see [LICENSE](LICENSE).
