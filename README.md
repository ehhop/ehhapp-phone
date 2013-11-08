# ehhapp-phone

This is a private webservice designed to be used by the EHHOP voicemail system.

It pulls information from a Google Spreadsheet and extracts contact information for people on call, and if somebody leaves a message, an email is sent to the people on call with a URL for the message.

## Installation

These services require some configuration of secrets before they will work.

Copy config.dist.php --> config.php and change the values according to the instructions.

Then copy all files to a webserver running PHP, and off you go!  Two 

`/services.php` will show the HTML homepage for the SOAP service with the WSDL at `/services?WSDL`.