#!/bin/sh
set -eu

# version_greater A B returns whether A > B
version_greater() {
    [ "$(printf '%s\n' "$@" | sort -t '.' -n -k1,1 -k2,2 -k3,3 -k4,4 | head -n 1)" != "$1" ]
}

# return true if specified directory is empty
directory_empty() {
    [ -z "$(ls -A "$1/")" ]
}

run_as() {
    if [ "$(id -u)" = 0 ]; then
        su -p "$user" -s /bin/sh -c "$1"
    else
        sh -c "$1"
    fi
}

# usage: file_env VAR [DEFAULT]
#    ie: file_env 'XYZ_DB_PASSWORD' 'example'
# (will allow for "$XYZ_DB_PASSWORD_FILE" to fill in the value of
#  "$XYZ_DB_PASSWORD" from a file, especially for Docker's secrets feature)
file_env() {
    local var="$1"
    local fileVar="${var}_FILE"
    local def="${2:-}"
    local varValue=$(env | grep -E "^${var}=" | sed -E -e "s/^${var}=//")
    local fileVarValue=$(env | grep -E "^${fileVar}=" | sed -E -e "s/^${fileVar}=//")
    if [ -n "${varValue}" ] && [ -n "${fileVarValue}" ]; then
        echo >&2 "error: both $var and $fileVar are set (but are exclusive)"
        exit 1
    fi
    if [ -n "${varValue}" ]; then
        export "$var"="${varValue}"
    elif [ -n "${fileVarValue}" ]; then
        export "$var"="$(cat "${fileVarValue}")"
    elif [ -n "${def}" ]; then
        export "$var"="$def"
    fi
    unset "$fileVar"
}

if expr "$1" : "apache" 1>/dev/null; then
    if [ -n "${APACHE_DISABLE_REWRITE_IP+x}" ]; then
        a2disconf remoteip
    fi
fi

uid="$(id -u)"
gid="$(id -g)"
if [ "$uid" = '0' ]; then
    case "$1" in
        apache2*)
            user="${APACHE_RUN_USER:-www-data}"
            group="${APACHE_RUN_GROUP:-www-data}"

            # strip off any '#' symbol ('#1000' is valid syntax for Apache)
            user="${user#'#'}"
            group="${group#'#'}"
            ;;
        *) # php-fpm
            user='www-data'
            group='www-data'
            ;;
    esac
else
    user="$uid"
    group="$gid"
fi

# If another process is syncing the html folder, wait for
# it to be done, then escape initalization.
(
    if ! flock -n 9; then
        # If we couldn't get it immediately, show a message, then wait for real
        echo "Another process is initializing Nextcloud. Waiting..."
        flock 9
    fi

    image_version="$(php -r 'require "/usr/src/nextcloud/version.php"; echo implode(".", $OC_Version);')"

    echo "Initializing nextcloud $image_version ..."

    if [ "$(id -u)" = 0 ]; then
        rsync_options="-rlDog --chown $user:$group"
    else
        rsync_options="-rlD"
    fi

    rsync $rsync_options --delete --exclude '' /usr/src/nextcloud/ /var/www/html/

    echo "New nextcloud instance"

    file_env NEXTCLOUD_ADMIN_PASSWORD
    file_env NEXTCLOUD_ADMIN_USER

    if [ -n "${NEXTCLOUD_ADMIN_USER+x}" ] && [ -n "${NEXTCLOUD_ADMIN_PASSWORD+x}" ]; then
        # shellcheck disable=SC2016
        install_options='-n --admin-user "$NEXTCLOUD_ADMIN_USER" --admin-pass "$NEXTCLOUD_ADMIN_PASSWORD"'
        if [ -n "${NEXTCLOUD_DATA_DIR+x}" ]; then
            # shellcheck disable=SC2016
            install_options=$install_options' --data-dir "$NEXTCLOUD_DATA_DIR"'
        fi

        file_env MYSQL_DATABASE
        file_env MYSQL_PASSWORD
        file_env MYSQL_USER
        file_env POSTGRES_DB
        file_env POSTGRES_PASSWORD
        file_env POSTGRES_USER

        install=false
        if [ -n "${SQLITE_DATABASE+x}" ]; then
            echo "Installing with SQLite database"
            # shellcheck disable=SC2016
            install_options=$install_options' --database-name "$SQLITE_DATABASE"'
            install=true
        elif [ -n "${MYSQL_DATABASE+x}" ] && [ -n "${MYSQL_USER+x}" ] && [ -n "${MYSQL_PASSWORD+x}" ] && [ -n "${MYSQL_HOST+x}" ]; then
            echo "Installing with MySQL database"
            # shellcheck disable=SC2016
            install_options=$install_options' --database mysql --database-name "$MYSQL_DATABASE" --database-user "$MYSQL_USER" --database-pass "$MYSQL_PASSWORD" --database-host "$MYSQL_HOST"'
            install=true
        elif [ -n "${POSTGRES_DB+x}" ] && [ -n "${POSTGRES_USER+x}" ] && [ -n "${POSTGRES_PASSWORD+x}" ] && [ -n "${POSTGRES_HOST+x}" ]; then
            echo "Installing with PostgreSQL database"
            # shellcheck disable=SC2016
            install_options=$install_options' --database pgsql --database-name "$POSTGRES_DB" --database-user "$POSTGRES_USER" --database-pass "$POSTGRES_PASSWORD" --database-host "$POSTGRES_HOST"'
            install=true
        fi

        if [ "$install" = true ]; then
            echo "Starting nextcloud installation"
            max_retries=10
            try=0
            until run_as "php /var/www/html/occ maintenance:install $install_options" || [ "$try" -gt "$max_retries" ]
            do
                echo "Retrying install..."
                try=$((try+1))
                sleep 10s
            done
            
            until run_as 'php /var/www/html/occ onfig:system:set debug --value="true" --type=boolean'
            if [ "$try" -gt "$max_retries" ]; then
                echo "Installing of nextcloud failed!"
                exit 1
            fi
            if [ -n "${NEXTCLOUD_TRUSTED_DOMAINS+x}" ]; then
                echo "Setting trusted domains…"
                NC_TRUSTED_DOMAIN_IDX=1
                for DOMAIN in $NEXTCLOUD_TRUSTED_DOMAINS ; do
                    DOMAIN=$(echo "$DOMAIN" | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//')
                    run_as "php /var/www/html/occ config:system:set trusted_domains $NC_TRUSTED_DOMAIN_IDX --value=$DOMAIN"
                    NC_TRUSTED_DOMAIN_IDX=$((NC_TRUSTED_DOMAIN_IDX+1))
                done
            fi
        else
            echo "Please run the web-based installer on first connect!"
        fi
    fi

    echo "Initializing finished"

    # Update htaccess after init if requested
    if [ -n "${NEXTCLOUD_INIT_HTACCESS+x}" ] && [ "$installed_version" != "0.0.0.0" ]; then
        run_as 'php /var/www/html/occ maintenance:update:htaccess'
    fi
) 9> /var/www/html/nextcloud-init-sync.lock

exec "$@"
