<?php

class WC_Gateway extends WC_Payment_Gateway {

    /**
     * Constructor for the Enkap Payment.
     */
    public function __construct() {

        global $woocommerce;
        $this->id                 = 'wc-jetchoko-gateway';
        $this->icon = apply_filters('woocommerce_gateway_icon', WC_GATEWAY_URL .'\assets\img\logo.png' );
        $this->has_fields         = false;
        $this->method_title       = __( 'JeTchoko Payment GateWay pour WooCommerce', 'wc-jetchoko-gateway' );
        $this->method_description = __( 'JeTchoko Payment GateWay Vous permet de faire votre paiement directement avec vos comptes mobiles', 'wc-jetchoko-gateway' );
        $this->order_button_text  = __( 'Payer avec JeTchoko', 'wc-jetchoko-gateway' );


        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->enabled      = $this->get_option( 'enabled' );
        //$this->instructions = $this->get_option( 'instructions', $this->description );
        $this->message      = $this->get_option( 'message' );
        $this->title        = $this->get_option( 'title' );
        $this->description  = $this->get_option( 'description' );
        $this->token_cmr    = $this->get_option( 'token_cmr' );
        $this->token_civ    = $this->get_option( 'token_civ' );
        
        if ( version_compare( $woocommerce->version, '5.0', '>=' ) ) {
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
        } else {
            add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
        }
  
        //add_action( 'woocommerce_thankyou', 'thankyou_page');

        add_action( 'woocommerce_after_order_notes', array( $this, 'my_custom_checkout_field' ), 10, 1);

        add_action( 'woocommerce_checkout_process', array( $this, 'my_custom_checkout_field_process' ), 10, 2);

        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'my_custom_checkout_field_update_order_meta' ), 10, 3);

        add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 4);

        //add_action( 'woocommerce_thankyou', array( $this, 'thankyou_page' ), 10, 5);
        
    }
    
    // Our hooked in function – $fields is passed via the filter!
    public function my_custom_checkout_field( $checkout ) {

        echo '<div id="my_custom_checkout_field"><h2>' . __('Opérateur du paiement mobile') . '</h2>';
    
        woocommerce_form_field( 'pays', array(
                'type'          => 'select',
                'label'         => __("Votre Pays", "wc-jetchoko-gateway"),
                'class'         => array('form-row-wide'),
                'required'      => true,
                'options'       => array(
                    ''          => __("Choix du pays", "wc-jetchoko-gateway"),
                'CMR'           => __("Cameroun", "wc-jetchoko-gateway"),
                'CIV'           => __("Côte d'Ivoire", "wc-jetchoko-gateway"),
            ),
        ), $checkout->get_value( 'pays' ));

        woocommerce_form_field( 'operator', array(
                'type'          => 'select',
                'label'         => __("Votre Opérateur", "wc-jetchoko-gateway"),
                'class'         => array('form-row-wide'),
                'required'      => true,
                'options'       => array(
                    ''          => __("Choix de l'pérateur mobile", "wc-jetchoko-gateway"),
                'MTN_CMR'       => __("MTN Cameroun", "wc-jetchoko-gateway"),
                'MTN_CIV'       => __("MTN Côte d'Ivoire", "wc-jetchoko-gateway"),
                'ORANGE_CMR'    => __("Orange Cameroun", "wc-jetchoko-gateway"),
                'ORANGE_CIV'    => __("Orange Côte d'Ivoire", "wc-jetchoko-gateway"),
                'MOOV_CIV'      => __("MOOV Côte d'Ivoire", "wc-jetchoko-gateway"),
            ),
        ), $checkout->get_value( 'operator' ));

        woocommerce_form_field( 'telephone', array(
                'type'          => 'tel',
                'label'         => __("Téléphone", "wc-jetchoko-gateway"),
                'class'         => array('form-row-wide'),
                'required'      => true,
        ), $checkout->get_value( 'telephone' ));
    
        echo '</div>';
    
    }

    /**
     * Process the checkout
     */

    public function my_custom_checkout_field_process() {
        // Check if set, if its not set add an error.
        if ( $_POST['pays'] === '' ) {

            wc_add_notice( __( "Choix d'un opérateur necessaire", "wc-jetchoko-gateway" ), 'error' );

        }

        if ( $_POST['operator'] === '' ) {

            wc_add_notice( __( "Choix d'un opérateur necessaire", "wc-jetchoko-gateway" ), 'error' );

        }

        if ( $_POST['telephone'] === '' ) {

            wc_add_notice( __( "Numéro necessaire pour la transaction", "wc-jetchoko-gateway" ), 'error' );

        }
    }

    /**
     * Update the order meta with field value
     */
    public function my_custom_checkout_field_update_order_meta( $order_id ) {
        if ( ! empty( $_POST['pays'] ) ) {
            update_post_meta( $order_id, 'Pays', sanitize_text_field( $_POST['pays'] ) );
        }

        if ( ! empty( $_POST['operator'] ) ) {
            update_post_meta( $order_id, 'Operator', sanitize_text_field( $_POST['operator'] ) );
        }

        if ( ! empty( $_POST['telephone'] ) ) {
            update_post_meta( $order_id, 'Telephone', sanitize_text_field( $_POST['telephone'] ) );
        }
    }

    /**
	 * Check if this gateway is available in the user's country based on currency.
	 *
	 * @return bool
	 */
	public function is_valid_for_use() {
		return in_array(
			get_woocommerce_currency(),
			apply_filters(
				'woocommerce_enkap_supported_currencies',
				array( 'XAF', 'XOF', 'CFA' )
			),
			true
		);
    }

    /**
	 * Admin Panel Options.
	 * - Options for bits like 'title' and availability on a country-by-country basis.
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {
		if ( $this->is_valid_for_use() ) {
			parent::admin_options();
		} else {
			?>
			<div class="inline error">
				<p>
                    <strong>
                        <?php 
                            esc_html_e( 'Passerelle désactivé', 'wc-jetchoko-gateway' ); 
                        ?>
                    </strong>: 
                        <?php 
                            esc_html_e( 'JeTchoko Payment GateWay ne supporte pas la monnaie de votre boutique!', 'wc-jetchoko-gateway' ); 
                        ?>
				</p>
                <?php deactivate_plugins( WC_GATEWAY_BASENAME ); ?>
			</div>
			<?php
            
		}
	}

    /**
     * Initialize Gateway Settings Form Fields
     */
    public function init_form_fields() {
    
        $this->form_fields = apply_filters( 
            
            'wc_form_fields', array(

                // payment platform access params
				'payment_platform_access' => array(
                    'title' => __('CONFIGURATION DE BASE POUR JETCHOKO', 'wc-jetchoko-gateway'),
                    'type' => 'title'
                ),
        
                'enabled' => array(
                    'title' => __('Activer/Desactiver','wc-jetchoko-gateway'),
                    'type' => 'checkbox',
                    'label' => $this ->title,
                    'default' => 'no'
                ),
                
                'title' => array(
                    'title'       => __( 'Titre', 'wc-jetchoko-gateway' ),
                    'type'        => 'text',
                    'description' => __( 'JeTchoko facilite les paiements sur votre boutique en ligne', 'wc-jetchoko-gateway' ),
                    'placeholder' => __( '(optionel) En laissant ce champ vide, le logo fera office de titre sur la checkout page' ),
                    'desc_tip'    => true,
                ),

                'description' => array(
                    'title'       => __( 'Description', 'wc-jetchoko-gateway' ),
                    'type'        => 'text',
                    'description' => __( 'Ce message apparaitra dans la checkout page de votre boutique', 'wc-jetchoko-gateway' ),
                    'placeholder' => __('Exemple: Veuillez patienter afin de finaliser votre achat', 'wc-jetchoko-gateway'),
                    'desc_tip'    => true,
                ),
                'message' => array(
                    'title'       => __( 'Message après transaction', 'wc-jetchoko-gateway' ),
                    'type'        => 'text',
                    'description' => __( 'Un message que vous enverez  à vos clients une fois leurs commandes traitées', 'wc-jetchoko-gateway' ),
                    'placeholder' => __( 'Merci de nous faire confiance et à très bientôt!', 'wc-jetchoko-gateway'),
                    'desc_tip'    => true,
                ),
                
                // payment platform access params
				'token_digits' => array(
                    'title' => __('BIEN VOULOIR REMPLIR LES CHAMPS SUIVANT SELON LES CODES RECUENT LORS DE LA CREATION DES COMPTES MARCHANDS', 'wc-jetchoko-gateway'),
                    'type' => 'title'
                ),
                'token_cmr' => array(
                    'title'       => __( 'Token Cameroun', 'wc-jetchoko-gateway' ),
                    'type'        => 'password',
                    'default'     => __('ljqsdfmlkqjdf4qsf4df8*DG5dgf', 'wc-jetchoko-gateway'),
                    'required'    => true,
                ),
                'token_civ' => array(
                    'title'       => __( 'Token Côte d\'Ivoire', 'wc-jetchoko-gateway' ),
                    'type'        => 'password',
                    'default'     => __('ljqsdfmlkqjdf4qsf4df8*DG5dgf', 'wc-jetchoko-gateway'),
                    'required'    => true,
                ),
            ) 
        );
    }

    // We initiate customer payment
    public function check_op_state( $refence ) {

        $url_ref = 'https://www.view-pay.com/api/repo/cashin/verify/' .$refence;

        $headers = array(
            'Accept'        => 'application/json',
            'Content-type'  => 'application/json'
        );

        //open connection
        $ch_ref = curl_init( $url_ref );

        //set the url, number of POST vars, POST data
        curl_setopt($ch_ref, CURLOPT_URL, $url_ref);
        curl_setopt($ch_ref, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch_ref, CURLOPT_HEADER, FALSE);
        curl_setopt($ch_ref, CURLOPT_POST, TRUE);
        curl_setopt($ch_ref, CURLOPT_RETURNTRANSFER, TRUE);

        //execute post
        $result_ref = json_decode(curl_exec($ch_ref), true);
        curl_close($ch_ref);

        return $result_ref;

    }

    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment( $order_id ) {

        // if ( ! $order_id ) {
        //     return;
        // }

        global $woocommerce;

        $order = wc_get_order( $order_id );

        // $returnurl = $this->get_return_url( $order ) . '&wc_order_id=' . $order_id;
        // $returnurl = $this->get_return_url( $order );


        if( $order ) {

            $body_string = '';
            $op_live_state = [];
            $pays = get_post_meta( $order_id, 'Pays', true );
            $operator = get_post_meta( $order_id, 'Operator', true );
			$telephone = get_post_meta( $order_id, 'Telephone', true );

            // Are we testing right now or is it a real transaction
            $url = 'https://view-pay.com/api/repo/cash/in';

            $headers = array(
                'Accept'        => 'application/json',
                'Content-type'  => 'application/json'
            );

            if( $pays === 'CMR' ) {

                $body = array(
                    'number' => urlencode($telephone),
                    'amount' => urlencode($order->get_total()),
                    'operator_code' => urlencode($operator),
                    'token' => urlencode($this->token_cmr),
                    'client_reference' => null,
                    'have_prefix' => false,
                    'callback' => null
                );

            }elseif ( $pays === 'CIV' ) {

                $body = array(
                    'number' => urlencode($telephone),
                    'amount' => urlencode($order->get_total()),
                    'operator_code' => urlencode($operator),
                    'token' => urlencode($this->token_civ),
                    'client_reference' => null,
                    'have_prefix' => false,
                    'callback' => null
                );

            }
            

            //url-ify the data for the POST
            foreach($body as $key=>$value) { 
                $body_string .= $key.'='.$value.'&'; 
            }
            rtrim($body_string, '&');

            //open connection
            $ch = curl_init( $url );

            //set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch,CURLOPT_POSTFIELDS, $body_string);

            //execute post
            $result = json_decode(curl_exec($ch), true);

            // $response = curl_getinfo($ch, CURLINFO_HTTP_CODE );
            // curl_close($ch);

            //$response_to_obj = json_decode( $result, true );
            if( $result['code'] == 'T200' ) {

                // wc_add_notice( $pays, 'error' );
                
               $ref = $result['data']['reference'];

               $op_live_state = $this->check_op_state( $ref );

               while(!($op_live_state['data']['status'] == 'SUCCESS')){

                   $op_live_state = $this->check_op_state($ref);

                   sleep(3);
                   if( $op_live_state['data']['status'] == 'SUCCESS' ) {
                       
                        wc_add_notice( $op_live_state['data']['status'], 'success' );
                            
                        //Reduce stock levels
                        wc_reduce_stock_levels($order_id);
                        
                        //Update Order Statut
                        $order->update_status('completed');

                        //Remove cart
                        WC()->cart->empty_cart();
                        
                        //Return thankyou redirect
                        return array(
                            'result' 	=> 'success',
                            'redirect'	=> $this->get_return_url( $order )
                        );

                   } elseif( $op_live_state['data']['status'] == 'FAILURE' ) {

                       wc_add_notice( 'Erreur lors de la transaction, bien vouloir vérifier que l\'opérateur que vous avez choisit correspond à celui de votre numéro et que votre solde est supérieur au montant de l\'achat!', 'error' );
                       $order->update_status('on-hold');
                       return;
                        
                   }
               }    

            }else {
                wc_add_notice( $result['message'], 'error' );
                $order->update_status('on-hold');
            }
            
        }

    }

    /**
     * Output for the order received page.
     */
    // public function thankyou_page() {
    //     echo 'test test ans test once more';
    // }
}
