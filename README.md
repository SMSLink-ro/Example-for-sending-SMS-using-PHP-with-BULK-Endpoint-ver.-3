# Example for sending SMS using SMSLink - SMS Gateway (BULK) Endpoint Version 3 API using PHP

This is an example for sending SMS using [SMSLink.ro](https://www.smslink.ro) API, called [SMS Gateway](https://www.smslink.ro/sms-gateway.html) (BULK). 
SMSLink.ro allows you to send SMS to all mobile networks in Romania and also to more than 168 countries and more than 1000 mobile operators worldwide. 

## Requirements & Usage

1. Create an account on [SMSLink.ro](https://www.smslink.ro/inregistrare/)
2. Create a SMS Gateway connection at [SMSLink.ro / SMS Gateway / Configuration & Settings](https://www.smslink.ro/sms/gateway/setup.php). Each SMS Gateway connection is a pair of Connection ID and Password. 
3. Find and replace within the example the values for Connection ID and Password parameters with the values obtained at the previous step.
4. Within the examples find and replace the value for *to* and *message* parameters with the destination phone number for the SMS and with the message to be sent to the destination. Phone numbers should be formatted as a Romanian national mobile phone number (*07xyzzzzzz*).

## About SMS Gateway (BULK) API

Please note that SMS Gateway (BULK) is designed to send large volumes of SMS in a single request and thus can send only SMS up to 160 characters. 
For sending SMS longer than 160 characters, please use SMS Gateway (HTTP), SMS Gateway (SOAP) or SMS Gateway (JSON).

Examples for SMS Gateway (HTTP), SMS Gateway (SOAP) or SMS Gateway (JSON) can be found here:

1. SMS Gateway (HTTP): https://github.com/SMSLink-ro/Example-for-sending-SMS-using-PHP-cURL/blob/master/main.php
2. SMS Gateway (SOAP): https://github.com/SMSLink-ro/Example-for-sending-SMS-using-PHP-SoapClient/blob/master/main.php
3. SMS Gateway (JSON): https://github.com/SMSLink-ro/Example-for-sending-SMS-using-PHP-cURL-with-JSON/blob/master/main.php

## Documentation

The [complete documentation](https://www.smslink.ro/sms-gateway-documentatie-sms-gateway.html) of the SMSLink - SMS Gateway API can be found [here](https://www.smslink.ro/sms-gateway-documentatie-sms-gateway.html), describing all available APIs (HTTP GET / POST, SOAP / WSDL, JSON and more).

## Additional modules and integrations

SMSLink also provides modules for major eCommerce platforms (on-premise & on-demand), integrations using Microsoft Power Automate, Zapier or Integromat and many other useful features. Read more about all available features [here](https://www.smslink.ro/sms-gateway.html). 

## Support

For technical support inquiries contact us at contact@smslink.ro or by using any other available method described [here](https://www.smslink.ro/contact.php).
