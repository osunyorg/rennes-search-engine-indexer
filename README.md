# Rennes Metropole - Search engine indexer PHP script

This repository is part of the Rennes Métropole Osuny project. It is intended to be included as a submodule dependency
on the various Rennes Métropole Osuny Github repositories. It provides a way to trigger Elasticsearch indexing of Osuny
Frontmatter content via a Github action.

## Project technologies

* Composer 2 - Dependency management
* Symfony Hub API (External API integration)
* DDEV - Local development environment

## Dependencies

| Package                  | Version | Description                                      |
|--------------------------|---------|--------------------------------------------------|
| guzzlehttp/guzzle        | 7.9.2   | PHP HTTP client library                          |
| symfony/dotenv           | 7.2.0   | Registers environment variables from a .env file |
| spatie/yaml-front-matter | 2.1.0   | A to the point yaml front matter parser          |

## Server requirements

* PHP 8.3 - Core programming language
* NGINX - Web server (via DDEV)

## Architecture

The project architecture is documented in a PlantUML diagram located in the `doc/architecture.puml` file. This diagram
shows the main components of the application, their relationships, and the API interactions.

```plantuml
::include{file=docs/architecture.puml}
```

## Local development requirements

### DDEV

**DDEV 1.21 or upper** is required: This project comes with an optimized DDEV configuration to handle automated Docker
environment for local development. You have to install it from
the [official repo](https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/#gitpod).

## Install the project

```bash
ddev start
```

## Environment variables

The `index.php` script needs the following environments variables in order to run:

* `API_URL`: API endpoint of Symfony Hub application
* `API_KEY`: API key in order to authenticate to Symfony Hub application

## Configuration

In all your website your need to setup `config/_default/indexer.yaml` with content like that:

```yml
content_dirs:
  - /content/fr/pages
exclude_dirs:
  - /content/fr/pages/art-dans-la-ville
has_thematic: true
taxonomies:
  - name: 'Rubrique'
    field_name: 'category'
    
# Example "ici" index
#has_thematic: false
#taxonomies:
#  - name: Catégories
#    field_name: 'category'
#  - name: Formats:
#    field_name: 'format'

# Example "app" index
#has_thematic: true
#taxonomies:
#  - name: Rubrique:
#    field_name: 'category'
```

## Mappings

The file `config/mapping.yaml` contains mappings of website with label associated.

