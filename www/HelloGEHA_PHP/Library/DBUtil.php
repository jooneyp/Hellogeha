<?php

    header('Content-type: text/html; charset=utf-8');

	class DBUtil {

		private $ip = "localhost";
		private $id = "test";
		private $pw = "zecSF4FWnLbK6Cbm";

        public function connectDB($DBName) {

			$connection = mysqli_connect($this->ip, $this->id, $this->pw, $DBName);

			if(mysqli_connect_errno()) {
		
				echo "DB 서버 연결 실패".mysqli_connect_error();

                //header("Location: 404.php");
                exit();
		
			}

			else {
				
				return $connection;

			}

            return null;

		}

		public function close($connection, $statement, $result) {
			
			if($connection != null) {
				
				mysqli_close($connection);
				$connection = null;

			}

			else if($statement != null) {

				mysqli_stmt_close($statement);
				$connection = null;

			}
			
			else if($result != null) {

				mysqli_free_result($result);
				$connection = null;

			}
			
		}

	}

?>