# CrazeBot-API
Provides an API for CrazeBot in JSON format

## Endpoints

Automatically available:

- `botinfo` contains selected entries from global configuration
- `<channel>/commands` has custom commands with any restrictions, schedule or repeat
- `<channel>/autoreplies` shows custom text triggers with id and replies
- `<channel>/filters` shows filter settings for a channel
- `<channel>/settings` contains saved settings for a channel 
- `<channel>/users` contains all users stored in the robot

Disabled by default:

- `<channel>/complete_config` dumps the entire channel configuration

## Installing
1. Unpack or clone all files to a webserver
2. Rename or copy `config.example.php` to `config.php`
3. Edit `config.php` and specify where CrazeBot is installed
