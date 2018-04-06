<?php
/**
 * Plugin Name: WooCommerce Criteo OneTag
 * Plugin URI: https://normanfrancis.com
 * Description: Criteo OneTag
 * Version: 1.0.0
 * Author: Norman Francis <hi@normanfrancis.com>
 * Author URI: https://normanfrancis.com
 */

if (!defined('ABSPATH'))
{
    exit;
} // Exit if accessed directly

function isWooCommerceActive() {
    return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
}

function criteo_tags() {
    if (get_option('wc_criteo_ot_account_id')) {
        getDefaultTag();

        if (is_front_page()) {
            getHomeTag();
        }

        if (isWooCommerceActive()) {
            if (is_product_category() || is_shop()) {
                getCategoryTag();
            }

            if (is_product()) {
                getProductTag();
            }

            if (is_cart()) {
                getCartTag();
            }

            if (is_order_received_page()) {
                getOrderConfirmationTag();
            }
        }
    }
}

function getUserEmail() {
    $email = '';
    
    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        $email = $current_user->user_email;
    }

    return md5( strtolower( str_replace( array("\r", "\n"), '', str_replace( ' ', '', $email ) ) ) );
}

function getCurrentCategory() {
    return (property_exists(get_queried_object(), 'slug') ? get_queried_object()->slug : '');
}

function getDefaultTag() {
?>
    <script type="text/javascript" src="//static.criteo.net/js/ld/ld.js" async="true"></script>
    <script type="text/javascript">
      var site_type = /iPad/.test(navigator.userAgent) ? "t" : /Mobile|iP(hone|od)|Android|BlackBerry|IEMobile|Silk/.test(navigator.userAgent) ? "m" : "d";
      window.criteo_q = window.criteo_q || [];
      window.criteo_q.push(
        { event: "setAccount", account: 00000 },
        { event: "setSiteType", type: site_type},
        { event: "setEmail", email: "<?php echo getUserEmail(); ?>"});
    </script>
<?php
}

function getHomeTag() {
?>
    <script type="text/javascript">
      window.criteo_q.push(
        { event: "viewHome", ecpplugin: "woocommerce"}
      );
    </script>
<?php
}

function getCategoryTag() {
    $args = array(
        'category' => getCurrentCategory(),
        'limit' => 3,
    );
    $products = wc_get_products( $args );
?>
    <script type="text/javascript">
        var criteo_items = [];

        <?php foreach ($products as $product): ?>
            criteo_items.push("<?php echo $product->get_id() ?>");
        <?php endforeach ?>

        window.criteo_q.push(
            { event: "viewList", ecpplugin: "woocommerce", product: criteo_items}
        );
    </script>
<?php
}

function getProductTag() {
    global $product;
    $product_id = $product->get_id();
?>
    <script type="text/javascript">
      window.criteo_q.push(
        { event: "viewItem", ecpplugin: "woocommerce", product: "<?php echo $product_id ?>" }
      );
    </script>
<?php
}

function getCartTag() {
?>
    <script type="text/javascript">
        var criteo_items = [];

        <?php foreach ( WC()->cart->get_cart() as $cart_item ): ?>
            criteo_items.push({
                id: "<?php echo $cart_item['data']->get_id() ?>",
                price: <?php echo $cart_item['data']->get_price() ?>,
                quantity: <?php echo $cart_item['quantity'] ?>
            });
        <?php endforeach ?>

        window.criteo_q.push(
            { event: "viewBasket", ecpplugin: "woocommerce", product: criteo_items}
        );
    </script>
<?php
}

function getOrderConfirmationTag() {
    global $wp;
    $order_id = isset( $wp->query_vars['order-received'] ) ? intval( $wp->query_vars['order-received'] ) : 0;
    $order = wc_get_order($order_id);
    $items = $order->get_items();
?>
    <script type="text/javascript">
        var criteo_items = [];

        <?php foreach ($items as $item_id => $item_data): ?>
            <?php $_product = $item_data->get_product(); ?>
            criteo_items.push({
                id: "<?php echo $_product->get_id() ?>",
                price: <?php echo $_product->get_price() ?>,
                quantity: <?php echo $item_data->get_quantity() ?>
            });
        <?php endforeach; ?>

        window.criteo_q.push(
            { event: "trackTransaction", ecpplugin: "woocommerce", id: "<?php echo $order_id ?>", product: criteo_items}
        );
    </script>
<?php
}

add_action('wp_footer', 'criteo_tags');

function wc_criteo_ot_settings() {
   register_setting( 'wc_criteo_ot_options_group', 'wc_criteo_ot_account_id' );
}
add_action( 'admin_init', 'wc_criteo_ot_settings' );

function wc_criteo_ot_create_option() {
  add_options_page('Criteo OneTag Settings', 'Criteo OneTag', 'manage_options', 'wc_criteo_ot', 'wc_criteo_ot_options_page');
}
add_action('admin_menu', 'wc_criteo_ot_create_option');

function wc_criteo_ot_options_page() {
?>
<div class="wrap">
    <h1>Criteo OneTag Settings</h1>
    <form method="post" action="options.php">
        <?php settings_fields( 'wc_criteo_ot_options_group' ); ?>
        <?php do_settings_sections( 'wc_criteo_ot_options_group' ); ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="wc_criteo_ot_account_id">Account ID</label></th>
                <td>
                    <input class="regular-text" type="text" id="wc_criteo_ot_account_id" name="wc_criteo_ot_account_id" value="<?php echo get_option('wc_criteo_ot_account_id'); ?>" />
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>
<?php
}
