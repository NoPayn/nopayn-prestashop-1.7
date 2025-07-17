# NoPayn plugin for Prestashop 1.7.x.x

This is the offical NoPayn plugin.

## About
The NoPayn Prestashop 1.7 plugin allows you to integrate NoPayn’s payment gateway into your Prestashop store. From your order overview, you can easily manage key actions such as auto-capture, manual capture on order completion, refunds, and cancellations—giving you full control over your payment flow.
NoPayn is built on a foundation of transparency and simplicity. With clear, fixed transaction fees and no hidden costs, our solution is designed to be straightforward for merchants of all sizes. In addition, every transaction made through NoPayn contributes to tree planting, making your payment setup climate positive by default.

## Version number
Version 1.0.2

## Pre-requisites to install the plug-ins
* PHP v5.4 and above
* MySQL v5.4 and above

## Installation
Manual installation of the PrestaShop 1.7 plugin using (s)FTP

1. Upload all of the folders in the ZIP file into the Modules folder of your PrestaShop installation (no files are transferred).
You can use an sFTP or SCP program, for example, to upload the files. There are various sFTP clients that you can download free of charge from the internet, such as WinSCP or Filezilla.
2. Go to your PrestaShop admin environment. Click 'Modules' > 'Module Manager’ and search for NoPayn. If it doesn't exists - check it on panel 'Uninstalled modules'
3. You will see several modules to be installed. Start with ‘nopayn’. Click Install / Proceed with the installation.
After installation, the module will move to Improve' > 'Modules' > 'Module Manager’
4. Configure the NoPayn module
- Enable the cURL CA bundle option.
This fixes a cURL SSL Certificate issue that appears in some web-hosting environments where you do not have access to the PHP.ini file and therefore are not able to update server certificates.
- Copy the API key

5. After you have installed the ‘NoPayn´ module, you can install the other modules you would like to offer in your webshop.
Enable only those payment methods that you applied for and for which you have received a confirmation from us.

Note that if a payment method has no specific configuration to be done apart from the ones in the generic configuration, the only option shown on the panel will be “Disable”/”Enable”.
The “configure” option is only shown in case the payment method has further configuration.


Manual installation of the PrestaShop 1.7 plugin using Admin Panel

1.  In the admin panel go IMPROVE->Module Manager.
2.  Push "Upload a module".
3.  Drag and drop the "nopayn.zip" or push the "select file" button and choose it. Wait for the installation process will be completed.
4.  Find the "NoPayn" at the bottom of the page in the "Other" section and push the "Configure" button in it.
5.  Fill the "API Key" field and push the "Save" button.
6.  Push the "Back" button in the top or go IMPROVE->Module Manager.
7.  Push "Upload a module".
8.  Drag and drop one by one all other archives with payment methods that you need.

Compatibility: PrestaShop 1.7.0.0 or higher
