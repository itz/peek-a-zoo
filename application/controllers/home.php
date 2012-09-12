<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Home extends CI_Controller {

    protected $limit = 10;

    function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->library('fungsi');
        $this->load->library('curl');
        $this->load->helper('url');
        $this->load->database();
        $this->load->model('user');

        $this->load->library('facebook', array(
            'appId' => $this->config->item('facebook_application_id'),
            'secret' => $this->config->item('facebook_secret_key'),
            'cookie' => true
        ));
    }

    function index() {
        $sesi = $this->session->userdata('idadmin');
        if ($sesi == "") {
            redirect('home/login');
        } else {
            redirect('leaderboard/facebook');
        }
    }

    function login() {
        $content['admin'] = '';
        $content['names'] = '';
        $content['sec1'] = '';

        $this->session->sess_destroy();
        $this->load->library('form_validation');
        $this->form_validation->set_rules('userlogin', 'Email', 'trim|required|valid_email');
        $this->form_validation->set_rules('passlogin', 'Password', 'trim|required');

        if ($this->form_validation->run() === TRUE) {
            $data['user'] = $this->input->post('userlogin', true);
            $data['pass'] = $this->fungsi->acak($this->input->post('passlogin', true));

            $count = $this->user->user_chk_login($data);
            if ($count) {
                $keyword['idadmin'] = $count->id;
                $keyword['idlevel'] = $count->account_group;
                $keyword['display'] = $count->account_displayname;
                $this->session->set_userdata($keyword);
                redirect('leaderboard/facebook');
            }
        }
        $content['content'] = $this->load->view('home/login', $content, true);
        $this->load->view('body', $content);
    }

    function about() {
        $sesi = $this->session->userdata('idadmin');
        $name = $this->session->userdata('display');

        $content['admin'] = $sesi;
        $content['names'] = $name;

        $content['title'] = 'About';
        $content['content'] = $this->load->view('home/about', $content, true);
        $this->load->view('body', $content);
    }

    function privacy() {
        $sesi = $this->session->userdata('idadmin');
        $name = $this->session->userdata('display');

        $content['admin'] = $sesi;
        $content['names'] = $name;

        $content['title'] = 'Privacy Policy';
        $content['content'] = $this->load->view('home/privacy', $content, true);
        $this->load->view('body', $content);
    }

    function tos() {
        $sesi = $this->session->userdata('idadmin');
        $name = $this->session->userdata('display');

        $content['admin'] = $sesi;
        $content['names'] = $name;

        $content['title'] = 'Terms of Service';
        $content['content'] = $this->load->view('home/tos', $content, true);
        $this->load->view('body', $content);
    }

    function contact() {
        $sesi = $this->session->userdata('idadmin');
        $name = $this->session->userdata('display');

        $content['admin'] = $sesi;
        $content['names'] = $name;

        $content['title'] = 'Contact Us';
        $content['content'] = $this->load->view('home/contact', $content, true);
        $this->load->view('body', $content);
    }

    function logout() {
        $this->session->unset_userdata('idadmin');
        $this->session->unset_userdata('idlevel');
        $this->session->unset_userdata('display');
        $this->session->sess_destroy();
        session_unset();
        unset($_SESSION);
        redirect('home/login');
    }

    function facebookconnect() {
        $sesi = $this->session->userdata('idadmin');
        $name = $this->session->userdata('display');

        if ($sesi == "") {
            redirect('home/login');
        }

        $data['facebook_uid'] = NULL;
        $data['facebook_token'] = NULL;

        $user = $this->facebook->getUser();
        $data['facebook_uid'] = $user;
        $data['facebook_token'] = $this->facebook->getAccessToken();

        if ($data['facebook_uid']) {
            $user_profile = $this->facebook->api('/me');
            $data['facebook_name'] = $user_profile['name'];
            $data['account_id'] = $sesi;

            $this->db->select('id');
            $check = $this->db->get_where('facebook', array('facebook_uid' => $data['facebook_uid']))->row();
            if ($check) {
                $this->db->where('id', $check->id);
                $this->db->update('facebook', $data);
                $new = $check->id;
            } else {
                $this->db->insert('facebook', $data);
                $new = $this->db->insert_id();
            }
            redirect('fb/manage/' . $data['facebook_uid']);
        } else {
            redirect('settings');
        }
    }

    function pages() {
        $params = array(
            'method' => 'fql.query',
            'query' => "SELECT page_id, type from page_admin WHERE uid=me()",
        );
        $data = $this->facebook->api($params);
        foreach ($data as $row) {
            $page = array();
            $data_page = $this->facebook->api('/' . $row['page_id']);

            $page['pages_uid'] = $data_page['id'];
            $page['pages_admin'] = 1;
            $page['pages_name'] = $data_page['name'];
            $page['pages_link'] = $data_page['link'];
            if (isset($data_page['picture']) && $data_page['picture'] != '')
                $page['pages_picture'] = $data_page['picture'];
            if (isset($data_page['category']) && $data_page['category'] != '')
                $page['pages_category'] = $data_page['category'];
            if (isset($data_page['username']) && $data_page['username'] != '')
                $page['pages_username'] = $data_page['username'];
            if (isset($data_page['release_date']) && $data_page['release_date'] != '')
                $page['pages_release_date'] = $data_page['release_date'];
            if (isset($data_page['description']) && $data_page['description'] != '')
                $page['pages_desc'] = $data_page['description'];

            $this->db->select('id');
            $check = $this->db->get_where('facebook_pages', array('pages_uid' => $row['page_id']))->row();
            if ($check) {
                $this->db->where('id', $check->id);
                $this->db->update('facebook_pages', $page);
            } else {
                $this->db->insert('facebook_pages', $page);
            }
        }
    }

    function twitterconnect() {
        $sesi = $this->session->userdata('idadmin');
        $name = $this->session->userdata('display');

        if ($sesi == "") {
            redirect('home/login');
        }

        include(APPPATH . 'third_party/tmhOAuth.php');
        $tmhOAuth = new tmhOAuth(array(
                    'consumer_key' => $this->config->item('tweet_consumer_key'),
                    'consumer_secret' => $this->config->item('tweet_consumer_secret')
                ));

        if (isset($_REQUEST['oauth_verifier'])) {
            /* oauth_verifier */
            $tmhOAuth->config['user_token'] = $_SESSION['oauth']['oauth_token'];
            $tmhOAuth->config['user_secret'] = $_SESSION['oauth']['oauth_token_secret'];

            $code = $tmhOAuth->request('POST', $tmhOAuth->url('oauth/access_token', ''), array(
                        'oauth_verifier' => $_REQUEST['oauth_verifier']
                    ));

            if ($code == 200) {
                /* oauth verifier success */
                $access_token = $tmhOAuth->extract_params($tmhOAuth->response['response']);
                if (is_array($access_token) && count($access_token) != '0') {
                    /* get additional user data */
                    $tmhOAuth->config['user_token'] = $access_token['oauth_token'];
                    $tmhOAuth->config['user_secret'] = $access_token['oauth_token_secret'];

                    $code = $tmhOAuth->request('GET', $tmhOAuth->url('1/account/verify_credentials'));
                    if ($code == 200) {
                        /* get data success */
                        $response = json_decode($tmhOAuth->response['response']);

                        $data = array(
                            'account_id' => $sesi,
                            'twitter_uid' => $response->id,
                            'twitter_name' => $response->name,
                            'twitter_username' => $response->screen_name,
                            'twitter_token' => $access_token['oauth_token'],
                            'twitter_secret' => $access_token['oauth_token_secret'],
                            'profile_image_url' => $response->profile_image_url,
                            'description' => $response->description,
                            'created_at' => date('Y-m-d h:i:s', strtotime($response->created_at)),
                            'time_zone' => $response->time_zone,
                            'lang' => $response->lang,
                            'verified' => $response->verified,
                            'protected' => $response->protected
                        );
                        $this->db->select('id');
                        $check_account = $this->db->get_where('twitter', array('twitter_uid' => $response->id))->row();
                        if (!$check_account) {
                            $this->db->insert('twitter', $data);
                        } else {
                            $this->db->where('id', $check_account->id);
                            $this->db->update('twitter', $data);
                        }
                        $this->__restartservice();
                        redirect('auto/twGetFirstData/' . $response->id);
                    }
                }
                unset($_SESSION['oauth']);
            }
        } else {
            /* twitter connect start */
            $callback = site_url('home/twitterconnect');

            $params = array('oauth_callback' => $callback);
            $code = $tmhOAuth->request('POST', $tmhOAuth->url('oauth/request_token', ''), $params);

            if ($code == 200) {
                /* token request available */
                $_SESSION['oauth'] = $tmhOAuth->extract_params($tmhOAuth->response['response']);
                $url = $tmhOAuth->url('oauth/authorize', '') . "?oauth_token={$_SESSION['oauth']['oauth_token']}&force_login=1";
                redirect($url);
            } else {
                echo $code;
            }
        }
        redirect('settings');
    }

    function gaconnect() {
        $sesi = $this->session->userdata('idadmin');
        $name = $this->session->userdata('display');

        if ($sesi == "") {
            redirect('home/login');
        }

        require APPPATH . 'third_party/google-api-php-client/src/apiClient.php';
        require APPPATH . 'third_party/google-api-php-client/src/contrib/apiOauth2Service.php';

        $cache_path = $this->config->item('cache_path');
        $GLOBALS['apiConfig']['ioFileCache_directory'] = ($cache_path == '') ? APPPATH . 'cache/' : $cache_path;

        $googleauth = new apiClient();
        $googleauth->setApplicationName($this->config->item('application_name', 'analytics'));
        $googleauth->setClientId($this->config->item('client_id', 'analytics'));
        $googleauth->setClientSecret($this->config->item('client_secret', 'analytics'));
        $googleauth->setRedirectUri($this->config->item('redirect_uri', 'analytics'));
        $googleauth->setDeveloperKey($this->config->item('api_key', 'analytics'));
        $googleauth->setAccessType($this->config->item('access_type', 'analytics'));
        $googleauth->setScopes($this->config->item('scopes', 'analytics'));

        $userdata = new apiOauth2Service($googleauth);

        if (isset($_GET['code'])) {
            $googleauth->authenticate();
            $token = $googleauth->getAccessToken();
            $token_data = json_decode($token, true);
            $user = $userdata->userinfo->get();
            if ($token_data && $user) {
                $db['name'] = $user['name'];
                $db['username'] = $user['email'];
                $db['acc_token'] = $token;
                $db['acc_access_token'] = $token_data['access_token'];
                $db['acc_token_type'] = $token_data['token_type'];
                $db['acc_expires_in'] = $token_data['expires_in'];
                $db['acc_id_token'] = $token_data['id_token'];
                $db['acc_refresh_token'] = $token_data['refresh_token'];
                $db['acc_created'] = $token_data['created'];
                $db['acc_id'] = $user['id'];
                $db['acc_verified_email'] = $user['verified_email'];

                if (isset($user['link']) && $user['link'] != '')
                    $db['acc_link'] = $user['link'];
                if (isset($user['picture']) && $user['picture'] != '')
                    $db['acc_picture'] = $user['picture'];
                if (isset($user['gender']) && $user['gender'] != '')
                    $db['acc_gender'] = $user['gender'];
                if (isset($user['locale']) && $user['locale'] != '')
                    $db['acc_locale'] = $user['locale'];

                $db['postdate'] = date('Y-m-d H:i:s');

                $this->db->select('id');
                $check_account = $this->db->get_where('ga_account', array('username' => $db['username']))->row();
                if (!$check_account) {
                    $this->db->insert('ga_account', $db);
                    $id = $this->db->insert_id();
                } else {
                    $this->db->where('id', $check_account->id);
                    $this->db->update('ga_account', $db);
                    $id = $check_account->id;
                }
                redirect('ganalytics/manage/' . $id);
            } else {
                redirect('settings');
            }
        } else {
            $authUrl = $googleauth->createAuthUrl();
            redirect($authUrl);
        }
    }

    function retrack() {
        $this->__restartservice();
        redirect('settings');
    }

    function __restartservice() {
        if (isset($_GET['goto']) && $_GET['goto'] != '')
            $goto = $_GET['goto']; else
            $goto = '';

        $user = $this->uri->segment(3, 0);

        $get_uid = "ps -ef |grep uli_timeline |grep -v grep | awk 2>&1";
        exec($get_uid, $out);

        sleep(1);
        if ($out) {
            $kill = "kill -9 " . $out[0] . " > /dev/null 2>&1";
            exec($kill, $out2);
        }
        sleep(1);

        $start = "nohup php /home/ulieye/public_html/index.php stream uli_timeline > /dev/null 2>&1 & echo $!";
        $uid = exec($start, $op);
    }

}
