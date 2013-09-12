Laravel Mobile Facebook App
===========================

A Laravel Filter for Facebook Page Tab Apps that need to work on mobile too.

## Concept

For all routes that you apply the filter too, it checks to see if the user is not on mobile and is not the facebook bot
and hasn't already been redirected to Facebook (prevents endless redirection once inside the iFrame), then redirects the
user to the tab app on your Facebook Page, on the page in your app that the user was trying to access originally.

However, if the user is on mobile (or it's the Facebook bot that gets open graph data), don't do the redirect and just
show them the page they are after.

All links to your app that get shared on social networks and other websites or apps should be to your domain, not the
Facebook Tab App URL.

I.e.

http://myfacebookapp.com/path/to/page?query=string&shared=true

not

http://www.facebook.com/pages/mypage?sk_app=123456789&app_data=...

Note the shared=true query string param in the above URL - this is required in all links to your app that are shared on
social networks or included on other websites. It's needed in case this is the second or third etc time that the user
visited your app not on a mobile device and they already have a valid session. It's required because we set a flag in
the session to remember that the user has been redirected to facebook, so when the app is loaded again inside the iFrame
the user is not redirected again, resulting in an infinite loop. However, if this is not the first time a user follows a
link to your app, and they already have a session, this shared=true query string parameter removes this flag from the
session before checking whether they have already been redirected, so that they get redirected again. Get that? No? Me
neither.

## Installation

Add the library via composer

    composer require fbf/laravel-mobile-facebook-app dev-master

Add the following to app/config/app.php

    'Fbf\LaravelMobileFacebookApp\LaravelMobileFacebookAppServiceProvider'

Publish the config from thomaswelton/laravel-facebook to app/config/packages (this library is used to decode the signed
request and to get the tab app URL for your app)

    php artisan config:publish thomaswelton/laravel-facebook

Add your app id and secret to the published config file

## Usage

Register and apply the filter, add the following to app/routes.php

    Route::filter('facebook', 'LaravelMobileFacebookAppFilter');
    Route::when('my/routes/*', 'facebook');
