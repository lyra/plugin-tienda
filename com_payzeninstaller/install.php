<?php 
#####################################################################################################
#
#					Module pour la plateforme de paiement PayZen
#						Version : 1.1 (révision 43394)
#									########################
#					Développé pour Tienda
#						Version : 0.7.0
#						Compatibilité plateforme : V2
#									########################
#					Développé par Lyra Network
#						http://www.lyra-network.com/
#						12/02/2013
#						Contact : support@payzen.eu
#
#####################################################################################################

/** ensure this file is being included by a parent file */
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.filesystem.file');

// load tienda installer
JLoader::import( 'com_tienda.library.dscinstaller', JPATH_ADMINISTRATOR.DS.'components' );

// path to payzen plugin 
$pathToFolder = $this->parent->getPath('source').DS.'tienda_plugin_payment_payzen';

$dscInstaller = new dscInstaller();
$dscInstaller->set( '_publishExtension', true ); // publish payzen plugin
$result = $dscInstaller->installExtension($pathToFolder, 'folder');

// track the message and status of installation from dscInstaller
if ($result) {
	$pstatus = '<img src="images/tick.png" border="0" alt="Installed" />';
	
	echo $pstatus . "&nbsp;&nbsp;The plugin payment_payzen for Tienda was installed successfully.";
} else {
	$pstatus = '<img src="images/publish_x.png" border="0" alt="Failed" />';

	echo $pstatus . "&nbsp;&nbsp;" . $dscInstaller->getError();
}

// copy documentation file to right location
$docFileName = "Integration_PayZen_Tienda_0.7.0_v1.1.pdf";
$srcDir = dirname($this->parent->getPath('source'));
$destDir = JPATH_SITE.DS.'plugins'.DS."tienda".DS."payment_payzen".DS."installation_doc";

if(JFile::exists($srcDir . DS . $docFileName)) {
	JFile::copy($srcDir . DS . $docFileName, $destDir . DS . $docFileName);
}
?>