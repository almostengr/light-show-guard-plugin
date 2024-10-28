# Show Pulse Plugin for Falcon Pi Player

Show Pulse plugin allows you to see and remotely controller what happens with your light show. This
plugin is designed to work with Falcon Pi Player.


## Install Plugin

There are two ways to install the plugin. Using FPP user interface or using PHP's Composer.

### Using FPP

To install the plugin to your Falcon Pi Player, go to the Plugins section of the FPP. Then copy and
paste the URL below to install the plugin.

```sh
https://raw.githubusercontent.com/almostengr/show-pulse-fpp/main/pluginInfo.json
```

### Using Composer

Open a terminal or command prompt window, and go to the plugin directory or other directory of your chosing. Then enter the command

```sh
composer require almostengr/show-pulse-fpp
```

After installing the plugin, you should see "Light Show Pulse" commands on the "Run FPP Command" 
dialog. If you do not see the commands, then restart FPPD or your show player.

## Setup

See the [User Guide](#) for how to configure the plugin.

## Commands

Each command can be added to a playlist or run manually from the FPP user interface. All commands for this 
plugin will start with "Light Show Pulse" in the list of commands.

## Version Numbering

Using the example version number of "5.2024.07.26", "5" 
represents the minimum major Falcon Pi Player version number that the plugin is designed for.
"2024.07.26" represents the date, in YYYY-MM-DD format, that the release was created.
