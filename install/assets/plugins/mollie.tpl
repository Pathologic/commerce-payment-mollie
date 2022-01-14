//<?php
/**
 * Payment Mollie
 *
 * Mollie payments processing
 *
 * @category    plugin
 * @version     0.0.1
 * @author      Pathologic
 * @internal    @events OnRegisterPayments,OnBeforeOrderProcessing,OnBeforeOrderSending,OnManagerBeforeOrderRender
 * @internal    @properties &title=Title;text; &api_key=API key;text; &debug=Debug;list;No==0||Yes==1;1 
 * @internal    @modx_category Commerce
 * @internal    @installset base
 */

return require MODX_BASE_PATH . 'assets/plugins/mollie/plugin.mollie.php';