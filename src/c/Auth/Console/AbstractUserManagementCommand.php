<?php
namespace c\Auth\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

abstract class AbstractUserManagementCommand extends Command
{
	public function getUser()
	{
		$model = $this->laravel['config']->get('auth.model');
		$model = $this->laravel->make($model);
		$query = $model->newQuery();

		foreach ($this->option() as $key => $value) {
			if (!empty($value)) $query->where($key, '=', $value);
		}

		if (empty($query->wheres)) {
			$this->error('Must provide at least one option! (username, email or id)');
			exit 1;
		}

		$count = $query->count();
		if ($count < 1) {
			$this->error('No users with those credentials found.');
			exit 1;
		} elseif ($count > 1) {
			$this->error('More than 1 user with those credentials found. Please be more specific.');
			exit 1;
		}

		return $query->first();
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('username', null, InputOption::OPTIONAL, 'Username to look for.', null),
			array('email', null, InputOption::OPTIONAL, 'E-mail address to look for.', null),
			array('id', null, InputOption::OPTIONAL, 'Database ID to look for.', null),
		);
	}
}