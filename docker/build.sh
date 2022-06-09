#!/bin/bash

# parameters
# PHP image
# Webserver image
# BDD image
# contao version
# cache usage (docker)

# colors
fg_red=$(tput setaf 1)
bg_red=$(tput setab 1)
fg_green=$(tput setaf 2)
bg_green=$(tput setab 2)
fg_yellow=$(tput setaf 3)
bg_yellow=$(tput setab 3)
fg_blue=$(tput setaf 4)
bg_blue=$(tput setab 4)
fg_magenta=$(tput setaf 5)
bg_magenta=$(tput setab 5)
fg_cyan=$(tput setaf 6)
bg_cyan=$(tput setab 6)
fg_white=$(tput setaf 7)
bg_white=$(tput setab 7)
normal=$(tput sgr0)

type=""
mode=""
image_php=""
cache=""

type_default="test-unit"
mode_default="up"
image_php_default="php:7.4-fpm"
cache_default="yes"

manual_parameters=false

helpFunction()
{
    echo ""
    echo "Usage: $0 --type type --image_php image_php [--cache cache] "
    echo -e "\t--type                The type to use (test-unit)"
    echo -e "\t--mode                The mode to use (up/down/destroy)"
    echo -e "\t--image_php           The docker image to use for PHP container"
    echo -e "\t--${fg_cyan}cache${normal}            Prevent docker from using cache (yes/no - ${fg_cyan}optionnal${normal} - default ${cache_default})"
    echo ""
    echo "Example :"
    echo "Run unit tests"
    echo -e "\t $0 --type test-unit --mode up --image_php ${image_php_default}"
    echo "Stop containers"
    echo -e "\t $0 --type test-unit --mode down"
    echo "Remove everything"
    echo -e "\t $0 --mode destroy"
    exit 1 # Exit script after printing help
}

set_env_vars()
{
    export IMAGE_PHP=$image_php;

    export IMAGE_PHP_DIR=${image_php/:/-};
}

unset_env_vars()
{
    unset IMAGE_PHP;
    unset IMAGE_PHP_DIR;
}

actual_job(){
    set_env_vars;
    defineDockerComposeFilename;
    if [[ "$mode" = "up" ]]
    then
        modeUp;
    elif [[ "$mode" = "down" ]]
    then
        modeDown;
    elif [[ "$mode" = "destroy" ]]
    then
        modeDestroy;
    fi

    unset_env_vars;
}

defineDockerComposeFilename(){
    if [[ "$type" = "test-unit" ]]
    then
        filename="docker-compose-test-unit";
    fi
}

modeUp(){
    if [[ "$cache" = "yes" ]]
    then
        echo -e "> docker-compose -f ./docker/${filename}.yml build;"
        docker-compose -f ./docker/${filename}.yml build;
    elif [[ "$cache" = "no" ]]
    then
        echo -e "> docker-compose -f ./docker/${filename}.yml build --no-cache;"
        docker-compose -f ./docker/${filename}.yml build --no-cache;
    fi

    echo -e "> docker-compose -f ./docker/${filename}.yml up -d --renew-anon-volumes;"
    docker-compose -f ./docker/${filename}.yml up -d;

    if [[ "$type" = "test-unit" ]]
    then
        echo -e "To run unit tests, run the following command :"
        echo -e "> docker exec grid_php_tu ./vendor/bin/codecept run unit";
    fi
}

modeDown(){
    echo -e "> docker-compose -f docker/${filename}.yml stop;"
    docker-compose -f docker/${filename}.yml stop; # stop only stops containers defined in the docker-compose.yml
}

modeDestroy(){
    echo -e "> docker-compose -f docker/docker-compose-base.yml down --remove-orphans --volumes;"
    docker-compose -f docker/docker-compose-base.yml down --remove-orphans --volumes;
}


function apply_parameters(){
    while (( $# > 1 )); do case $1 in
        --type) type="$2";;
        --mode) mode="$2";;
        --image_php) image_php="$2";;
        --cache) cache="$2";;
        *) break;
     esac; shift 2
    done

}

verify_parameter_mode(){
    while [[ "$mode" != "up" ]] && [[ "$mode" != "down" ]] && [[ "$mode" != "destroy" ]]
    do
        manual_parameters=true
        echo -e "${fg_green}Choose the mode (${fg_yellow}up${fg_green}/${normal}down${fg_green}/${normal}destroy${fg_green}) [default is '${fg_yellow}$mode_default${fg_green}']:${normal}";
        read -p "> " mode
        if [ -z "$mode" ]
        then
            mode=$mode_default;
        elif [[ "$mode" != "up" ]] && [[ "$mode" != "down" ]] && [[ "$mode" != "destroy" ]]
        then
            echo -e "${fg_red}Error :${normal} '$mode' is not a valid value."
        fi
    done
}

verify_parameter_type(){
    while [[ "$type" != "test-unit" ]]
    do
        manual_parameters=true
        echo -e "${fg_green}Choose the type (${fg_white}test-unit${fg_green}) [default is '${fg_yellow}$type_default${fg_green}']:${normal}";
        read -p "> " type
        if [ -z "$type" ]
        then
            type=$type_default;
        elif [[ "$type" != "test-unit" ]]
        then
            echo -e "${fg_red}Error :${normal} '$type' is not a valid value."
        fi
    done
}

verify_parameter_image_php(){
    while [[ "$image_php" != "php:7.4-fpm" ]]
    do
        manual_parameters=true
        echo -e "${fg_green}Choose the PHP image (${fg_yellow}php:7.4-fpm${fg_green}) [default is '${fg_yellow}$image_php_default${fg_green}']:${normal}";
        read -p "> " image_php
        if [ -z "$image_php" ]
        then
            image_php=$image_php_default;
        elif [[ "$image_php" != "php:7.4-fpm" ]]
        then
            echo -e "${fg_red}Error :${normal} '$image_php' is not a valid value."
        fi
    done
}

verify_parameter_cache(){
    while [[ "$cache" != "yes" ]] && [[ "$cache" != "no" ]]
    do
        manual_parameters=true
        echo -e "${fg_green}Choose the cache usage (${fg_yellow}yes${fg_green}/${fg_white}no${fg_green}) [default is '${fg_yellow}$cache_default${fg_green}']:${normal}";
        read -p "> " cache
        if [ -z "$cache" ]
        then
            cache=$cache_default;
        elif [[ "$cache" != "yes" ]] && [[ "$cache" != "no" ]]
        then
            echo -e "${fg_red}Error :${normal} '$cache' is not a valid value."
        fi
    done

}

function verify_parameters(){
    verify_parameter_mode;
    echo -e "> mode : $mode";

    if [[ "$mode" = "up" ]]
    then
        verify_parameter_type;
        echo -e "> type : $type";
        verify_parameter_image_php;
        echo -e "> PHP image : $image_php";
        verify_parameter_cache;
        echo -e "> Cache usage : $cache";
        if [[ true = $manual_parameters ]]
        then
            echo -e ""
            echo -e "[${fg_cyan}INFO${normal}] To reproduce this behaviour without manually re-entering parameters, use :"
            echo -e "\t $0 --type ${type} --mode ${mode} --image_php ${image_php} --cache ${cache}"
            echo -e ""
        fi
    elif [[ "$mode" = "down" ]]
    then 
        verify_parameter_type;
        echo -e "> type : $type";
        if [[ true = $manual_parameters ]]
        then       
            echo -e ""
            echo -e "[${fg_cyan}INFO${normal}] To reproduce this behaviour without manually re-entering parameters, use :"
            echo -e "\t $0 --type ${type} --mode ${mode}"
            echo -e ""
        fi
    elif [[ "$mode" = "destroy" ]]
    then 
        if [[ true = $manual_parameters ]]
        then       
            echo -e ""
            echo -e "[${fg_cyan}INFO${normal}] To reproduce this behaviour without manually re-entering parameters, use :"
            echo -e "\t $0 --mode ${mode}"
            echo -e ""
        fi
    else
        echo "${fg_red}Error :${normal} --mode parameter empty or invalid";
        helpFunction
    fi

}

apply_parameters "$@";
verify_parameters;
actual_job;