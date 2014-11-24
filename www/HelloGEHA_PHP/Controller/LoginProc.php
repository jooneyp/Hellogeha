<?php

    header('Content-type: text/html; charset=utf-8');

    /* DB Settings */
    define("database_name","test");
    define("user_table_name", "user");

    /* E-Mail Settings */
    define("admin_email", "hello@hellogeha.com");
    define("login_address", "127.0.0.1/HGH/View/login.html");

    /* Login class start */
    class Login {

        /* SQL Queries */
        private $query_login    = "SELECT username, email, pass_hash FROM ".user_table_name." WHERE username = ? OR email = ? ;";
        private $query_recovery = "SELECT username, email FROM ".user_table_name." WHERE email = ? ;";
        private $query_temp_pw  = "UPDATE ".user_table_name." SET pass_hash = ? WHERE email = ? ;";

        /* Creating database variables */
        private $db_con = null;
        private $db_rs  = null;
        private $db_st  = null;

        /* Creating troubleshooting variables */
        public $error    = array();
        public $errorCode   = null;

        /* Constructor (Automatically starts when object of this class is created) */
        public function __construct() {

//            /* Redirect to HTTPS if HTTP connection */
//            if(!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == ""){
//
//                $redirect = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
//                header("Location: $redirect");
//
//            }

            /* Start DB Connection */
            /* if any errors on DB connection */
            if(!$this->connect()) {

                /* Set ErrorCode */
                $this->errorCode   = -1;

                /* error variable will tell you what is error */
                $this->error[]     = "DB Connection error!\nPlease check DB name or DB settings.";

                /* FOR DEBUG */
                die($this->error);

                /* ON SERVICE */
                /* header("Location: 505.html");*/

            }

            /* Start session */
            session_start();

            /* if logout */
            if(isset($_POST["logout"])) {

                /* call logout function */
                $this->doLogout();

            }

            /* if login */
            else if (isset($_POST["login"])) {

                /* For SSL Connection */
//                if(!isset($_SERVER['HTTPS'])) {
//
//                    die('Error: Only HTTPS request are supported!');
//                    /* 수정이 필요함 */
//
//                }

                /* call login function */
                $this->doLogin();

            }


            /* if OAuth */
            else if(isset($_POST["oauth"])) {

                $this->oauth($_POST["provider"]);

            }

            /* Password Recovery */
            else if(isset($_POST["recovery"])) {

                $this->recovery();

            }

            /* if there have no parameters or wrong parameters */
            else {

                /* Set ErrorCode */
                $this->errorCode   = -100;
                die('WRONG PARAMETERS!');

            }

        }

        /* Database Connection */
        private function connect() {

            /* Database Utility */
            require_once("./Library/DBUtil.php");

            /* Start Database connection - initialize object */
            $db = new DBUtil;

            /* and connect with saving connection to db_con variable */
            $this->db_con = $db->connectDB(database_name);

            /* Try to change db charset to UTF-8 */
            if(!$this->db_con->set_charset("utf8")) {

                /* Set ErrorCode */
                $this->errorCode   = -2;

                /* error variable will tell you what is error */
                $this->error[]     = "Can't change charset to UTF-8.".$this->db_con->error;
                $this->close();

                /* ON SERVICE */
                /* header("Location: 505.html");*/

            }

            /* if db_con is empty (means error on db connection) */
            if(!$this->db_con) {

                /* Set ErrorCode */
                $this->errorCode   = -1;

                return false;

            } else {

                return true;

            }

        }

        /* Database Connection close */
        private function close() {

            /* Database Utility */
            require_once("./Library/DBUtil.php");

            /* Start Database connection - initialize object */
            $db = new DBUtil;

            /* and close connection with setting all db variables to null data */
            $db->close($this->db_con, $this->db_st, $this->db_rs);

        }

        /* Password Generate - Old One - Renewed to MD5 see under */
//        private function generatePassword($length) {
//
//            /* setting random seed */
//            srand(time(null));
//
//            /* Results */
//            $generatedPassword = "";
//
//            /* Dictionary */
//            $vowels_low          = 'aeiou';
//            $consonants_low     = 'bcdfghjklnmpqrstvwxyz';
//            $vowels_caps         = 'AEIOU';
//            $consonants_caps    = 'BCDFGHIJKLNMPQRSTVWXYZ';
//            $symbols             = '!@#$+=-_?<>()[]{}';
//
//            /* Generating */
//            for ($i = 0; $i < $length; $i++) {
//
//                /* Make random number between 0 - 4 */
//                $random = rand(0, 4);
//
//                if ($random == 0) {
//
//                    $generatedPassword .= $vowels_low[(rand() % strlen($vowels_low))];
//
//                } else if ($random == 1) {
//
//                    $generatedPassword .= $vowels_caps[(rand() % strlen($vowels_caps))];
//
//                } else if ($random == 2) {
//
//                    $generatedPassword .= $consonants_low[(rand() % strlen($consonants_low))];
//
//                } else if ($random == 3) {
//
//                    $generatedPassword .= $consonants_caps[(rand() % strlen($consonants_caps))];
//
//                } else {
//
//                    $generatedPassword .= $symbols[(rand() % strlen($symbols))];
//
//                }
//
//            }
//
//            return $generatedPassword;
//
//        }

        /* Password Generate - MD5 */
        private function generatePassword($length) {

            /* setting random seed */
            srand(time(null));

            /* Results */
            $generatedPassword = rand();
            $generatedPassword = md5($generatedPassword);

            return substr($generatedPassword, 0, $length);

        }

        /* Password E-Mail send */
         /* @address :  수신자
         *  @password:  패스워드 값 */
        private function sendPasswordEmail($address, $password) {

            /* 이메일 헤더 작성 */
            $headers =   'From: '.admin_email."\r\n"
                        .'Reply-to: '.admin_email."\r\n"
                        .'X-Mailer: FormMailer'."\r\n"
                        .'MIME-Version: 1.0'."\r\n"
                        .'Content-Type: text/html; charset=UTF-8'."\r\n"
                        .'X-Priority: 1'."\r\n"
                        .'X-MSMail-Priority: High'."\r\n"
                        .'Content-Transfer-Encoding: base64';

            /* 이메일 제목 작성 */
            $subject = "=?UTF-8?B?".base64_encode("HelloGEHA Temporary password notification")."?=\n";

            /* 이메일 컨텐츠 */
            $contents = '
                        <html>
                        <head>
                          <title>HelloGEHA Temporary password notification.</title>
                        </head>
                        <body>
                          <h2>Your temporary password is: </h2>'.$password.'
                          <h3>You can login in <a href="'.login_address.'">here</a>.</h3>
                        </body>
                        </html>
                        ';

            $message = base64_encode($contents);

            flush();

            return mail($address, $subject, $message, $headers);

        }

        /* Login - Generic */
        private function doLogin() {

            /* If username form is empty */
            if(empty($_POST['login-username'])) {

                /* Set ErrorCode */
                $this->errorCode   = -10;

                /* error variable will tell you what is error */
                $this->error[]     = "Username is empty!";

                /* close connection */
                $this->close();

            }

            /* If password form is empty */
            else if(empty($_POST['login-password'])) {

                /* Set ErrorCode */
                $this->errorCode   = -11;

                /* error variable will tell you what is error */
                $this->error[]     = "Password is empty!";

                /* close connection */
                $this->close();

            }

            /* And... if both are filled */
            else if(!empty($_POST['login-username']) && !empty($_POST['login-password'])) {

                /* Finally, If there have no errors on connection(just checking error exists), Login will begin */
                if(!$this->db_con->errno) {

                    /* Make username to string */
                    $username = $_POST['login-username'];

                    /* Make prepared statement */
                    $this->db_st = $this->db_con->prepare($this->query_login);

                    /* bind parameters */
                    $this->db_st->bind_Param("ss", $username, $username);

                    /* AND QUERY IT!! (also save the query state in variable) */
                    $execute_result = $this->db_st->execute();

                    /* Check Query executed nicely */
                    if($execute_result) {

                        /* Save query result in result set... */
                       $this->db_rs = $this->db_st->get_result();

                        /* And check the result */
                        /* If there have a matching ID, it will return 1 */
                        if($this->db_rs->num_rows == 1) {

                            /* It's time to check password */
                            /* Save the results data in variable as object */
                            $result_obj = $this->db_rs->fetch_object();

                            /* And compare hashes (if it's correct, it returns 1) */
                            if(password_verify($_POST['login-password'], $result_obj->pass_hash)) {

                                /* If password is correct */
                                /* Now creating sessions */
                                $_SESSION['username'] = $result_obj->username;
                                $_SESSION['email'] = $result_obj->email;
                                $_SESSION['is_Logged_in'] = true;

                            } else {

                                /* If password is not correct */
                                $this->error[] = "Wrong Password!";

                                /* Set ErrorCode */
                                $this->errorCode   = -12;

                            }

                        } else {

                            /* If There has no username match */
                            $this->error[] = "No matching username!";

                            /* Set ErrorCode */
                            $this->errorCode   = -12;

                        }

                    } else {

                        /* If query didn't execute nicely */
                        $this->error[] = "Cannot execute Query!";

                        /* Set ErrorCode */
                        $this->errorCode   = -3;

                        /* FOR DEBUG */
                        die($this->error);

                        /* ON SERVICE */
                        /* header("Location: 505.html");*/

                    }

                } else {

                    /* If there have a DB error */
                    $this->error[] = "DB Error!".$this->db_con->error;

                    /* Set ErrorCode */
                    $this->errorCode   = -1;

                    /* FOR DEBUG */
                    die($this->error);

                    /* ON SERVICE */
                    /* header("Location: 505.html");*/

                }

            } else {

                /* Something strange error...*/
                $this->error[] = "Call Junny!! because It cannot be happen.";

                /* FOR DEBUG */
                die($this->error);

                /* ON SERVICE */
                /* header("Location: 505.html");*/

            }

            /* close connection */
            $this->close();

        } /* Do Login Ended! */

        /* Logout - All */
        public function doLogout() {

            /* Remove user's session */
            $_SESSION = array();
            session_destroy();

        } /* Do Logout Ended! */

        /* Check Login status */
        public function chkLoginStat() {

            /* If there have a session named is_Logged_in and the session's value is true it means user is logged in*/
            if((isset($_SESSION['is_Logged_in']) == true) && ($_SESSION['is_Logged_in'] == true)) {

                return true;

            } else {

                return false;

            }

        } /* Check Login status ended */

        /* Account Recovery */
        public function recovery() {

            /* If E-Mail form is empty */
            if(empty($_POST['login-email'])) {

                /* Set ErrorCode */
                $this->errorCode   = -13;

                /* error variable will tell you what is error */
                $this->error[]     = "E-Mail is empty!";

                /* close connection */
                $this->close();

            }

            /* If E-Mail don't have '@' and '.'  */
            else if ((!strpos($_POST['login-email'], '@')) && (!strpos($_POST['login-email'], '.'))) {

                /* Set ErrorCode */
                $this->errorCode   = -15;

                /* error variable will tell you what is error */
                $this->error[]     = "Wrong E-Mail data.";

                /* close connection */
                $this->close();

            }

            /* And if it filled and right e-mail */
            else {

                /* Finally, If there have no errors on connection(just checking error exists), Checking E-Mail will begin */
                if(!$this->db_con->errno) {

                    /* Make username to string */
                    $email = $_POST['login-email'];

                    /* Make prepared statement */
                    $this->db_st = $this->db_con->prepare($this->query_recovery);

                    /* bind parameters */
                    $this->db_st->bind_Param("s", $email);

                    /* AND QUERY IT!! (also save the query state in variable) */
                    $execute_result = $this->db_st->execute();

                    /* Check Query executed nicely */
                    if($execute_result) {

                        /* Save query result in result set... */
                        $this->db_rs = $this->db_st->get_result();

                        /* And check the result */
                        /* If there have a matching E-Mail, it will return 1 */
                        if($this->db_rs->num_rows == 1) {

                            /* Save the results data in variable as object */
                            $result_obj = $this->db_rs->fetch_object();

                            $generatedPW = $this->generatePassword(12);
                            $email       = $result_obj->email;

                            if($this->sendPasswordEmail($email, $generatedPW)) {

                                $pass_hash = password_hash($generatedPW, PASSWORD_DEFAULT);

                                /* Make prepared statement */
                                $this->db_st = $this->db_con->prepare($this->query_temp_pw);

                                /* bind parameters */
                                $this->db_st->bind_Param("ss", $pass_hash, $email);

                                /* AND QUERY IT!! (also save the query state in variable) */
                                $execute_result = $this->db_st->execute();

                                /* Check Query executed nicely */
                                if($execute_result) {

                                    /* FOR DEBUG */
                                    echo "Done! PW-Hash: ".$pass_hash;

                                    /* ON SERVICE */
                                    /* header("Location: ./View/login.html");*/

                                } else {

                                    /* If query didn't execute nicely */
                                    $this->error[] = "Cannot execute Query!";

                                    /* Set ErrorCode */
                                    $this->errorCode   = -3;

                                    /* FOR DEBUG */
                                    die($this->error);

                                    /* ON SERVICE */
                                    /* header("Location: 505.html");*/

                                }

                            } else {

                                /* Set ErrorCode */
                                $this->errorCode   = -16;

                                /* error variable will tell you what is error */
                                $this->error[]     = "Failed to send E-Mail.";

                                /* FOR DEBUG */
                                die($this->error);

                                /* ON SERVICE */
                                /* header("Location: 505.html");*/

                            }

                        } else {

                            echo "Cannot find the email";

                            /* If There has no E-Mail match */
                            $this->error[] = "No matching E-Mail!";

                            /* Set ErrorCode */
                            $this->errorCode   = -14;

                        }

                    } else {

                        /* If query didn't execute nicely */
                        $this->error[] = "Cannot execute Query!";

                        /* Set ErrorCode */
                        $this->errorCode   = -3;

                        /* FOR DEBUG */
                        die($this->error);

                        /* ON SERVICE */
                        /* header("Location: 505.html");*/

                    }

                } else {

                    /* If there have a DB error */
                    $this->error[] = "DB Error!".$this->db_con->error;

                    /* Set ErrorCode */
                    $this->errorCode   = -1;

                    /* FOR DEBUG */
                    die($this->error);

                    /* ON SERVICE */
                    /* header("Location: 505.html");*/

                }

            }

            /* close connection */
            $this->close();

        }

        /* OAuth */
        public function oauth($provider) {

            if(empty($provider)) {



            }




        }

    } /* class ended */