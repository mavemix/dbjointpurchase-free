# DB Joint Purchase

## Instalación de stack de pruebas Prestashop

1. Crear carpeta de proyecto
2. Crear archivo 'docker-compose.yml' dentro de la carpeta del proyecto:
        'version: "3"
        networks: 
            cx-prestashop-1.7.6.9-net:
        
        services: 
            mysql:
                image: mysql:5.7
                container_name: PS-MySql
                tty: true
                ports:
                    - "3306:3306"
                volumes:
                    - "./var/lib/mysql/:/var/lib/mysql"
                environment: 
                    MYSQL_ROOT_PASSWORD: XXXXXX 
                networks: 
                    - cx-prestashop-1.7.6.9-net
            phpmyadmin:
                image: phpmyadmin/phpmyadmin
                container_name: PhpMyAdmin
                environment:
                    PMA_HOST: PS-MySql
                    PMA_PORT: 3306
                    PMA_ARBITRARY: 1
                restart: always
                ports:
                    - "8081:80"
                networks: 
                    - cx-prestashop-1.7.6.9-net
            server:
                image: prestashop/prestashop:1.7.6.9-apache
                container_name: PS-apache
                ports:
                    - "8080:80"
                volumes:
                    - "./:/var/www/html"
                environment:
                    DB_SERVER: PS-MySql
                depends_on:  
                    - mysql
                networks: 
                    - cx-prestashop-1.7.6.9-net'

3. Establecer contraseña para mysql ( MYSQL_ROOT_PASSWORD: XXXXXX )
4. Ejecutar el comando 'docker compose up -d' para levantar el servidor (tardará la primera vez)
5. Realizar la instalación de prestashop, accediendo a 'localhost:8080'. En la configuración de la base de datos, el servidor es el nombre del contenedor de mysql 'PS-MySql'.
6. Clonar este repositorio en la carpeta modules, dentro de prestashop, con el nombre 'dbjointpurchase'
7. En el back office de prestashop, instalar este módulo.

## Funcionamiento general del módulo ##
En el backoffice de los productos, aparecen una serie de productos alternativos seleccionables (productos más vendidos junto al producto principal + top ventas). 

Si no se selecciona ninguno (y máximo se pueden seleccionar 3), el módulo sigue su lógica habitual.

La lógica habitual del módulo es ofrecer en el front de cada producto, un pack de productos para comprar en un solo click). Este pack es el top 3 de productos más comprados junto al producto original, y si no hubiera, ofrece el top 3 de ventas generales.

Lo que hace nuevo el módulo en el back office es ofrecer todas las posibilidades (top ventas conjuntas + top ventas generales) para que el administrador pueda decidir qué productos ofrecer al cliente en dicho pack.