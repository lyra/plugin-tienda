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

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

/**
 * Renders a label element
 */

class JElementPayzenLabel extends JElement
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'PayzenLabel';

	function fetchElement($name, $value, &$node, $control_name)
	{
		$class = ( $node->attributes('class') ? 'class="'.$node->attributes('class').'"' : 'class="text_area"' );
		return '<label for="'.$name.'"'.$class.'>'.$value.'</label>';
	}
}