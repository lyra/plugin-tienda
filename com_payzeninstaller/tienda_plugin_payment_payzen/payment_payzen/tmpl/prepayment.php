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

defined('_JEXEC') or die('Restricted access'); 
?>

<?php 
$payzen_api = new PayzenApi();

$fields = $vars->getProperties();

foreach($fields as $name => $value) {
	$payzen_api->set($name, $value);
}
?>

<form action="<?php echo $payzen_api->platformUrl; ?>" method="POST">
	<div id="payment_payzen">
		<div class="prepayment_message">
	   		<?php echo JText::_( "PAYZEN PAYMENT PREPARATION MESSAGE" ); ?>
	    </div>
		<div class="prepayment_action">
	    	<div style="float: left; padding: 10px;"><input type="image" src="<?php echo JURI::root() . '/media/com_tienda/images/PayZen.jpg'; ?>" border="0" name="submit" alt="<?php echo JText::_( "PAYZEN CHECKOUT BTN ALT"); ?>" /></div>
	    	<div style="float: left; padding: 10px;"><?php echo "<b>".JText::_( "PAYZEN CHECKOUT AMOUNT").":</b> ".TiendaHelperBase::currency( @$vars->orderpayment_amount ); ?></div>
	        <div style="clear: both;"></div>
	        <br/><br/>
	    </div>
	</div>
	<?php echo $payzen_api->getRequestFieldsHtml(); ?>
</form>
