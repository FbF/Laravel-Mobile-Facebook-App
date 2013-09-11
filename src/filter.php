<?php

/**
 * Class LaravelMobileFacebookAppFilter
 *
 * A Laravel Filter for Facebook Apps that need to work on mobile too.
 */
class LaravelMobileFacebookAppFilter {

	/**
	 * The signed request from Facebook. Populated in the self::filter() method, using thomaswelton/laravel-facebook lib
	 *
	 * @var array
	 */
	protected $signedRequest;

	/**
	 * The main method called by Laravel before routes are executed
	 *
	 * If
	 *
	 * @return mixed
	 */
	public function filter()
	{
		// If the URL was shared, i.e. has the shared=true querystring param, e.g. (mydomain.com/path/to/page?shared=true)
		// then delete the session var done_facebook_redirect if it's set (it may have been set before if the user has clicked
		// on a link to the app before) so that we can check whether we should redirect to facebook properly.
		if ($this->wasUrlShared())
		{
			Session::forget('done_facebook_redirect');
		}

		$this->signedRequest = Facebook::getSignedRequest();

		// Redirect to facebook, with the original uri encoded in app_data, if not on mobile, not the facebook bot and
		// we haven't already redirected to facebook.
		if ($this->shouldRedirectToFacebook())
		{
			return $this->doFacebookRedirect();
		}

		// If there is a valid uri in app_data and we haven't already done the redirect for this uri, we are in the
		// iFrame, so do the app_data uri redirect to show the page in the app that the user originally wanted
		$appDataUri = $this->newAppDataUri();
		if ($appDataUri)
		{
			return $this->doAppDataUriRedirect($appDataUri);
		}

		$this->sendP3PHeaders();
	}

	/**
	 * Checks whether the URL was shared by the presence of the querystring param shared=true
	 *
	 * @return bool
	 */
	protected function wasUrlShared()
	{
		return array_key_exists('shared', $_GET);
	}

	/**
	 * Determines whether to redirect to facebook or not
	 *
	 * @return bool
	 */
	protected function shouldRedirectToFacebook()
	{
		// If the request is from a mobile, don't redirect to facebook
		if ($this->isRequestFromMobile())
		{
			return false;
		}

		// If the request is from the Facebook bot user agent (which gets the og: tags), don't redirect to facebook
		if ($this->isRequestFromFacebookBot())
		{
			return false;
		}

		// If we have done the redirect to facebook, don't do it again
		if (Session::get('done_facebook_redirect'))
		{
			return false;
		}

		return true;
	}

	/**
	 * Determines whether request is from mobile or not
	 *
	 * @return bool
	 */
	protected function isRequestFromMobile()
	{
		$detect = new Mobile_Detect();
		return $detect->isMobile();
	}

	/**
	 * Determines whether request if from facebook bot or not
	 *
	 * @return bool
	 */
	protected function isRequestFromFacebookBot()
	{
		return stripos($_SERVER['HTTP_USER_AGENT'], 'facebookexternalhit') !== false;
	}

	/**
	 * Returns a Redirect object which in turn is returned by self::filter() which redirects the user to facebook
	 *
	 * @return mixed
	 */
	protected function doFacebookRedirect()
	{
		// Remove the shared=true query string param from the URL
		$url = parse_url($_SERVER['REQUEST_URI']);
		if (array_key_exists('query', $url))
		{
			parse_str($url['query'], $queryArr);
			if (array_key_exists('shared', $queryArr))
			{
				unset($queryArr['shared']);
			}
		}
		$url = $url['path'];
		if (!empty($queryArr))
		{
			$url .= '?' . http_build_query ($queryArr);
		}

		$tabAppUrl = Facebook::getTabAppUrl();

		$tabAppUrl .= '&app_data=uri,'.urlencode($url);

		// Sets a variable in the session to say we've done the redirect, so the next time the page is loaded, which
		// will be when it is loaded inside the iFrame on Facebook, the test to see whether the user should be
		// redirected fails and the page is loaded.
		Session::put('done_facebook_redirect', true);

		return Redirect::to($tabAppUrl);
	}

	/**
	 * Returns the uri of the deep linked page in your app which is encoded in the app_data paramter, if present, and we
	 * haven't already done the redirect for this occasion, else returns false
	 *
	 * @return mixed
	 */
	protected function newAppDataUri()
	{
		if (!$this->signedRequest)
		{
			return false;
		}

		if (Session::get('done_app_data_redirect') == $this->signedRequest['issued_at'])
		{
			return false;
		}

		if (!isset($this->signedRequest['app_data'])) {
			return false;
		}

		if (!preg_match('/^uri,(.*)/', $this->signedRequest['app_data'], $matches))
		{
			return false;
		}

		return $matches[1];
	}

	/**
	 * Returns a Redirect object which in turn is returned by self::filter() which redirects the user to the page within
	 * your app that they were originally trying to access.
	 *
	 * @param $appDataUri string
	 * @return mixed
	 */
	public function doAppDataUriRedirect($appDataUri)
	{
		// Sets the session var 'done_app_data_redirect' to the 'issued_at' value in the signed request. This value is
		// a timestamp that is reset each time you land on a facebook page, i.e. so it is regenerated when the user
		// refreshes the browser for example. This value is used rather than just a boolean value so if a user goes away
		// and comes back from another deep link URL, but the session is still valid, the redirect will still happen. If
		// it was just a boolean value and the session was still valid, the call to self::newAppDataUri() would return
		// false the redirect to the deep linked page wouldn't happen inside the facebook iFrame, so the homepage of
		// your app would be displayed.
		Session::put('done_app_data_redirect', $this->signedRequest['issued_at']);

		return Redirect::to($appDataUri);
	}

	/**
	 * Required for IE to allow 3rd party cookies to be set
	 */
	protected function sendP3PHeaders()
	{
		header('X-Frame-Options: GOFORIT');
		header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');
	}

}