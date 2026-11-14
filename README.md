# WorldManager

WorldManager is a powerful, modern and easy-to-use world management plugin for PocketMine-MP (5.36+).

Built with modern standards and performance in mind, WorldManager provides an intuitive way to create, load, unload, duplicate, rename, delete and manage your worlds with just a few commands.

## Features

- **World Management** – Create, load, unload, rename and delete worlds.
- **World Duplication** – Easily clone existing worlds.
- **World Teleportation** – Teleport yourself or other players between worlds.
- **World Information** – View detailed information about your worlds.
- **World Presets** – Supports Presets.
- **Vanilla Game Rules** – Configure individual game rules for each world.
- **Multilingual Support** – English and German translations included.
- **Performance Friendly** – Large world operations run in the background.

## Getting Started

1. Download WorldManager.
2. Put the plugin into your `plugins/` folder.
3. Start your server.

Use `/wm` or `/worldmanager` to see all available commands.

### Quick Example

```text
/wm create arena flat
/wm tp arena
/wm duplicate arena arena_backup
/wm setlobby
/gamerule pvp false
```

## Commands

| Command | Description |
|---|---|
| `create <name> [generator] [seed] [difficulty] [preset]` | Creates a new world. |
| `load <world>` | Loads a world. |
| `unload <world>` | Unloads a world. |
| `tp <world> [player]` | Teleports to a world. |
| `duplicate <world> <newName>` | Duplicates a world. |
| `rename <world> <newName>` | Renames a world. |
| `setlobby` | Sets the current world as the server lobby. |
| `delete <world> [confirm]` | Deletes a world. |
| `list` | Lists all worlds. |
| `info [world]` | Shows information about a world. |

### Game Rules

```text
/gamerule <rule> [true|false]
```

| Rule | Default |
|---|---|
| `dodaylightcycle` | `true` |
| `keepinventory` | `false` |
| `pvp` | `true` |
| `showdeathmessages` | `true` |
| `domobloot` | `true` |
| `tntexplosiondropdecay` | `false` |
| `drowningdamage` | `true` |
| `falldamage` | `true` |
| `firedamage` | `true` |
| `naturalregeneration` | `true` |
| `showcoordinates` | `true` |
| `showdaysplayed` | `false` |
| `showtags` | `true` |

## Languages

WorldManager currently includes:

- English (`en`)
- German (`de`)

Change your preferred language in `plugin_data/WorldManager/config.yml`:

```yaml
language: en
```

Custom translations are supported as well.

## AI Usage

AI tools were used during the development of this project to assist with certain development tasks, code improvements and documentation.

All generated or assisted content was reviewed and adapted as needed.