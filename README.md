# Rennes Metropole - Search engine indexer PHP script

This repository is part of the Rennes Métropole Osuny project. It is intended to be included as a submodule dependency
on the various Rennes Métropole Osuny Github repositories. It provides a way to trigger Elasticsearch indexing of Osuny
content via a Github action.

## Project technologies

* PHP 8.3

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
* `CONTENT_DIR`: Project directory containing Osuny pages data


