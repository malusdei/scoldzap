Welcome to SCOLDZAP, Solomon Chang's OnLine DropZone Army Planner

This code is protected by the LGPL.  You can download it, host it on your own servers, and even modify it... but if you do make improvements, you are asked to share those changes and check in the new version.

If you have any questions, feel free to contact me at skevin521(at)yahoo(dot)com

Installation instructions:

git clone the repository
unzip the sql file (gzip -d scoldzap_data.sql.gz)
create the scoldzap database (CREATE DATABASE scoldzap;)
create the dzc user (CREATE USER 'dzc'@'localhost' IDENTIFIED BY 'dropzone';)
grant the dzc user privileges (GRANT ALL PRIVILEGES ON scoldzap.* to 'dzc';)
import the database to mysql (mysql -u <USERNAME> -p<PASSWORD> scoldzap < scoldzap_data.sql)