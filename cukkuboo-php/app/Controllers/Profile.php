<?php
namespace App\Controllers;
use App\Models\AuthModel;
require 'public/mailer/Exception.php';
require 'public/mailer/PHPMailer.php';
require 'public/mailer/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Libraries\AuthService;
use App\Models\LoginModel;
use App\Helpers\AuthHelper; 

class Profile extends BaseController
{
	public function __construct() {
		
		$this->session = \Config\Services::session();
		$this->input = \Config\Services::request();
		$this->authService = new AuthService();
        $this->loginModel = new LoginModel();
	}
    public function index() {
        // $authHeader = $this->request->getHeaderLine('Authorization');
        $authHeader = AuthHelper::getAuthorizationToken($this->request);
        $user = $this->authService->getAuthenticatedUser($authHeader);
        if ($user) {
            $data['user'] = $this->session->get('username');
            $data['email'] = $this->session->get('email');
                // $data['utype'] = $this->session->get('us_type');
                // $data['menu'] = 1;
                // $data['getTeam'] = $this->AuthModel->getTeamMembers();
                // $data['desArr'] = array("1"=>'Management',"2"=>"Team Lead","3"=>"Developer/Designer","4"=>"QA","5"=>"Guest","6"=>"Client");
                // $data['viewArr'] = array("2"=>'Admin Access',"3"=>'Restricted Access',"4"=>"All Access","5"=>"Guest Access","6"=>"Client Access");
            $template = view('common/header', $data);
            $template .= view('common/leftmenu');
            $template .= view('user');
            $template .= view('common/footer');
            $template .= view('pagescripts/user');
            return $template;
        }
        else {
            return redirect()->to(base_url()); 
            }
	}
	public function resetPassword()
	{
		$json = $this->request->getJSON(true);

		$email = isset($json['email']) ? trim($json['email']) : null;
		$otpInput = isset($json['otp']) ? trim($json['otp']) : null;
		$newPassword = isset($json['new_password']) ? $json['new_password'] : null;
		$confirmPassword = isset($json['confirm_password']) ? $json['confirm_password'] : null;

		if (!$email) {
			return $this->response->setJSON([
				'success' => false,
				'message' => 'Email is required.'
			]);
		}

		$user = $this->loginModel->where('email', $email)->first();
		if (!$user) {
			return $this->response->setJSON([
				'success' => false,
				'message' => 'User not found.'
			]);
		}
		
		if($email && $newPassword && $confirmPassword && $otpInput) {
			
			if (strpos($user['password'], '_') === false) {
				return $this->response->setJSON([
					'success' => false,
					'message' => 'OTP verification not found or has expired. Please request a new OTP.'
				]);
			}

			list($storedOtp, $expiry) = explode('_', $user['password']);

			if ($otpInput != $storedOtp || time() > (int)$expiry) {
				return $this->response->setJSON([
					'success' => false,
					'message' => 'Invalid or expired OTP.'
				]);
			}

			if ($newPassword !== $confirmPassword) {
				return $this->response->setJSON([
					'success' => false,
					'message' => 'Passwords do not match.'
				]);
			}

			$hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
			$this->loginModel->update($user['user_id'], ['password' => $hashedPassword]);

			return $this->response->setJSON([
				'success' => true,
				'message' => 'Password reset successfully.'
			]);
		}
		else if($email && $otpInput) {
			
			if (strpos($user['password'], '_') === false) {
				return $this->response->setJSON([
					'success' => false,
					'message' => 'Invalid OTP format or expired.'
				]);
			}

			list($storedOtp, $expiry) = explode('_', $user['password']);

			if ($otpInput != $storedOtp) {
				return $this->response->setJSON([
					'success' => false,
					'message' => 'Invalid OTP.'
				]);
			}

			if (time() > (int)$expiry) {
				return $this->response->setJSON([
					'success' => false,
					'message' => 'OTP has expired.'
				]);
			}

			return $this->response->setJSON([
				'success' => true,
				'message' => 'OTP verified. You may now reset your password.'
			]);
		}
		else if($email) {
			
			$otp = rand(100000, 999999);
			$otpExpiry = time() + 300; 
			$this->loginModel->update($user['user_id'], [
				'password' => $otp . '_' . $otpExpiry
			]);
			
			$api_url = 'https://v4cstaging.co.in/cukkuboo-emailer/cukkuboo-email.php';
			$ch = curl_init($api_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array("email"=>$email,"name"=>$user['username'],"otp"=>$otp)));
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			$response = curl_exec($ch);
			// print_r($response);
			if($response) {
				return $this->response->setJSON([
					'success' => true,
					'message' => 'OTP sent to your email.'
				]);
			}
			curl_close($ch);
		}
		else {
			return $this->response->setJSON([
				'success' => false,
				'message' => 'Invalid request.'
			]);
		}
		
		
		/*if (!$otpInput && !$newPassword && !$confirmPassword) {
			$otp = rand(100000, 999999);
			$otpExpiry = time() + 300; 
			$this->loginModel->update($user['user_id'], [
				'password' => $otp . '_' . $otpExpiry
			]);

			try {
				$mail = new PHPMailer(true);
				$mail->isSMTP();
				$mail->Host       = 'mail.smartlounge.online';
				$mail->SMTPAuth   = true;
				$mail->Username   = 'no-reply@smartlounge.online';
				$mail->Password   = 'JujjmH9WkpL7AgP4TgHe';
				$mail->SMTPSecure = 'ssl';
				$mail->Port       = 465;
				$mail->setFrom('no-reply@smartlounge.online', 'Promat');
				$mail->addAddress($email, $user['username']);
				$mail->isHTML(true);
				$mail->Subject = "OTP for Password Reset - Promat";
				$mail->Body = "
					<p>Hello " . ucwords($user['username']) . ",</p>
					<p>Your OTP is: <strong>$otp</strong></p>
					<p>This OTP will expire in 5 minutes.</p>
					<p>Regards,<br>Promat Team</p>
				";
				$mail->send();

				return $this->response->setJSON([
					'success' => true,
					'message' => 'OTP sent to your email.'
				]);
			} catch (\Exception $e) {
				return $this->response->setJSON([
					'success' => false,
					'message' => 'Mail error: ' . $mail->ErrorInfo
				]);
			}			
		}
		//if ($otpInput && !$newPassword && !$confirmPassword) {
		if ($otpInput && !$newPassword && !$confirmPassword) {
			if (strpos($user['password'], '_') === false) {
				return $this->response->setJSON([
					'success' => false,
					'message' => 'Invalid OTP format or expired.'
				]);
			}

			list($storedOtp, $expiry) = explode('_', $user['password']);

			if ($otpInput != $storedOtp) {
				return $this->response->setJSON([
					'success' => false,
					'message' => 'Invalid OTP.'
				]);
			}

			if (time() > (int)$expiry) {
				return $this->response->setJSON([
					'success' => false,
					'message' => 'OTP has expired.'
				]);
			}

			return $this->response->setJSON([
				'success' => true,
				'message' => 'OTP verified. You may now reset your password.'
			]);
		}
		if ($otpInput && $newPassword && $confirmPassword) {
			if (strpos($user['password'], '_') === false) {
				return $this->response->setJSON([
					'success' => false,
					'message' => 'OTP verification not found or has expired. Please request a new OTP.'
				]);
			}

			list($storedOtp, $expiry) = explode('_', $user['password']);

			if ($otpInput != $storedOtp || time() > (int)$expiry) {
				return $this->response->setJSON([
					'success' => false,
					'message' => 'Invalid or expired OTP.'
				]);
			}

			if ($newPassword !== $confirmPassword) {
				return $this->response->setJSON([
					'success' => false,
					'message' => 'Passwords do not match.'
				]);
			}

			$hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
			$this->loginModel->update($user['user_id'], ['password' => $hashedPassword]);

			return $this->response->setJSON([
				'success' => true,
				'message' => 'Password reset successfully.'
			]);
		}
		return $this->response->setJSON([
			'success' => false,
			'message' => 'Invalid request.'
		]);*/
	}


	public function removeUser()
{
    $userId = $this->request->getPost('userId');

    if ($userId) {
        $this->AuthModel->delUser($userId);
        return $this->response->setJSON(['success' => true, 'message' => 'User removed']);
    } else {
        return $this->response->setJSON(['success' => false, 'message' => 'User ID missing']);
    }
}

}