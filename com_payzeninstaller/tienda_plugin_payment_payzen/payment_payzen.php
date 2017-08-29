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
defined('_JEXEC') or die('Restricted access');

Tienda::load( 'TiendaPaymentPlugin', 'library.plugins.payment' );

// Load PAYZEN API
 include_once JPATH_PLUGINS.DS."tienda/payment_payzen/library/payzen_api.php";

class plgTiendaPayment_payzen extends TiendaPaymentPlugin
{
	/**
	 * @var $_element  string  Should always correspond with the plugin's filename, 
	 *                         forcing it to be unique 
	 */
    var $_element    = 'payment_payzen';
    var $_payzenApi;
    
	function plgTiendaPayment_payzen(& $subject, $config) {
		parent::__construct($subject, $config);
		$this->loadLanguage( '', JPATH_ADMINISTRATOR );
		$this->_payzenApi = new PayzenApi();
	}

    /************************************
     * Note to 3pd: 
     * 
     * The methods between here
     * and the next comment block are 
     * yours to modify
     * 
     ************************************/

	
    /**
     * Prepares the payment form
     * and returns HTML Form to be displayed to the user
     * generally will have a message saying, 'confirm entries, then click complete order'
     * 
     * Submit button target for onsite payments & return URL for offsite payments should be:
     * index.php?option=com_tienda&view=checkout&task=confirmPayment&orderpayment_type=xxxxxx
     * where xxxxxxx = $_element = the plugin's filename 
     *  
     * @param $data     array       form post data
     * @return string   HTML to display
     */
    function _prePayment( $data )
    {
    
     	$order = JTable::getInstance('Orders', 'TiendaTable');
        $order->load( $data['order_id']);
        
        // prepare the payment form
   		$vars = new JObject();
        $vars->orderpayment_amount = $data['orderpayment_amount'];
        $vars->orderpayment_type = $this->_element;
        
        // Admin params
        $vars->set('vads_platform_url', $this->params->get('payzen_gateway_url'));
        $vars->set('vads_site_id', $this->params->get('payzen_site_id')); 
        $vars->set('vads_key_test',$this->params->get('payzen_key_test'));
		$vars->set('vads_key_prod',$this->params->get('payzen_key_prod'));
		$vars->set('vads_ctx_mode', $this->params->get('payzen_ctx_mode') );   
		
		// Process language
		jimport('joomla.language.helper');
		$lang = substr(JLanguageHelper::detectLanguage(), 0, 2);
		$payzen_language = in_array($lang, $this->_payzenApi->getSupportedLanguages()) ? $lang : 
			($this->params->get('payzen_language') ? $this->params->get('payzen_language') : 'fr');
		$vars->set('vads_language',  $payzen_language);
		
		$available_languages = $this->params->get('payzen_available_languages');
		if(isset($available_languages) && !empty($available_languages)) {
			if (is_array($available_languages)) {
				$vars->set('vads_available_languages',  implode(';', $available_languages));
			} else { 
				// 1 language selected
				$vars->set('vads_available_languages',  $available_languages);
			}
		}
		
		$vars->set('vads_shop_name', $this->params->get('payzen_shop_name'));
		$vars->set('vads_shop_url', $this->params->get('payzen_shop_url'));
		$vars->set('vads_payment_cards', $this->params->get('payzen_payment_cards'));
		$vars->set('vads_capture_delay', $this->params->get('payzen_capture_delay')  );   
		$vars->set('vads_validation_mode', $this->params->get('payzen_validation_mode'));   
		
		$vars->set('vads_redirect_enabled', $this->params->get('payzen_redirect_enabled') );   
		$vars->set('vads_redirect_success_timeout', $this->params->get('payzen_redirect_success_timeout'));   
		$vars->set('vads_redirect_success_message', $this->params->get('payzen_redirect_success_message') );   
		$vars->set('vads_redirect_error_timeout', $this->params->get('payzen_redirect_error_timeout') );   
		$vars->set('vads_redirect_error_message', $this->params->get('payzen_redirect_error_message') );   
		
		$vars->set('vads_return_mode', $this->params->get('payzen_return_mode') );   
		$vars->set('vads_return_get_params', $this->params->get('payzen_return_get_params'));
		$vars->set('vads_return_post_params', $this->params->get('payzen_return_post_params')); 
		$vars->set('vads_url_return', $this->params->get('payzen_url_return')); 
		$vars->set('vads_url_success', $this->params->get('payzen_url_success'));
		$vars->set('vads_url_cancel', $this->params->get('payzen_url_cancel')); 
		
		//Avalable cards
		$availableCards = $this->params->get('payzen_payment_cards');
		if(isset($availableCards) && !empty($availableCards)) {
			if (is_array($availableCards)) {
				$vars->set('payment_cards',  implode(';', $availableCards));
			} else {
				// 1 card selected
				$vars->set('payment_cards',  $availableCards);
			}
		}
		
		// activate 3ds ?
		$threeds_min_amount = $this->params->get('payzen_3ds_min');
		if($threeds_min_amount != '' &&  $vars->orderpayment_amount < $threeds_min_amount) {
			$threeds_mpi = 2;
		} else {
			$threeds_mpi = '';
		}
		$vars->set('threeds_mpi', $threeds_mpi);
		
		// load currency by Id  
     	$currency = JTable::getInstance('currencies', 'TiendaTable');
        $currency->load($data['currency_id']);
    	$currency_obj = $this->_payzenApi->findCurrencyByAlphaCode($currency->currency_code);

    	if($currency_obj == null) {
    		// normally never happens
			$vars->message = JText::_('PAYZEN UNKNOWN CURRENCY');
			
			$html = $this->_getLayout('prepayment', $vars);
       		return $html;
		} 
		$payzen_currency = $currency_obj->num;
		$vars->set('vads_currency', $payzen_currency );
		
		$vars->set('vads_amount',  $currency_obj->convertAmountToInteger($data['order_total']));
		
		// Other params
		$vars->set('vads_contrib', 'Tienda0.7.0_1.1');
		$vars->set('vads_order_id', $data['orderpayment_id']);
		
		$vars->set('vads_cust_id', $order->get('user_id'));
		$vars->set('vads_cust_address', $this->_decodeHtmlEntity($data['orderinfo']->billing_address_1). ' ' .$this->_decodeHtmlEntity($data['orderinfo']->billing_address_2));
		$vars->set('vads_cust_email', $data['orderinfo']->user_email);
		$vars->set('vads_cust_phone', $data['orderinfo']->billing_phone_1);
		$vars->set('vads_cust_city', $this->_decodeHtmlEntity($data['orderinfo']->billing_city));
		$vars->set('vads_cust_name', $this->_decodeHtmlEntity($data['orderinfo']->billing_last_name). ' ' .$this->_decodeHtmlEntity($data['orderinfo']->billing_first_name));
		$vars->set('vads_cust_zip', $data['orderinfo']->billing_postal_code);
		$vars->set('vads_cust_state', $this->_decodeHtmlEntity($data['orderinfo']->billing_zone_name));
		
		// Load country code
		$country = JTable::getInstance('countries', 'TiendaTable');
		$country->load( $data['orderinfo']->billing_country_id);
		$vars->set('vads_cust_country', $country->country_isocode_2);
		
		$vars->set('vads_ship_to_name', $this->_decodeHtmlEntity($data['orderinfo']->shipping_first_name) . ' ' . $this->_decodeHtmlEntity($data['orderinfo']->shipping_last_name));
		$vars->set('vads_ship_to_street', $this->_decodeHtmlEntity($data['orderinfo']->shipping_address_1));
		$vars->set('vads_ship_to_street2', $this->_decodeHtmlEntity($data['orderinfo']->shipping_address_2));
		$vars->set('vads_ship_to_city', $this->_decodeHtmlEntity($data['orderinfo']->shipping_city));
		$vars->set('vads_ship_to_state',$this->_decodeHtmlEntity($data['orderinfo']->shipping_zone_name));
		$vars->set('vads_ship_to_zip', $data['orderinfo']->shipping_postal_code);
		
		// Load shipping country code
	    if($data['orderinfo']->shipping_country_id) {
	        $country->load($data['orderinfo']->shipping_country_id);
			$vars->set('vads_ship_to_country', $country->country_isocode_2);
	    }

	    $html = $this->_getLayout('prepayment', $vars);
        return $html;
    }

    /**
     * Processes the payment form
     * and returns HTML to be displayed to the user
     * generally with a success/failed message
     *  
     * @param $data     array       form post data
     * @return string   HTML to display
     */
    function _postPayment( $data )
    {
        // Callback to be processed on return from payment platform
		$paction = JRequest::getVar('paction');
        
		// Vras for displaying messages
		$vars = new JObject();
		
		$data = isset($_POST['vads_order_id']) ? JRequest::get('post') :  JRequest::get('get');

		$payzen_resp = new PayzenResponse(
			$data, 
			$this->params->get('payzen_ctx_mode'),
			$this->params->get('payzen_key_test'),
			$this->params->get('payzen_key_prod')
		);
		
		// Response message 
   		$msg = $payzen_resp->message;
		if(!empty($payzen_resp->extraMessage)) {
			$msg .= '. '.$payzen_resp->extraMessage;
		}
		if(!empty($payzen_resp->authMessage)) {
			$msg .= '. '.$payzen_resp->authMessage;
		}	
		if(!empty($payzen_resp->warrantyMessage)) {
		   	$msg .= '. '.$payzen_resp->warrantyMessage;
		}
		
        // Load payment from db and initialize 
        $order_payment = JTable::getInstance('orderpayments', 'TiendaTable');
        $order_payment->load($payzen_resp->get('order_id'));
        $order_payment->transaction_id = $payzen_resp->get('trans_id');		
		$order_payment->transaction_details = $msg ;
		
		// Load order from db
		$order = JTable::getInstance('Orders', 'TiendaTable');
        $order->load($order_payment->order_id);
		
		// Get initial order state 
		$initial_state = TiendaConfig::getInstance()->get('initial_order_state', '15');
        
        switch ($paction) {
            case "process":
		        if(!$payzen_resp->isAuthentified()) {
					echo($payzen_resp->getOutputForGateway('auth_fail'));
			 	} else if (empty($order)) { 
					echo($payzen_resp->getOutputForGateway('order_not_found'));
				} else {
					if($order->order_state_id == $initial_state) {
						if ($payzen_resp->isAcceptedPayment()) {
							echo($payzen_resp->getOutputForGateway('payment_ok'));
            				
							$order->order_state_id = $this->params->get('payzen_succeeded_status_id');	
						    $order_payment->transaction_status = 'Succeeded';
						} else {
							echo($payzen_resp->getOutputForGateway('payment_ko'));
							
							$order->order_state_id = $this->params->get('payzen_failed_status_id');	
						    $order_payment->transaction_status = 'Failed';
						}
						
						// save order and order payment
					    $order->save();
					    $order_payment->save();
					} else {
						if ($payzen_resp->isAcceptedPayment()) {
							echo ($payzen_resp->getOutputForGateway('payment_ok_already_done'));
						} else {
							echo ($payzen_resp->getOutputForGateway('payment_ko_on_order_ok'));
						}
					}
				}
					
				$app =& JFactory::getApplication();
                $app->close();
                
                die();
            break;
           
            case "display_message":
        		if(!$payzen_resp->isAuthentified()) { // signature not verified
					$vars->message = JText::_('PAYZEN ERROR MESSAGE');
			 	} else if (empty($order)) { // Order not foud in db
					$vars->message = JText::_('PAYZEN ERROR MESSAGE');
				} else {
					$vars->message = "";
					
					if ($payzen_resp->isAcceptedPayment()) {
						$this->removeOrderItemsFromCart($payzen_resp->get('order_id')); // Remove cart content
						$vars->message .= JText::_('PAYZEN PAYMENT SUCCEEDED')  . '<br/><br/><br/>';
					} else {
						$vars->message .= JText::_('PAYZEN ERROR MESSAGE') . '<br/>' . $payzen_resp->message  . '<br/><br/><br/>';
					}
					
					if($this->params->get('payzen_ctx_mode') == 'TEST') {
						// Mode TEST warning : Check URL not correctly called
						$vars->message .= JText::_('PAYZEN GOING INTO PRODUCTION') . '<br/><br/>';
					}
					
					if($order->order_state_id == $initial_state) {
						if($this->params->get('payzen_ctx_mode') == 'TEST') {
							// Mode TEST warning : Check URL not correctly called
							$vars->message .= JText::_('PAYZEN CHECK URL WARNING') . '<br/>' . 
											  JText::_('PAYZEN CHECK URL WARNING DOCUMENTATION');	
						}
					
						if ($payzen_resp->isAcceptedPayment()) {
            				$order->order_state_id = $this->params->get('payzen_succeeded_status_id');	
						    $order_payment->transaction_status = 'Succeeded';
						} else {
							$order->order_state_id = $this->params->get('payzen_failed_status_id');	
						    $order_payment->transaction_status = 'Failed';
						}
						
						// save order and order payment
					    $order->save();
					    $order_payment->save();
					} 
				}
            break;
              
            case "cancel":
            	$vars->message = JText::_('PAYZEN PAYMENT CANCELLED BY USER') . '<br/><br/><br/>';
            	
            	if($this->params->get('payzen_ctx_mode') == 'TEST') {
            		// Mode TEST warning : Check URL not correctly called
            		$vars->message .= JText::_('PAYZEN GOING INTO PRODUCTION') . '<br/><br/>';
            	}
            break;

            default:
            	$vars->message = JText::_('PAYZEN UNKNOWN INVALID ACTION');
            break;
        }
        
        
        $html = $this->_getLayout('message', $vars);
        
        return $html;
    }   
    
    
    /**
     * Prepares variables for the payment form
     * 
     * @return unknown_type
     */
    function _renderForm( $data )
    {
        $user = JFactory::getUser();    
        $vars = new JObject();
        
        $html = $this->_getLayout('form', $vars);
        return $html;
    }

    
    
	/**
     * Determines if this payment option is valid for this order
     * 
     * @param $element
     * @param $order
     * @return unknown_type
     */
    function onGetPaymentOptions($element, $order)
    {       
    	// Check if this is the right plugin
    	if (!$this->_isMe($element)) 
        {
            return null;
        }
        
        // Chack amount min / max
     	if(($this->params->get('payzen_amount_min') != '' && $order->order_total < $this->params->get('payzen_amount_min'))
				|| ($this->params->get('payzen_amount_max') != '' && $order->order_total > $this->params->get('payzen_amount_max')))
     	{
            return false;
        }
        
        // Check currency
    	$currency = JTable::getInstance('currencies', 'TiendaTable');
        $currency->load($order->currency_id);
        
    	$currency_obj = $this->_payzenApi->findCurrencyByAlphaCode($currency->currency_code);
    	if($currency_obj == null) {
			return false;
		}    
        
        return parent::onGetPaymentOptions($element, $order);
    }
    
    function _decodeHtmlEntity($input) {
    	$html= html_entity_decode($input, ENT_NOQUOTES, 'UTF-8');
    	return $html;
    }
    
     /************************************
     * Note to 3pd: 
     * 
     * The methods between here
     * and the next comment block are 
     * specific to this payment plugin
     * 
     ************************************/
   
}
