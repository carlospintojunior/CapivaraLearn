Technical Premises

* The includes/config.php file is automatically generated during the install.php process.
* The code should not be modified directly on the Apache server, but rather in the working directory. Synchronization is done through the ./sync_to_xampp.sh script.
* The log should be recorded to a file during application execution in the server directory itself. In this case, /opt/lampp/htdocs/CapivaraLearn/logs/

The database is located at:
DatabaseSchema.md

The logging framework is monolog.
The DB framework is medoo.