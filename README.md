# The Movie DB Command

Download poster and meta info for movies and tv show from *The Movie DB* for movies and *The TVDB* for tv shows.

# Requirements

Register on **The Movie DB** and **The TVDb** and obtain un API key.

# Install

```bash
cp app/config/config.dist.yml app/config/config.yml
composer install
```

# Instrucctions

```bash
# List all commands
./console.php list

# Covered movies
./console.php movie:covered [PATHS_OR_FOLDERS_WITH_MOVIES]

# Covered tv shows
./console.php tvshow:covered [PATHS_OR_FOLDERS_WITH_TV_SHOWS]
```
