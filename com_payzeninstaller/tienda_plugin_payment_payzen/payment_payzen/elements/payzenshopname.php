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
 * Renders the shop name element
 */

class JElementPayzenShopName extends JElement
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'PayzenShopName';

	function fetchElement($name, $value, &$node, $control_name)
	{
		$size = ( $node->attributes('size') ? 'size="'.$node->attributes('size').'"' : '' );
		$class = ( $node->attributes('class') ? 'class="'.$node->attributes('class').'"' : 'class="text_area"' );
      
        $value = htmlspecialchars(html_entity_decode($value, ENT_QUOTES), ENT_QUOTES);
        
		if ($node->attributes( 'default' ) == $value)
        {
        	$value = TiendaConfig::getInstance()->get('shop_name');
        }

		return '<input type="text" name="'.$control_name.'['.$name.']" id="'.$control_name.$name.'" value="'.$value.'" '.$class.' '.$size.' />';
	}
}