# webmention-sender

A CLI PHP script that reads an RSS feed, fetches each post page to discover outgoing links, and sends [webmentions](https://www.w3.org/TR/webmention/) to any target that supports them. A state file prevents re-sending on subsequent runs.

## Installation

Clone or download the repository to a directory **above your webroot** (so it cannot be accessed via HTTP):

```sh
git clone https://github.com/thomasuebel/webmention-sender.git
cd webmention-sender
```

Or [download the latest release](https://github.com/thomasuebel/webmention-sender/releases) and unzip it.

Then create your config file:

```sh
cp config.example.php config.php
```

Edit `config.php` and set at minimum your feed URL and a path for the state file:

```php
return new Config(
    feedUrl:   'https://your-blog.com/index.xml',
    stateFile: __DIR__ . '/webmentions-sent.json',
);
```

Run it:

```sh
php webmention-sender.php
```

That's it. The state file is created automatically on first run.

## Requirements

- PHP 8.2+
- Extensions: `curl`, `dom`, `simplexml` (standard on most hosts)
- Composer (development/testing only — not required to run the script)

## Usage

### Cron

```
0 8 * * * php /path/to/webmention-sender.php >> /path/to/webmention-sender.log 2>&1
```

### Dry run

Set `dryRun: true` in `config.php` to discover endpoints and log what would be sent without actually sending anything or updating state.

## Configuration

| Option           | Type      | Default                  | Description                                                                 |
|------------------|-----------|--------------------------|-----------------------------------------------------------------------------|
| `feedUrl`        | `string`  | —                        | URL of your RSS feed                                                        |
| `stateFile`      | `string`  | —                        | Path to the JSON file tracking sent webmentions                             |
| `userAgent`      | `string`  | `WebmentionSender/1.0`   | User-Agent sent with outgoing requests                                      |
| `requestTimeout` | `int`     | `10`                     | Total transfer timeout in seconds                                           |
| `connectTimeout` | `int`     | `5`                      | Connection timeout in seconds (should be ≤ `requestTimeout`)               |
| `lookbackDays`   | `?int`    | `null`                   | Only process posts published within this many days; `null` processes all    |
| `dryRun`         | `bool`    | `false`                  | Log intended actions without sending                                        |
| `verbose`        | `bool`    | `false`                  | Emit debug-level log output                                                 |

## Tests

```sh
./test
```

Requires Composer. Dependencies are installed automatically on first run.

## License

MIT
