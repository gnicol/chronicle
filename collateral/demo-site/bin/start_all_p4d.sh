#!/bin/bash
SERVER_BASEDIR=`cd $(dirname $0)/../p4cms-servers; pwd`
APACHE_USER=www-data
P4D=/usr/local/bin/p4d

[ `whoami` != "root" ] && echo "please run as root" && exit 1

function start_server
{
    [ -z "$1" ] && echo "no arguments passed" && return
    [ -z "$2" ] && echo "no instance name passed" && return

    local port="$1"
    local instance="$2"
    local p4root=$SERVER_BASEDIR/$instance

    [ ! -d "$p4root" ] && echo "P4 root dir [$p4root] does not exist" && return

    mkdir -p $p4root
    chown -R $APACHE_USER $p4root
    [ ! -z "$P4CONFIG" ] && echo "P4PORT=$port" > $p4root/$P4CONFIG

    echo sudo -u $APACHE_USER $P4D -p $port -r $p4root -L $p4root/log -d
    sudo -u $APACHE_USER $P4D -p $port -r $p4root -L $p4root/log -d
}

start_server 1111 demo
start_server 2222 ui
start_server 3333 docs
start_server 4444 qa
start_server 5555 web
start_server 6666 dmarti
start_server 7777 twilliams
start_server 8888 jgarcia
start_server 9999 maindebug
start_server 20111 2011.1
start_server 20112 2011.2
start_server 20121 2012.1
start_server 20122 2012.2
start_server 20123 2012.3
start_server 1173 live
start_server 1174 livedev
start_server 54321 p4chron.com
start_server 12345 main
