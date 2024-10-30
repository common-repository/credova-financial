<?php
/**
 * Defining Credova Table List
 * ============================================================================
 *
 * In this part you are going to define custom table list class,
 * that will display your database records in nice looking table
 *
 */
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
/**
 * Credova_Table_Example_List_Table class  that will display our custom table
 * records in nice table
 */
class Credova_Table_Example_List_Table extends WP_List_Table
{
    /**
     * [REQUIRED] You must declare constructor and give some basic params
     */
    public function __construct()
    {
        global $status, $page;
        parent::__construct(array(
            'singular' => 'credova',
            'plural'   => 'credovas',
        ));
    }
    /**
     * [REQUIRED] this is a default column renderer
     *
     * @param $item - row (key, value array)
     * @param $column_name - string (key)
     * @return HTML
     */
    public function column_default($item, $column_name)
    {
        return $item[$column_name];
    }
    /**
     * [OPTIONAL] this is example, how to render specific column
     *
     * method name must be like this: "column_[column_name]"
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    public function column_shop_order_id($item)
    {
        return '<em>' . $item['shop_order_id'] . '</em>';
    }
    /**
     * [OPTIONAL] this is example, how to render column with actions,
     * when you hover row "Edit | Delete" links showed
     *
     * @param $item - row (key, value array)
     * @return HTML
     */

    public function column_delivery_info($item)
    {
        $actions = array(
            'edit' => sprintf('<a href="?page=credova_form&id=%s&delivery_info=%s&check_form=%s">%s</a>', $item['id'], $item['delivery_info'], 'delivery_info', __('Edit DI', 'cltd_example')),
        );
        return sprintf(
            '%s %s',
            $item['delivery_info'],
            $this->row_actions($actions)
        );
    }

    public function column_refund_status($item)
    {
        $actions = array(
            'edit' => sprintf('<a href="?page=credova_form&id=%s&refund_status=%s&check_form=%s">%s</a>', $item['id'], $item['refund_status'], 'refund_status', __('Edit', 'cltd_example')),
        );
        return sprintf(
            '%s %s',
            $item['refund_status'],
            $this->row_actions($actions)
        );
    }
    /**
     * [REQUIRED] this is how checkbox column renders
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }
    /**
     * [REQUIRED] This method return columns to display in table
     * you can skip columns that you do not want to show
     * like content, or description
     *
     * @return array
     */
    public function get_columns()
    {
        $columns = array(
            'cb'                => '<input type="checkbox" />', //Render a checkbox instead of text
            'shop_order_id'     => __('Shop Order ID', 'cltd_example'),
            //'store_name' => __('Store Name', 'cltd_example'),
            //'federal_license' => __('Federal License', 'cltd_example'),
            //'cart_id' => __('shop order id', 'cltd_example'),
            //'transaction_date' => __('Transaction Date', 'cltd_example'),
            'customer_name'     => __('Customer Name', 'cltd_example'),
            //'customer_address' => __('Customer Address', 'cltd_example'),
            //'customer_city' => __('Customer City', 'cltd_example'),
            //'customer_state' => __('Customer State', 'cltd_example'),
            //'customer_zipcode' => __('Customer Zip', 'cltd_example'),
            'customer_email'    => __('Customer Email', 'cltd_example'),
            'customer_phone'    => __('Customer Phone', 'cltd_example'),
            'payment_status'    => __('Payment Status', 'cltd_example'),
            'created_at'        => __('Created At', 'cltd_example'),
            'total_inc_tax'     => __('Total Amount', 'cltd_example'),
            'woo_order_status'  => __('Order Status', 'cltd_example'),
            'credova_public_id' => __('Credova Public ID', 'cltd_example'),
            //'credova_lender_name' => __('Credova Lender Name', 'cltd_example'),
            //'credova_lender_code' => __('Credova Lender Code', 'cltd_example'),
            //'credova_approval_amount' => __('Credova Approval Amount', 'cltd_example'),
            //'credova_borrowed_amount' => __('Credova Borrowed Amount', 'cltd_example'),
            //'financing_partner_name' => __('Financing Partner Name', 'cltd_example'),
            //'financing_partner_code' => __('Financing Partner Code', 'cltd_example'),
            //'invoice_upload' => __('Invoice Upload', 'cltd_example'),
            // 'delivery_info'     => __('Delivery Info', 'cltd_example'),
            'refund_status'     => __('Return Request', 'cltd_example'),
        );
        return $columns;
    }
    /**
     * [OPTIONAL] This method return columns that may be used to sort table
     * all strings in array - is column names
     * notice that true on name column means that its default sort
     *
     * @return array
     */
    public function get_sortable_columns()
    {
        $sortable_columns = array(
            'shop_order_id' => array('shop_order_id', true),
            'created_at'    => array('created_at', true),
        );
        return $sortable_columns;
    }
    /**
     * [OPTIONAL] Return array of bult actions if has any
     *
     * @return array
     */
    public function get_bulk_actions()
    {
        $actions = array(
            'delete' => 'Delete',
        );
        return $actions;
    }
    /**
     * [OPTIONAL] This method processes bulk actions
     * it can be outside of class
     * it can not use wp_redirect coz there is output already
     * in this example we are processing delete action
     * message about successful deletion will be shown on page in next part
     */
    public function process_bulk_action()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'credova_info'; // do not forget about tables prefix
        // $req_id     = isset($_REQUEST['id']) ? intval($_REQUEST['id']):null;
        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
            if (is_array($ids)) {
                $ids = implode(',', $ids);
            }
            if (!empty($ids)) {
                $wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
            }
        }
        if ('refund' === $this->current_action()) {
            $credova_details  = get_option('woocommerce_credova_settings');
            $credova_testmode = $credova_details['testmode'];
            if ($credova_testmode == 'yes') {
                $credova_testmode = 1;
            } else {
                $credova_testmode = 0;
            }
            $credova_api_username = $credova_details['api_username'];
            $credova_api_password = $credova_details['api_password'];
            $order_billing_phone  = '';
            if (isset($_REQUEST['id'])) {
                $req_id     = $_REQUEST['id'];
                $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $req_id), ARRAY_A);
                if ($item) {
                    $credova_public_id = $item['credova_public_id'];
                    $refund_status     = $item['refund_status'];
                    //if($refund_status != 'Refunded'){
                    $client = new CredovaClient($credova_api_username, $credova_api_password, $credova_testmode);
                    $client->authenticate();
                    $resp = $client->request_return($credova_public_id);

                    //if(isset($resp['public_id']))
                    if (empty($resp)) {
                        $colm_id = array('id' => intval($_REQUEST['id']));
                        $colms   = array('refund_status' => 'Refunded');
                        $result  = $wpdb->update($table_name, $colms, $colm_id);
                    } else {
                        $error   = $resp['errors'][0];
                        $colm_id = array('id' => intval($_REQUEST['id']));
                        $colms   = array('refund_status' => $error);
                        $result  = $wpdb->update($table_name, $colms, $colm_id);
                    }
                    //}
                }
            }
        }
    }
    /**
     * [REQUIRED] This is the most important method
     *
     * It will get rows from database and prepare them to be showed in table
     */
    public function prepare_items()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'credova_info'; // do not forget about tables prefix

        $per_page = 20; // constant, how much records will be shown per page
        $columns  = $this->get_columns();

        $hidden   = array();
        $sortable = $this->get_sortable_columns();
        // here we configure table headers, defined in our methods
        $this->_column_headers = array($columns, $hidden, $sortable);
        // [OPTIONAL] process bulk action if any
        $this->process_bulk_action();
        // will be used in pagination settings
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name WHERE `payment_status`='Signed'");

        // prepare query params, as usual current page, order by and order direction
        $paged   = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged'] - 1) * $per_page) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'shop_order_id';
        $order   = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'desc';
        // [REQUIRED] define $items array
        // notice that last argument is ARRAY_A, so we will retrieve array
        $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE `payment_status`='Signed' ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);

        // [REQUIRED] configure pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items, // total items defined above
            'per_page'    => $per_page, // per page constant defined at top of method
            'total_pages' => ceil($total_items / $per_page), // calculate pages count
        ));
    }
}
/**
 * Admin page
 * ============================================================================
 *
 * In this part you are going to add admin page for custom table
 *
 */
/**
 * admin_menu hook implementation, will add pages to list credovas and to add new one
 */
function credova_example_admin_menu()
{
    //add_menu_page(__('Credova', 'cltd_example'), __('Credova', 'cltd_example'), 'activate_plugins', 'credovas', 'credova_example_persons_page_handler');
    add_submenu_page('woocommerce', __('Credova Orders', 'cltd_example'), __('Credova Orders', 'cltd_example'), 'activate_plugins', 'credova', 'credova_example_persons_page_handler');
    // add new will be described in next part
    add_submenu_page('persons', __('Add new', 'cltd_example'), __('Add new', 'cltd_example'), 'activate_plugins', 'credova_form', 'credova_example_persons_form_page_handler');
}
add_action('admin_menu', 'credova_example_admin_menu');
/**
 * List page handler
 *
 * This function renders our custom table
 * Notice how we display message about successfull deletion
 * Actualy this is very easy, and you can add as many features
 * as you want.
 *
 * Look into /wp-admin/includes/class-wp-*-list-table.php for examples
 */
function credova_example_persons_page_handler()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'credova_info';
    $table      = new Credova_Table_Example_List_Table();
    $table->prepare_items();

    $message = '';
    if ('delete' === $table->current_action()) {
        $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d', 'cltd_example'), count($_REQUEST['id'])) . '</p></div>';
    }
    if ('refund' === $table->current_action()) {
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_REQUEST['id'])), ARRAY_A);
        if ($item) {
            $refund_status = $item['refund_status'];
            if (!empty($refund_status)) {
                $message = '<div class="updated below-h2" id="message"><p>Refund Status Updated</p></div>';
            }
        }
    }
    ?>
<div class="wrap">

    <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
    <!--h2><?php //_e('Credovas', 'cltd_example')?> <a class="add-new-h2"
                                 href="<?php //echo get_admin_url(get_current_blog_id(), 'admin.php?page=persons_form');?>"><?php //_e('Add new', 'cltd_example')?></a>
    </h2-->
    <?php echo $message; ?>

    <form id="persons-table" method="GET">
        <input type="hidden" name="page" value="<?php echo sanitize_text_field($_REQUEST['page']) ?>"/>
        <?php $table->display()?>
    </form>

</div>
    <?php
}
/**
 * Form for adding andor editing row
 * ============================================================================
 *
 * In this part you are going to add admin page for adding andor editing items
 * You cant put all form into this function, but in this example form will
 * be placed into meta box, and if you want you can split your form into
 * as many meta boxes as you want
 *
 */
/**
 * Form page handler checks is there some data posted and tries to save it
 * Also it renders basic wrapper in which we are callin meta box render
 */
function credova_example_persons_form_page_handler()
{

    global $wpdb;
    $table_name = $wpdb->prefix . 'credova_info'; // do not forget about tables prefix
    $message    = '';
    $notice     = '';
    // this is default $item which will be used for new records
    $default = array(
        'id'    => 0,
        'name'  => '',
        'email' => '',
        'age'   => null,
    );
    // here we are verifying does this request is post back and have correct nonce
    if (isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {
        // combine our default item with request params
        $credova_details  = get_option('woocommerce_credova_settings');
        $credova_testmode = $credova_details['testmode'];

        if ($credova_testmode == 'yes') {
            $credova_testmode = 1;
        } else {
            $credova_testmode = 0;
        }
        $credova_api_username = $credova_details['api_username'];
        $credova_api_password = $credova_details['api_password'];

        /*Delivery Info-------*/
        if (isset($_REQUEST['check_form'])) {
            if ($_REQUEST['check_form'] == 'delivery_info') {
                if (isset($_REQUEST['id'])) {
                    if (empty($_REQUEST['delivery_info'])) {
                        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $_REQUEST['id']), ARRAY_A);

                        if ($item) {
                            $credova_public_id = $item['credova_public_id'];
                            $federal_license   = $item['federal_license'];
                            $customer_state    = $item['customer_state'];
                            $data              = array(
                                //"federalLicenseNumber" => $federal_license,
                                "method"         => sanitize_text_field($_REQUEST['dl_shipping_method']),
                                "address"        => sanitize_text_field($_REQUEST['dl_address']),
                                "address2"       => sanitize_text_field($_REQUEST['dl_address2']),
                                "city"           => sanitize_text_field($_REQUEST['dl_city']),
                                "state"          => sanitize_text_field($_REQUEST['dl_state']),
                                "zip"            => sanitize_text_field($_REQUEST['dl_zip']),
                                "carrier"        => sanitize_text_field($_REQUEST['dl_carrier']),
                                "trackingNumber" => sanitize_text_field($_REQUEST['dl_tracking_number']),
                            );
                            $client = new CredovaClient($credova_api_username, $credova_api_password, $credova_testmode);
                            $client->authenticate();

                            $resp = $client->delivery($credova_public_id, $data);

                            if ($resp['status'] == 'Delivery information created') {
                                $colm_id = array('id' => intval($_REQUEST['id']));
                                $colms   = array('delivery_info' => $resp['status']);
                                $result  = $wpdb->update($table_name, $colms, $colm_id);
                                if ($result) {
                                    $message = __($resp['status'], 'cltd_example');
                                } else {
                                    $notice = __('There was an error while updating item', 'cltd_example');
                                }
                            } else {
                                $message = __('Delivery information Not created', 'cltd_example');
                            }
                        } else {
                            $notice = __('Item not found', 'cltd_example');
                        }
                    } else {
                        $message = __('Delivery Info Already Saved', 'cltd_example');
                    }
                }
            }
        }
        /*-------Delivery Info*/

        /*Return Info-------*/
        //print_r($_REQUEST);
        if (isset($_REQUEST['check_form'])) {
            if ($_REQUEST['check_form'] == 'refund_status') {
                if (isset($_REQUEST['id'])) {
                    if (empty($_REQUEST['refund_status'])) {
                        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $_REQUEST['id']), ARRAY_A);

                        if ($item) {
                            $credova_public_id = $item['credova_public_id'];

                            $data = array(
                                "returnType"   => sanitize_text_field($_REQUEST['returntype']),
                                "returnReason" => sanitize_text_field($_REQUEST['returnreason']),
                                "reason"       => sanitize_text_field($_REQUEST['reason']),
                            );

                            $client = new CredovaClient($credova_api_username, $credova_api_password, $credova_testmode);
                            $client->authenticate();

                            $resp = $client->request_return($credova_public_id, $data);

                            //$resp = $client->return_reasons();

                            //echo "<pre>";print_r($resp);

                            if (empty($resp)) {
                                $colm_id = array('id' => intval($_REQUEST['id']));
                                $colms   = array('refund_status' => 'Refunded');
                                $result  = $wpdb->update($table_name, $colms, $colm_id);
                                $message = __("Success", 'cltd_example');
                            } else {
                                $error   = $resp['errors'][0];
                                $colm_id = array('id' => intval($_REQUEST['id']));
                                $colms   = array('refund_status' => $error);
                                $result  = $wpdb->update($table_name, $colms, $colm_id);
                                $notice  = __('There was an error while updating item', 'cltd_example');
                            }

                        } else {
                            $notice = __('Item not found', 'cltd_example');
                        }
                    } else {
                        $message = __('Return request already sent.', 'cltd_example');
                    }
                }
            }
        }
        /*-------Return Info*/
    } else {
        // if this is not post back we load item to edit or give new one to create
        $item = $default;
        if (isset($_REQUEST['id'])) {
            $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $_REQUEST['id']), ARRAY_A);
            if (!$item) {
                $item   = $default;
                $notice = __('Item not found', 'cltd_example');
            }
        }
    }
    // here we adding our custom meta box
    add_meta_box('persons_form_meta_box', 'Credova data', 'cltd_example_persons_form_meta_box_handler', 'credova', 'normal', 'default');
    ?>
<div class="wrap">
    <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
    <h2><?php _e('Credova', 'cltd_example')?> <a class="add-new-h2"
                                href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=credova'); ?>"><?php _e('back to list', 'cltd_example')?></a>
    </h2>

    <?php if (!empty($notice)): ?>
    <div id="notice" class="error"><p><?php echo $notice ?></p></div>
    <?php endif;?>
    <?php if (!empty($message)): ?>
    <div id="message" class="updated"><p><?php echo $message ?></p></div>
    <?php endif;?>
    <form id="form" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__)) ?>"/>
        <?php /* NOTICE: here we storing id to determine will be item added or updated */?>
        <input type="hidden" name="id" value="<?php echo $item['id'] ?>"/>

        <div class="metabox-holder" id="poststuff">
            <div id="post-body">
                <div id="post-body-content">
                    <?php /* And here we call our custom meta box */?>
                    <?php do_meta_boxes('credova', 'normal', $item);?>
                    <input type="submit" value="<?php _e('Save', 'cltd_example')?>" id="submit" class="button-primary" name="submit">
                </div>
            </div>
        </div>
    </form>
</div>
    <?php
}
/**
 * This function renders our custom meta box
 * $item is row
 *
 * @param $item
 */
function cltd_example_persons_form_meta_box_handler($item)
{
    if (isset($_REQUEST['check_form']) && isset($_REQUEST['delivery_info'])) {
        if ($_REQUEST['check_form'] == 'delivery_info') {
            if (empty($_REQUEST['delivery_info'])) {
                ?>
        <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
            <tbody>
            <tr class="form-field">
                <th valign="top" scope="row">
                    <label for="Shipping Method"><?php _e('Shipping Method', 'cltd_example')?></label>
                </th>
                <td>
                    <input id="dl_shipping_method" name="dl_shipping_method" type="text" style="width: 95%" value="" size="50" class="code" placeholder="Direct or Shipped" required>
                </td>
            </tr>
            <tr class="form-field">
                <th valign="top" scope="row">
                    <label for="Address"><?php _e('Address', 'cltd_example')?></label>
                </th>
                <td>
                    <input id="dl_address" name="dl_address" type="text" style="width: 95%" value="" size="50" class="code" required>
                </td>
            </tr>
            <tr class="form-field">
                <th valign="top" scope="row">
                    <label for="Address2"><?php _e('Address 2', 'cltd_example')?></label>
                </th>
                <td>
                    <input id="dl_address2" name="dl_address2" type="text" style="width: 95%" value="" size="50" class="code" required>
                </td>
            </tr>
            <tr class="form-field">
                <th valign="top" scope="row">
                    <label for="City"><?php _e('City', 'cltd_example')?></label>
                </th>
                <td>
                    <input id="dl_city" name="dl_city" type="text" style="width: 95%" value="" size="50" class="code" required>
                </td>
            </tr>
            <tr class="form-field">
                <th valign="top" scope="row">
                    <label for="State"><?php _e('State', 'cltd_example')?></label>
                </th>
                <td>
                    <input id="dl_state" name="dl_state" type="text" style="width: 95%" value="" size="2" maxlength="2" placeholder="Enter State Code" class="code" required>
                </td>
            </tr>
            <tr class="form-field">
                <th valign="top" scope="row">
                    <label for="Zip"><?php _e('Zip', 'cltd_example')?></label>
                </th>
                <td>
                    <input id="dl_zip" name="dl_zip" type="text" style="width: 95%" value="" size="5" maxlength="5" placeholder="Max. 5 digit zip code" class="code" required>
                </td>
            </tr>
            <tr class="form-field">
                <th valign="top" scope="row">
                    <label for="Carrier"><?php _e('Carrier', 'cltd_example')?></label>
                </th>
                <td>
                    <input id="dl_carrier" name="dl_carrier" type="text" style="width: 95%" value="" size="50" class="code" required>
                </td>
            </tr>
            <tr class="form-field">
                <th valign="top" scope="row">
                    <label for="tracking_number"><?php _e('Tracking Number', 'cltd_example')?></label>
                </th>
                <td>
                    <input id="dl_tracking_number" name="dl_tracking_number" type="text" style="width: 95%" value="" size="50" class="code" required>
                </td>
            </tr>
            </tbody>
        </table>

                <?php
} else {
                ?>
        <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
        <tbody>
            <tr class="form-field">
                <th valign="top" scope="row">
                    <label for="Delivery Info"><?php _e('Delivery Info', 'cltd_example')?></label>
                </th>
                <td>
                    <input id="dl_info" name="dl_info" type="text" style="width: 95%" maxlength="20" value="<?php echo esc_attr($_REQUEST['delivery_info']) ?>" size="50" class="code" disabled required>
                </td>
            </tr>
        </tbody>
        </table>
                <?php
}
        }
    }

    if (isset($_REQUEST['check_form']) && isset($_REQUEST['refund_status'])) {
        if ($_REQUEST['check_form'] == 'refund_status') {
            if (empty($_REQUEST['refund_status'])) {
                ?>
        <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
            <tbody>
            <tr class="form-field">
                <th valign="top" scope="row">
                    <label for="Return Type"><?php _e('Return Type', 'cltd_example')?></label>
                </th>
                <td>
                    <select name="returntype" id="returntype" class="code" required>
                            <option value="1">Redraft</option>
                            <option value="2">Return</option>
                    </select>
                </td>
            </tr>
            <tr class="form-field">
                <th valign="top" scope="row">
                    <label for="Reason"><?php _e('Reason', 'cltd_example')?></label>
                </th>
        <?php
$credova_details  = get_option('woocommerce_credova_settings');
                $credova_testmode = $credova_details['testmode'];

                if ($credova_testmode == 'yes') {
                    $credova_testmode = 1;
                } else {
                    $credova_testmode = 0;
                }
                $credova_api_username = $credova_details['api_username'];
                $credova_api_password = $credova_details['api_password'];
                $client               = new CredovaClient($credova_api_username, $credova_api_password, $credova_testmode);
                $client->authenticate();
                $resp = $client->return_reasons();
                //echo "<pre>";print_r($resp);
                ?>
                <td>
                    <select name="reason" id="reason" class="code" required>
                    <?php
foreach ($resp as $reason) {
                    ?>
                        <option value="<?php echo $reason['publicId']; ?>"><?php echo $reason['description']; ?></option>
                    <?php }?>
                    </select>
                </td>
            </tr>
            </tbody>
        </table>

                <?php
} else {
                ?>
        <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
        <tbody>
            <tr class="form-field">
                <th valign="top" scope="row">
                    <label for="Return Status"><?php _e('Return Status', 'cltd_example')?></label>
                </th>
                <td>
                    <input id="refund_info" name="refund_info" type="text" style="width: 95%" maxlength="20" value="<?php echo esc_attr($_REQUEST['refund_status']) ?>" size="50" class="code" disabled required>
                </td>
            </tr>
        </tbody>
        </table>
                <?php
}
        }
    }
}
