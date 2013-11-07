<?php
namespace c\View;

use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
	public function register()
	{
		$this->app['menubuilder'] = $this->app->share(function($app) {
			return new MenuBuilder;
		});
	}

	public function boot()
	{
		$this->app['view']->creator('c::sidebar', function($view) {
			$view->with('sidebar', array());
		});

		$this->app['view']->composer('partial.menu', function($view) {
			if ($this->app['auth']->check()) {
				$username = $this->app['auth']->user()->name;
				$item = $this->app['menubuilder']->makeDropdown('user', $username, 'user');

				$subItem = $this->app['menubuilder']->item('profile');
				$subItem->title = $this->app['translator']->get('c::user.profile-title');
				$subItem->url = $this->app['url']->action('UserController@profile');
				$item->subMenu->addItem($subItem);

				$subItem = $this->app['menubuilder']->item('logout');
				$subItem->title = $this->app['translator']->get('c::auth.logout');
				$subItem->url = $this->app['url']->action('AuthController@logout');
				$item->subMenu->addItem($subItem);

				$this->app['menubuilder']->add('right', $item);
			}
		});
	}

	public function provides()
	{
		return ['menubuilder'];
	}
}