/*************************************************************************************
 *                                                                                   *
 * Copyright (C) 2014-2022  Mercury Labs SAGL  <info@mercurylabs.ch>                 *
 * Copyright (C) 2014-2022  Reto Kessler       <reto.kessler@mercurylabs.ch>         *
 *                                                                                   *
 * Licence       : COMMERCIAL                                                        *
 * File          : README.txt                                                        *
 * Date          : 15 Apr 2014 - 25 Aug 2022                                         *
 * Description   : SwissBanking README                                               *
 *                                                                                   *
 *************************************************************************************/

DEPENDENCIES:
The module requires that FCKEditor is enabled in order to work correctly.
If it's not enabled in your installation, it will be automatically enabled by
the module as soon as you enter in the configuration interface.

PREREQUISITES:
Usage of the "custom" directory for custom modules
In your Dolibarr installation directory, edit the htdocs/conf/conf.php file
- Find the following lines:
  //$=dolibarr_main_url_root_alt ...
  //$=dolibarr_main_document_root_alt ...
- Uncomment these lines (delete the leading "//") and assign a sensible value according to your Dolibarr installation
  For example :
  - UNIX:
    $dolibarr_main_url_root = 'http://localhost/Dolibarr/htdocs';
    $dolibarr_main_document_root = '/var/www/Dolibarr/htdocs';
    $dolibarr_main_url_root_alt = '/custom';
    $dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';
  - Windows:
    $dolibarr_main_url_root = 'http://localhost/Dolibarr/htdocs';
    $dolibarr_main_document_root = 'C:/My Web Sites/Dolibarr/htdocs';
    $dolibarr_main_url_root_alt = '/custom';
    $dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';
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

##############################################################################
#                           Commercial License                               #
# This Commercial License gives you the right to install and configure this  #
# module on a single installation of the base software.                      #
#                                                                            #
# For each other additional installation you must buy a new license from the #
# publisher.                                                                 #
##############################################################################
#
# 1. Software Licence Agreement
# In this Licence, "the Product" means the software module for Dolibarr written in PHP language "SwissBanking" which may be found at www.dolistore.com
# This Licence is a legal agreement between you and Mercury Labs SAGL for the Product.
# By proceeding to install the Product, and in consideration of your use of the Product, you are deemed to agree to be bound by the terms of this Licence.
# Mercury Labs SAGL permits you to use the Product only in accordance with the terms of this Licence and your rights under this Licence will terminate
# automatically without notice if you fail to comply with the terms of this Licence
#
# 2. Product Licence
# In consideration of your agreeing to abide by the terms of this Licence and subject to your compliance with the terms of this Licence, Mercury Labs SAGL
# grants you a non-exclusive, non-transferable licence to use the Product for the following purposes and in the following manner:
# You may:
#   1. Install and use the Product for personal, educational, professional, non-profit use. In these cases, you are granted the right to use and to
#      make 1 single copy of this software
# You may not:
#   1. Remove any proprietary notices or labels from the Product
#   2. Reverse engineer, decompile, disassemble or make derivative works based on the Product
#   3. Rent, lease, sublicense, redistribute, resell, modify or assign the Product or any copy thereof, including any related documentation
#   3. Additional Terms : Where you are licensing the Product, this licence shall take effect between Mercury Labs SAGL and you and you shall take all
#      appropriate steps to ensure that the Product is operated in a proper manner by your employees and staff
#   4. Intellectual Property Rights : The Product is intellectual property of Mercury Labs SAGL and is protected by law. You acknowledge that all
#      intellectual property rights in the Product anywhere in the world belong to Mercury labs SAGL, that rights in the Product are licensed (not sold)
#      to you, and that you have no rights in, or to, the Product other than the right to use them in accordance with the terms of this Licence.
#   5. Warranty Disclaimer : THIS PRODUCT AND ANY RELATED DOCUMENTATION is PROVIDED "AS IS" WITHOUT WARRANTY OF ANY KIND, EITHER EXPRESS OR IMPLIED,
#      INCLUDING, WITHOUT LIMITATION, THE IMPLIED WARRANTIES OR MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, OR NONINFRINGEMENT. THE ENTIRE RISK
#      ARISING OUT OF USE OR PERFORMANCE OF THE PRODUCT REMAINS WITH YOU.
#   6. Limitation of Liability : IN NO EVENT SHALL MERCURY LABS SAGL BE LIABLE FOR ANY SPECIAL, INCIDENTAL, INDIRECT, OR CONSEQUENTIAL DAMAGES WHATSOEVER
#      (INCLUDING, WITHOUT LIMITATION, DAMAGES FOR LOSS OF BUSINESS PROFITS, BUSINESS INTERRUPTION, LOSS OF BUSINESS INFORMATION, OR ANY OTHER PECUNIARY
#      LOSS) ARISING OUT OF THE USE OR OF INABILITY TO USE THE PRODUCT
#   8. General
#   - Updates may be licensed to you by Mercury Labs SAGL with additional or different terms but Mercury labs SAGL has no obligation to provide any updates
#   - This Licence is the entire agreement between you and us and supersedes any prior representations, undertakings or advertising relating to the Product
#     and you acknowledge that in entering into this Licence you have not relied on any statement, representation, advertising, assurance or warranty
#     (whether made negligently or innocently) other than as expressly set out in this Licence
#   - This terms are subject to change without notice. Visit our site www.dolistore.com and download software to get the most recent End User License Agreement
# COPYRIGHT NOTICE. Copyright(C) 2013 - 2021 Mercury Labs SAGL, All rights reserved. Any rights not expressly granted herein are reserved