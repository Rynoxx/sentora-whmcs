# README #
_A WHMCS module for the Sentora control Panel AND a Sentora module for WHMCS_  
_These modules allow WHMCS to create users, suspend, unsuspend, terminate, change password and change packages on Sentora_

WHMCS Module tested on version 5.3.12, 5.3.13 and 5.3.14, OS: CentOS 6.5, CentOS 7, Debian 8  
Sentora Module tested on Sentora 1.0.0 - 1.0.3, OS: CentOS 7, Debian 8  
Sentora Module tested on AWServer ZPanelX v2.1, OS: Windows 7  
Status page tested on Windows 7 running WAMP and CentOS 7 running Sentora


## Credits ##

### Original version(s) ###
WHMCS Module: [Mathieu Légaré](mailto:levelkro@yahoo.ca)  
ZPanel Module: [Knivey](https://github.com/knivey/)

### Additions/Edits for Sentora ###
[Rynoxx](https://github.com/rynoxx)  
[MarkDark](http://forums.sentora.org/member.php?action=profile&uid=307) - ZPanelX compatability updates  

- - -

## Requirements ##
[WHMCS](http://whmcs.com/ "WHMCS Website") installed (doesn't have to be on the same machine as the sentora installation(s))
The WHMCS and Sentora installation running on PHP 5.4 or above  
The Sentora module requires the following default modules to function (enabled by default): manage_clients and domains  

- - -

## Installation instructions ##

### Sentora Module ###
1. Add the repository to your Sentora installation and install the module using the following terminal commands:  
```
	zppy repo add zppy.grid-servers.net
	zppy update
	zppy install whmcs
```
2. Configure which usergroups that should be allowed to access the WHMCS module on Sentora using the Module Admin tool `http://url.toyoursentora.tld/?module=moduleadmin`
3. Configure the WHMCS module according to the form on the module page `http://url.toyoursentora.tld/?module=whmcs`
4. Configure packages to your liking

**If you're having trouble with terminating clients through WHMCS**
* Edit line 21 of `sentora/modules/manage_clients/code/webservice` to be this:
`module_controller::ExecuteDeleteClient($contenttags['uid'], empty($contenttags['moveid']) ? 1 : $contenttags['moveid']);`
Instead of this.
`module_controller::ExecuteDeleteClient($contenttags['uid']);`

### WHMCS Module ###

1. Download the whmcs.zip from your the WHMCS module in your Sentora panel Located here: `http://url.toyoursentora.tld/?module=whmcs`
2. Extract it to the root directory of your WHMCS installation, e.g.
> /home/username/public_html/billing/ (cPanel has this folder structure, in this case billing is the directory where you installed WHMCS)  
> /var/sentora/hostdata/username/public_html/domainname/ (Sentora has this folder structure, in this case WHMCS installed directly to the domain root)  

3. In the WHMCS admin panel, navigate to "Setup" -> "Product/Services" -> "Servers" and add a new server
	1. Set the IP Address of the server to the domainname or ip you use to access your Sentora installation (and if you're running a port which isn't 80 (for non HTTPS) or 443 (for SSL))

	2. Set the Server Status Address to `http://url.toyoursentora.tld/modules/whmcs/assets/status.php` where example.com should be replaced by the domain (or IP) of your Sentora installation (And replace http with https if you're running a secure server)

	3. Set the nameservers to whichever points to your sentora installation
		* Often something like ns1.example.com and ns2.example.com
		* Do note that cloudflare has nameserver hostnames that are more similar to hank.ns.cloudflare.com and ivy.ns.cloudflare.com

	4. Set the server type to "Sentora". Leave Username and Password empty and in the access hash put `NUM,API-KEY` where `NUM` is the user id of the reseller account (leave it as `1` to be zadmin) and the `API-KEY` can be found on the WHMCS module page in Sentora.

	5. Tick the "Secure" box if you want WHMCS to connect to your server using HTTPS instead of HTTP

	6. Create a server group for you sentora servers, and add your created server(s) to it.

4. In WHMCS admin panel, navigate to "Setup" -> "Products/Services" -> "Products/Services" and add a new product
	1. Go to the 'Module Settings' tab, it's the only tab which within the scope of this guide.

	2. Set 'Module Name' to 'Sentora'

	3. Set the 'Server Group' to the one you created in step 3.6

	4. Set 'Package Name' to the exact package name of the package you want as configured in Sentora

	5. Leave 'Reseller' unticked if you're unsure.

	6. Tick 'Auto-Create DNS Records' if you want the domains created through WHMCS to automatically have default DNS records (email, ftp, www, etc) created in Sentora, this is required if you want your clients to be able to use your nameservers without them manually creating the default records.

### WHMCS Setup guide by conceptr980 ###
**Written by [conceptr980](http://forums.sentora.org/member.php?action=profile&uid=1196 "Conceptr980s profile on the Sentora forum")**

#### WHMCS - Servers ####
**- Create New Server/Edit Server**
* **Name**: Whatever you want
* **Hostname**: Your Hostpanel URL (Ex, host.domain.com) **EXCLUDING http:// AND https://**
* **-Nameservers**
* **Nameserver**: Primary and secondary if exist with or without an IP should be ok.
* **- Server Details:**
* **Type**: Sentora
* **Username**: Empty
* **Password**: Empty
* **Access Hash**: 1,Key
* **Note**: you can view your key in WHMCS module in your Home --> Host Management --> WHMCS --> Toggle API Key Visibility
* **Secure**: Depends on your settings

#### WHMCS Products/Services ####
**- Create New Product**
* **Product Type**: Hosting Account
* **Product Group**: Whatever you created
* **Product Name**: Name of your product  
**- Details Tab: Fill the desired Info**  
**- Modules Settings Tab**
* **Module Name**: Sentora
* **Server Group**: A server group you've created for the Sentora server(s)
* **Package Name**: The name of your package in the host panel
* (Copy and past package name, Home --> Hosting Management --> Package Manager)
* **Reseller**: Tick
* **Automatically setup the product as soon as the first payment is received**: Most Common Choice

**- WHMCS View/Search Clients**
* Click on your client name --> Add New Order
* Add new order details --> Fill the info and make sure your Product/Service is pointed to the right service
* Submit Order
* Products/Services Tab (Make sure that you have a user name otherwise it will return an error)
* Create, Suspend, Unsespend ... Etc

### Updating the module ###
To update the Sentora module enter the following command to your terminal:  
```
	zppy update
	zppy upgrade whmcs
```  
To update the WHMCS module repeat the first two steps of the installation

## License ##

This work is licensed under the [GPL-V3 License](LICENSE)

### What to do when errors occur ###
* Enable module debug logging in WHMCS (located under Utilities > Logs > Module Log)
* Do the API call you attempted to do (e.g. create the user/accept order, unsuspend/suspend etc)
* Post on the Sentora forum and tag me in the post (By typing `@[Rynoxx]`) include the problem and the output of the WHMCS debug log
