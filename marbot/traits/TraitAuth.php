<?php
trait TraitAuth {
	private function login(){
		$user	= isset($_POST['dt']['user'])?$_POST['dt']['user']:false;
		$pass	= isset($_POST['dt']['pass'])?$_POST['dt']['pass']:false;
		
		if(!$user||!$pass){
			$this->retError("Data tidak valid...");
		} else {
			try {
				$pdo = getDbConnection();
				$stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE username = :u AND password = :p");
				$stmt->execute([':u' => $user, ':p' => $pass]);
				$userData = $stmt->fetch();
				
				if ($userData) {
					$_SESSION["user_id"] = $userData['username'];
					$_SESSION["role"]    = $userData['role'];
					$this->registered=true;
					$this->retSuccess();
				} else {
					$this->retError("Username atau password salah...");
				}
			} catch (Exception $e) {
				$this->retError("Database error: " . $e->getMessage());
			}
		}
	}

	private function logout(){
        $_SESSION = array();
        session_destroy();
		$this->registered=false;
		$this->retSuccess();
	}

}
