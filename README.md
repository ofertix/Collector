What is "Collector"?
====================

Collector is the component in charge of subscribing to the channel where stats and events are published. Message storing policies are determined by matching type or a regexp defined in the config file.

You can configure:

  - Your stats types, adding operations: counter, median, percentile 95, min, max, etc.

  - Retention:

    - History time (N days)

    - Sample (N seconds or N minutes or N hours)


Also "Collector" offers a JSON API to query stored stats (used by WebUIStats component).


Requirements
============

- PHP 5.3.2 and up.
- RabbitMQ or ZMQ.
- MongoDB


Libraries and services used
===========================

- PHP
	- Pimple
	- Silex
	- Symfony Components:
		- ClassLoader
		- YAML
		- Console
	- PhpAmqpLib
	- Monolog
- MongoDB
- RabbitMQ/ZMQ+OpenPGM


Installation
============

The best way to install is to clone the repository and then configure as you need. See "Configuration" section.

After cloning you must update vendors:

	./update_vendors.sh


Usage
=====

Start collector server:

	php app/collector.php -c app/config/stats_test.yml


Configuration
=============

All configuration is done using a YAML file.

Config file is structured in 4 sections:

- storage:
	- class name in charge of process and store messages.

- output:
	- storage name configured to get data to the JSON API.

- channel:
	- class name that subscribe to the channel to get messages.

- stats:
	- stat types definition. Assign some operations and retention time.
	- config can be defined by type or by a regexp

See config file for more details and examples.


Extra notes
===========

Use of ZMQ is discontinued because a memory leak using ZMQ with OpenPGM PUB/SUB.
