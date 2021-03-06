<?php
/**
 * Laravel 4 Core - PasswordBroker replacement
 *
 * @author    Andreas Lutro <anlutro@gmail.com>
 * @license   http://opensource.org/licenses/MIT
 * @package   Laravel 4 Core
 */

namespace c\Auth;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Reminders\ReminderRepositoryInterface;
use Illuminate\Auth\Reminders\RemindableInterface;
use Illuminate\Auth\UserProviderInterface;

/**
 * Class responsible for handling password resets. Improved version of
 * Illuminate\Auth\Reminders\PasswordBroker - doesn't return redirect responses
 * with session data hard-coded in, but rather does things behind the scenes,
 * returns boolean flags or throws exceptions and lets the developer decide what
 * should be done with the results.
 */
class PasswordBroker
{
	protected $users;
	protected $reminders;

	public function __construct(
		UserProviderInterface $users,
		ReminderRepositoryInterface $reminders
	) {
		$this->users = $users;
		$this->reminders = $reminders;
	}

	/**
	 * Get a user by his/her credentials.
	 *
	 * @param  array  $credentials
	 *
	 * @return mixed
	 */
	public function findUser(array $credentials)
	{
		return $this->users->retrieveByCredentials($credentials);
	}

	/**
	 * Register a request for a password reset. Create a reset token for a user
	 * and send him/her an email with instructions on how to reset password.
	 *
	 * @param  RemindableInterface $user
	 *
	 * @return bool
	 */
	public function requestReset(RemindableInterface $user)
	{
		$token = $this->reminders->create($user);
		return $this->mail($user, $token);
	}

	/**
	 * Send an email to a user with instructions on how to reset his/her password.
	 *
	 * @param  RemindableInterface $user
	 * @param  string $token
	 *
	 * @return bool
	 */
	public function mail(RemindableInterface $user, $token)
	{
		// @todo inject this? maybe?
		$view = Config::get('auth.reminder.email');

		$email = $user->getReminderEmail();

		// @todo inject this? maybe?
		$method = Config::get('auth.reminder.queue') ? 'queue' : 'mail';

		$viewData = ['token' => $token];

		return Mail::$method($view, $viewData, function($msg) use ($email) {
			$msg->to($email)
				->subject(Lang::get('auth.reminder.subject'));
		});
	}

	/**
	 * Find a user and reset his/her password.
	 *
	 * @param  array  $credentials
	 * @param  string $token
	 * @param  string $newPassword 
	 *
	 * @return User
	 */
	public function reset(array $credentials, $token, $newPassword)
	{
		if (!$user = $this->findUser($credentials)) {
			return false;
		}

		if ($this->resetUser($user, $token, $newPassword)) {
			return $user;
		} else {
			return false;
		}
	}

	/**
	 * Reset a user's password.
	 *
	 * @param  RemindableInterface $user
	 * @param  string              $token
	 * @param  string              $newPassword
	 *
	 * @return boolean
	 */
	public function resetUser(RemindableInterface $user, $token, $newPassword)
	{
		if (!$this->reminders->exists($user, $token)) {
			return false;
		}

		$user->setPasswordAttribute($newPassword);
		$user->save();
		$this->reminders->delete($token);

		return true;
	}
}
