#!/bin/sh 

# example: p4chroniclecodeline=main
# example: p4chroniclecodeline=p11.1
p4chroniclecodeline=main

if [ $1 ]; then
   p4chroniclecodeline=$1
fi

# example: changelist=@281526
changelist=

if [ $2 ]; then
   changelist=\@$2
fi

P4CLIENT=qa-selenium-get-p4chronicle-lin; export P4CLIENT
P4CLIENTdir=/work/p4clients/bld-ondemand9991/$P4CLIENT
P4USER=perforce; export P4USER
P4PORT=bld-ondemand:9991; export P4PORT

p4ver=r11.1
p4dir=/work/releases/$p4ver/latest
p4=$p4dir/p4
p4chronicledir=/work/releases/$p4chroniclecodeline/latest
p4chronicledepotpath=/depot/$p4chroniclecodeline/p4-bin/bin.multiarch/p4chronicle.tgz

# Parameters for downloading p4 from FTP
ftphost=ftp.perforce.com
ftpuser=anonymous
ftppasswd='cgrant@perforce.com'
ftpbindir=/perforce/$p4ver/bin.linux26x86

# Parameters for configuring Apache
# Get FQDN from /etc/hosts file
for host in `getent hosts \`cat /etc/hostname\` | awk '{for (f=2; f <= NF; f++) print $f}'`
do
   case $host in
   *.*)
      servername=$host
      break
      ;;
   esac
done
if [ $servername ]; then
   echo Servername: $servername
else
   echo "Could not determine server name. Make sure /etc/hosts has an entry of the form hostname.sub.domain.com"
   exit
fi

vhostdef=/etc/apache2/sites-enabled/$servername

# Parameters for configuring P4PHP
p4php=$p4chronicledir/p4chronicle/p4-bin/bin.linux26x86/p4php/perforce.so
phpini=/etc/php5/apache2/php.ini

printf2()
{
   #
   # printf to file descriptor 2.
   #
   printf "$@" >&2
}

run()
{
   sh -c "$*"
}

configureapache()
{
   # sudo apt-get -y install apache2 libapache2-mod-php5 php5 php-apc
   if [ ! -e /etc/apache2/mods-enabled/rewrite.load ]; then
      echo "Linking /etc/apache2/mods-available/rewrite.load to /etc/apache2/mods-enabled/rewrite.load"
      sudo ln -s /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled
   fi
   
   echo "Creating vhost file..."
   cat <<vhost > vhostdef
   <VirtualHost *:80>
       ServerAdmin webmaster@localhost
       ServerName $servername
       DocumentRoot $p4chronicledir/p4chronicle/

       <Directory />
                Options FollowSymLinks
                AllowOverride None
        </Directory>
        <Directory $p4chronicledir/p4chronicle/>
                Options Indexes FollowSymLinks -MultiViews
                AllowOverride All
                Order allow,deny
                allow from all
        </Directory>

        ErrorLog /var/log/apache2/error-p4chronicle.log

        # Possible values include: debug, info, notice, warn, error, crit,
        # alert, emerg.
        LogLevel info

        CustomLog /var/log/apache2/access-p4chronicle.log combined

   </VirtualHost>
vhost
   
   # A move doesn't work here if the CWD is an NFS mount
   sudo cp vhostdef $vhostdef
   rm vhostdef
   
}

configurephp()
{
   echo "Setting up P4PHP..."
   if [ `grep ^extension= $phpini | wc -l` = 1 ]; then
      sudo sed -i s_"^extension=.*"_"extension=$p4php"_ $phpini
   else
      sudo sh -c "echo 'extension=$p4php' >> $phpini"
   fi
}


getp4()
{
   if [ ! -f $p4dir/p4 ]; then
      echo "Creating P4 executable directory: $p4dir"
      mkdir -p $p4dir
   
      echo "Retrieving P4 command line client from FTP"

ftp -n $ftphost <<END_SCRIPT
quote USER $ftpuser
quote PASS $ftppasswd
lcd $p4dir
cd $ftpbindir
binary
get p4
quit
END_SCRIPT

      if [ ! -f $p4dir/p4 ]; then
         echo "Failed to retrieve P4 command line client from FTP. Exiting..."
         exit 1
      fi
      
      echo "Granting P4 command line client execute permissions"
      chmod 755 $p4
      
   fi
}

buildclient()
{ 
   printf2 "Creating client..."
   run "cat <<EODclient | $p4 -p $P4PORT -u $P4USER -c $P4CLIENT client -i
Client: $P4CLIENT  
Root: $P4CLIENTdir
Options: rmdir
View:
        /$p4chronicledepotpath //$P4CLIENT$p4chronicledepotpath
EODclient"
   printf2 " done.\n"
}

syncfiles()
{
   run "$p4 -p $P4PORT -u $P4USER -c $P4CLIENT sync -f $changelist"
}

unpackp4chronicle()
{
   mkdir -p $p4chronicledir
   if [ -d $p4chronicledir/p4chronicle ]; then
      printf2 "Removing $p4chronicledir/p4chronicle\n"
      sudo rm -rf $p4chronicledir/p4chronicle
   fi

   cd $p4chronicledir
   printf2 "Untaring files to $p4chronicledir\n"
   tar -zxf $P4CLIENTdir$p4chronicledepotpath
   p4chroniclebasedir=`ls -d p4chronicle-*`    
   printf2 "Moving $p4chroniclebasedir to p4chronicle\n"
   mv $p4chroniclebasedir p4chronicle
   
   printf2 "Setting permissions on $p4chronicledir/p4chronicle/data\n"
   sudo chown www-data: $p4chronicledir/p4chronicle/data
}

printf2 "Stopping Apache Web Server\n"
sudo /usr/sbin/apache2ctl stop
configureapache
configurephp
getp4
buildclient
syncfiles
unpackp4chronicle
printf2 "Starting Apache Web Server\n"
sudo /usr/sbin/apache2ctl start
