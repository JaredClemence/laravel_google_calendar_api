= Google Calendar Api for Laravel =

== Installing ==

Check back later for installation instructions. This package has not yet been 
uploaded to packagist.

== Use ==

This package uses the namespace JRC\Google\Calendar. All classes will be in this namespace.

Prior to using this package,

1. Register your application with Google
2. Create an OAuth 2.0 credential for your application
3. Download your application credentials as a json file and put a relative path 
    reference to your json file in your .env file (This service will tell you 
    the appropriate name of the env variable name in an exception, so just put 
    it anywhere for now, and then change the variable name after you receive 
    the error.)

=== Obtain an Auth Code ===

After including the composer autoloader, you will be able reference the CalendarAuthController.
Start by instantiating an instance of this class. Call the method `getAuthUrl()` to generate 
a redirect URL for your program. You will receive errors indicating which fields need to be set in 
your .env file.

Send the user to that URL by passing back a `redirect( $url )` value from your controller method.

For example:

Let us assume that we have set up the following route:

    Route::get( '/google/auth', 'MyController@redirectUser' );

Then in our controller, we will have the following method:

    class MyController extends Controller{
        ...
        public function redirectUser(){
            $authController = new JRC\Google\Calendar\CalendarAuthController();
            $uri = $authController->getAuthUrl();
            return redirect( $uri );
        }
        ...
    }

This will pass your user to the Google Auth view, where your user will grant your application 
permissions.

Google will call your program back at the URI you specify in your application's 
registration. (Don't worry if you picked a bad URI, you can always update this 
in your Google account and download a new JSON file.)

** Further edits are being made to this file **


