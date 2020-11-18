# Overview

This repository is where Telerehabilitation Aoo implemented in Laravel and using docker-compose and single sign on with keycloak.

# Maintainer

* Web Essentials Co., Ltd

# Required Dependencies

* MySQL >= 8
* PHP >= 7.3

* [Git](https://git-scm.com/)
* [Composer](https://getcomposer.org/)
* [Docker](https://docs.docker.com/install/) >= v17.12
* [docker-compose](https://docs.docker.com/compose/install/#install-compose) >= 1.12

# Local environment with Docker


# Run code check style with `phpcs`

* make sure you are using `phpcs` version `>=3.3.1`. If not, run `~/dotfiles/setup.sh`

    ```bash
    cd ~/dev/docker-projects/hiv/admin-service
    phpcs
    ```

* References

    > [https://github.com/standard/eslint-config-standard](https://github.com/standard/eslint-config-standard)

* Configuration with IDE

    > [https://eslint.org/docs/user-guide/integrations](https://eslint.org/docs/user-guide/integrations)

# Checklist after Live

* Run migration command, ref. `config/ansible/roles/deploy/tasks/migration.yml`
* Run indexing command, ref. `config/ansible/roles/deploy/tasks/re-indexing.yml`
