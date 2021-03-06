# Message Broker for Async PHP Applications 
 
Pure and simple PHP based message broker. Allows to simplify async code and make it easier to test.

[![Build Status](https://travis-ci.com/EcomDev/message-broker.svg?branch=master)](https://travis-ci.com/EcomDev/message-broker) [![Maintainability](https://api.codeclimate.com/v1/badges/2109d457b15cdd34b64a/maintainability)](https://codeclimate.com/github/EcomDev/message-broker/maintainability) [![Test Coverage](https://api.codeclimate.com/v1/badges/2109d457b15cdd34b64a/test_coverage)](https://codeclimate.com/github/EcomDev/message-broker/test_coverage) 

## Why do you need it?
When you develop async applications - blocking operations can quickly become the main bottleneck. 
This library allows to separate async thread from blocking operation by introduction of message based
communication between blocking and non blocking code. 

## Concept 
Every single message must be as light weight as possible. 

## Installation
```bash
composer require ecomdev/message-broker
```

## License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details
