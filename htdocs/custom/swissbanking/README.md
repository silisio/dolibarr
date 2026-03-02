Module SwissBanking
=========

DEPENDENCIES
-------
The module requires that FCKEditor is enabled in order to work correctly.

If it's not enabled in your installation, it will be automatically enabled by the module as soon as you enter in the configuration interface.

PREREQUISITES
-------
Usage of the "custom" directory for custom modules

In your Dolibarr installation directory, edit the htdocs/conf/conf.php file
- Find the following lines:
  - //$=dolibarr_main_url_root_alt ...
  - //$=dolibarr_main_document_root_alt ...
- Uncomment these lines (delete the leading "//") and assign a sensible value according to your Dolibarr installation

For example :
- UNIX:
  - $dolibarr_main_url_root = 'http://localhost/Dolibarr/htdocs';
  - $dolibarr_main_document_root = '/var/www/Dolibarr/htdocs';
  - $dolibarr_main_url_root_alt = '/custom';
  - $dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';
- Windows:
  - $dolibarr_main_url_root = 'http://localhost/Dolibarr/htdocs';
  - $dolibarr_main_document_root = 'C:/My Web Sites/Dolibarr/htdocs';
  - $dolibarr_main_url_root_alt = '/custom';
  - $dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';
- DoliWamp:
  - Follow instructions as for Windows
  - PHP Extension "fileinfo" must be enabled in C:\dolibarr\bin\apache\apacheX.Y.Z\bin\php.ini (Where X.Y.Z is the actual apache version)

INSTALL / UPGRADE
-------
1. Login to Dolibarr and goto Dashboard -> Settings -> Modules/Applications -> Add external module (http(s)://WWW.YOURWEBSITE.COM/modules.php?mode=deploy)
2. Choose the downloaded file module_swissbanking-x.y.z.zip and press the "Upload" button. (The module is automatically uploaded)
   (If you upload the module manually e.g. through ftp make sure the swissbanking folder and its content has the correct access rights)
3. Enable the module in the modules section
4. Configure the module upon your needs

PRINT MODEL SwissBanking1Page
-------
The provided print model is given as a courtesy and it's not actively mantained or supported. It's intended to be used to print invoices that lasts on a single page,
so not more than 4-5 lines of products/services.