# README #
_A WHMCS module for the Sentora control Panel AND a Sentora module for WHMCS_  
_These modules allow WHMCS to create users, suspend, unsuspend, terminate, change password and change packages on Sentora_

WHMCS Module tested on version 5.3.12 and 5.3.13, OS: CentOS 6.5  
Sentora Module tested on Sentora 1.0.0, OS: CentOS 7  
Status page tested on Windows 7 running WAMP and CentOS 7 running Sentora


## Credits ##

### Original version(s) ###
WHMCS Module: [Mathieu L�gar�](mailto:levelkro@yahoo.ca)  
ZPanel Module: [Knivey](https://github.com/knivey/)

### Additions/Edits for Sentora ###
[Rynoxx](https://github.com/rynoxx)  
_Do note that currently I've basicly only tested it and renamed variables and edited comments_

- - -

## Installation instructions ##

### Sentora ###
1. Add the repository to your Sentora installation and install the module using the following terminal commands:  
```
	zppy repo add zppy.grid-servers.net
	zppy update
	zppy install whmcs
```
2. Configure which usergroups that should be allowed to access the WHMCS module on Sentora using the Module Admin tool `http://url.toyoursentora.tld/?module=moduleadmin`
3. Configure the WHMCS module according to the form on the module page `http://url.toyoursentora.tld/?module=whmcs`

### WHMCS ###

1. Download the whmcs.zip from your the WHMCS module in your Sentora panel Located here: `http://url.toyoursentora.tld/?module=whmcs`
2. Extract it to the root directory of your WHMCS installation, e.g.
> /home/username/public_html/billing/ (cPanel has this folder structure, in this case billing is the directory where you installed WHMCS)  
> /var/sentora/hostdata/username/public_html/domainname/ (Sentora has this folder structure, in this case WHMCS installed directly to the domain root)  

3. In the WHMCS admin panel, navigate to "Setup" -> "Product/Services" -> "Servers" and add a new server
	1. Set the IP Address of the server to the domainname you use to access your Sentora installation (and if you're running a port which isn't 80 (for non HTTPS) or 443 (for SSL))

	2. Set the Server Status Address to `http://url.toyoursentora.tld/modules/whmcs/assets/status.php` where example.com should be replaced by the domain (or IP) of your Sentora installation (And replace http with https if you're running a secure server)

	3. Set the nameservers to whichever points to your sentora installation
		* Often something like ns1.example.com and ns2.example.com

	4. Set the server type to "Sentora". Leave Username and Password empty and in the access hash put `NUM,API-KEY` where `NUM` is the user id of the reseller account (leave it as `1` to be zadmin) and the `API-KEY` can be found in the MySQL database in sentora_core.x_settings

	5. Tick the "Secure" box if you want WHMCS to connect to your server using HTTPS instead of HTTP

### Updating the module ###
To update the Sentora module enter the following command to your terminal:  
```
	zppy upgrade whmcs
```  
To update the WHMCS module repeat the first two steps of the installation

## License ##

This work is licensed under the [GPL-V3 License](LICENSE)
