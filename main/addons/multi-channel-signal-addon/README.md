# Multi-Channel Signal Addon

A modular addon for automatically forwarding messages from external channels (Telegram, APIs, websites, RSS feeds) into the system as signals.

## Installation

1. The addon is located in `main/addons/multi-channel-signal-addon/`
2. Run migrations: `php artisan migrate`
3. Register the addon service provider in `config/app.php`

## Features

- Telegram Bot API integration
- Telegram MTProto (user account) integration
- REST API webhook integration
- Web scraping support
- RSS/Atom feed integration
- Automatic message parsing
- Signal auto-creation
- Admin review interface

## Usage

See documentation in `specs/active/multi-channel-signal-addon/`


