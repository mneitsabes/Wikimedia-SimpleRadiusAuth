These is the readme file for the Simple Radius Authentification Provider extension.

## About

SimpleRadiusAuth is an extension that queries a RADIUS server to authenticate users.

Visitors cannot create an account and users cannot change their password.

It's based on Wikimedia 1.35 changes like AuthManager, *PrimaryAuthenticationProvider, UserNameUtils so it doesn't have backward compatibility with older versions.

### Requirements

- You must have a RADIUS service running somewhere accessible from the Wiki server.
- You must use Wikimedia 1.35 or later (test with 1.38.2)
- You must have the [PHP RADIUS](http://php.net/manual/en/book.radius.php) extension

### Usage

1. Put the SimpleRadiusAuth in the extensions directory
2. Edit your LocalSettings.php file and add :
```
  // Load SimpleRadiusAuth
  wfLoadExtension( 'SimpleRadiusAuth' );
  $wgSimpleRadiusAuthServer = "IP_OR_DNSNAME_OF_RADIUS_SERVER";
  $wgSimpleRadiusAuthSecret = "SHARED_SECRET";

  // Disable account creation
  $wgGroupPermissions['*']['createaccount'] = false;
```
3. That's all !

### Config

$wgSimpleRadiusAuthServer : the hostname parameter specifies the server host, either as a fully qualified domain name or as a dotted-quad IP address in text form.

$wgSimpleRadiusAuthPort : the port specifies the UDP port to contact on the server. If port is given as 0, the library looks up the radius/udp or radacct/udp service in the network services database, and uses the port found there. If no entry is found, the library uses the standard Radius ports, 1812 for authentication.

$wgSimpleRadiusAuthSecret : the shared secret for the server host is passed to the secret parameter. The Radius protocol ignores all but the leading 128 bytes of the shared secret.

$wgSimpleRadiusAuthTimeout : the timeout for receiving replies from the server is passed to the timeout parameter, in units of seconds.

$wgSimpleRadiusAuthMaxTries : the maximum number of repeated requests to make before giving up is passed into the max_tries.
