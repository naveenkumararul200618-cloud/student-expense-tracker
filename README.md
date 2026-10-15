<<<<<<< HEAD

cd /home/workspace/my-project
mkdir -p phpext/apt/lists/partial phpext/apt/cache/archives/partial
OPTS="-o Dir::State::Lists=$PWD/phpext/apt/lists -o Dir::Cache=$PWD/phpext/apt/cache -o APT::Sandbox::User=studio"
apt-get $OPTS update
cd phpext && apt-get $OPTS download php8.4-mysql && ls

dpkg -x php8.4-mysql_*.deb .
find . -name "*.so"

cd /home/workspace/my-project
EXT=$(dirname $(find $PWD/phpext -name pdo_mysql.so))
php -d extension=$EXT/mysqlnd.so -d extension=$EXT/pdo_mysql.so -m | grep -i mysql

php -d extension=$EXT/mysqlnd.so -d extension=$EXT/mysqli.so -d extension=$EXT/pdo_mysql.so -S 0.0.0.0:8080 -t internship-portal

https://80a4d7855a53-0af40120-8080.ws6.app/

   cd /home/workspace/my-project
   ls internship-portal/config phpext

    cd /home/workspace/my-project
   EXT=$(dirname $(find $PWD/phpext -name pdo_mysql.so))
   php -d extension=$EXT/mysqlnd.so -d extension=$EXT/mysqli.so -d extension=$EXT/pdo_mysql.so -S 0.0.0.0:8080 -t internship-portal  
=======
# student-expense-tracker
A php based student expense tracker  
>>>>>>> bbc6b897387036ec5dcd6481beca6fa7dff5fc34
