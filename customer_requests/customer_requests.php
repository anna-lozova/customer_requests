<?php
/*
	Plugin Name: Customer Requests
    	Description: Use shortcode [add_form] to add the form to your page to get customer requests
	Author: A.L.
*/
if (!defined('ABSPATH')) die();
if (!class_exists('Customer_requests')) {
    class Customer_requests
    {
        private static $instance;

        public static function init()
        {
            if (self::$instance === null) {
                self::$instance = new self;
            }
            return self::$instance;
        }

        public function __construct()
        {
            add_shortcode('add_form', array($this, 'add_form_shortcode'));
            add_action('admin_menu', array($this, 'add_option_CR'));
            add_action('wp_ajax_send_form_cr', array($this, 'save_form_data'));
            add_action('wp_ajax_nopriv_send_form_cr', array($this, 'save_form_data'));
            add_action('wp_footer', array($this, 'enqueue_js'));
            add_action('wp_enqueue_scripts', array($this, 'enqueue_style'));
        }

        public function enqueue_style()
        {
            wp_enqueue_style('cr_css', plugin_dir_url(__FILE__) . 'assets/cr_css.css');
        }

        public function add_form_shortcode()
        {
            return '<form novalidate class="cr_form" enctype="multipart/form-data">
                <div class="cr_form_wrap"><label>Name: <input type="text" name="name" required class="name_cr"></label>
                <label>Email: <input type="email" name="email" required class="email_cr"></label></div>
                <div class="cr_form_wrap"><label>Phone: <input type="tel" name="phone" required class="phone_cr"></label>
                <label>Date: <input type="date" name="date" required class="date_cr"></label></div>
                <div class="response"></div>
                <div class="cr_form_btn"><button type="submit">Submit</button></div>
                </form>';
        }

        public function add_option_CR()
        {
            add_options_page('Customer Requests', 'Customer Requests', 'manage_options', 'Customer Requests', array($this, 'show_option_CR'));
        }

        public function show_option_CR()
        {
            echo '<p>Use shortcode [add_form] to add the form to your page to get customer requests</p>';
            echo '<h2>Customer requests table</h2>';
            $option_data_CR = get_option('data_customer_requests');
            wp_enqueue_style('customer_requests_css', plugin_dir_url(__FILE__) . 'assets/customer_requests_css.css');
            echo '<table class="cr_table"><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Date</th></tr>';
            if ($option_data_CR != NULL) {
                $i = count($option_data_CR);
                foreach (array_reverse($option_data_CR) as $row) {
                    echo '<tr><td>' . $i . '</td>';
                    foreach ($row as $item) {
                        echo "<td>${item}</td>";
                    }
                    echo '</tr>';
                    $i--;
                }
            }
            echo '</table>';
        }

        public function enqueue_js()
        { ?>
            <script>
                const form_cr = document.querySelector('.cr_form');
                if (form_cr) {
                    const ajax_url = '<?php echo admin_url("admin-ajax.php"); ?>',
                        wrap_response = form_cr.querySelector('.response');
                    function validate(el, sym, is_remove = true) {
                        form_cr.querySelector('.cr_form input[type="'+el+'"]').onkeypress = e => sym.indexOf(String.fromCharCode(e.which)) >= 0 ? !is_remove : is_remove;
                    }

                    function form_cr_submit(e) {
                        e.preventDefault();
                        let form = this,
                            xhr = new XMLHttpRequest(),
                            form_data = new FormData(form);
                        form.classList.remove('send');
                        xhr.open("POST", ajax_url + '?action=send_form_cr', true);
                        xhr.addEventListener('load', () => {
                            let response = xhr.response,
                                errors = {
                                    'empty_name': 'Insert name. This field is required',
                                    'empty_email': 'Insert email. This field is required',
                                    'empty_phone': 'Insert phone. This field is required',
                                    'empty_date': 'Insert date. This field is required',
                                    'incorrect_name': 'Insert correct name',
                                    'incorrect_email': 'Insert correct email',
                                    'incorrect_phone': 'Insert correct phone'
                                },
                                errors_arr = Object.keys(errors);
                            if (errors_arr.includes(response)) {
                                wrap_response.textContent = errors[response];
                                form.classList.add('error');
                            } else if (response === 'success') {
                                wrap_response.textContent = 'Request was send';
                                form.classList.remove('error');
                                form.classList.add('send');
                                form.reset();
                            } else {
                                wrap_response.textContent = 'Request wasn\'t send';
                                form.classList.add('error');
                            }
                        });
                        xhr.send(form_data);
                    }

                    validate('email', "qwertyuiopasdfghjklzxcvbnm@1234567890._", !1);
                    validate('tel', "1234567890", !1);
                    validate('text', "!@#$%^&*()+=;:`~\\|?/.><,\"");
                    form_cr.addEventListener('submit', form_cr_submit);
                }
            </script><?php
        }

        public function save_form_data()
        {
            $name_cr = $_POST['name'] ? $_POST['name'] : '';
            $email_cr = $_POST['email'] ? $_POST['email'] : '';
            $phone_cr = $_POST['phone'] ? $_POST['phone'] : '';
            $date_cr = $_POST['date'] ? $_POST['date'] : '';
            $item_customer_requests = array(
                'name' => $name_cr,
                'email' => $email_cr,
                'phone' => $phone_cr,
                'date' => $date_cr
            );
            if (empty($name_cr)) {
                echo 'empty_name';
                wp_die();
            } elseif (!preg_match("/^\w+$/", $name_cr)) {
                echo 'incorrect_name';
                wp_die();
            } elseif (empty($email_cr)) {
                echo 'empty_email';
                wp_die();
            } elseif (!filter_var($email_cr, FILTER_VALIDATE_EMAIL)) {
                echo 'incorrect_email';
                wp_die();
            } elseif (empty($phone_cr)) {
                echo 'empty_phone';
                wp_die();
            } elseif (!preg_match("/^\d+$/", $phone_cr)) {
                echo 'incorrect_phone';
                wp_die();
            } elseif (empty($date_cr)) {
                echo 'empty_date';
                wp_die();
            } else {
                echo 'success';
                $data_customer_requests = get_option('data_customer_requests') ? get_option('data_customer_requests') : [];
                array_push($data_customer_requests, $item_customer_requests);
                update_option('data_customer_requests', $data_customer_requests);
                wp_die();
            }
        }
    }

    Customer_requests::init();
}
