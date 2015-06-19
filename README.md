Debug Bundle
===================


This is the Debug Bundle provided with **BackBee Standard Edition**.
This Bundle provide some useful **features** to help you find the root of an issue.

----------

Commands
-----------------

**container:debug**

A powerful command to find services, parameters and tags inside the ContainerBuilder of BackBee CMS.
This is mostly an adaptation of the Symfony Framework ``container:debug`` command.

* Search a service or a parameter
![service-parameter-container](https://cloud.githubusercontent.com/assets/1247388/8253963/c2e50f8e-1694-11e5-8cda-f07e381a43c6.png)
* List parameters
![params](https://cloud.githubusercontent.com/assets/1247388/8253968/cad8f8ea-1694-11e5-96cf-8595da3ae60f.png)
* List services
![services](https://cloud.githubusercontent.com/assets/1247388/8253970/d18ddba6-1694-11e5-8d62-00331c1d1906.png)

**container:routing**

Make your own bundles allow you to create complete and totaly "CMS-uncoupled" applications.
Sometimes, you need to check if the routes you have defined are setted correctly: this is exactly
the purpose of this command.

* Debug a route (and have complete informations)
![dump-route](https://cloud.githubusercontent.com/assets/1247388/8253756/fe3450b0-1692-11e5-864b-bd46893cd302.png)
* Overview of all routes
![dump-routes](https://cloud.githubusercontent.com/assets/1247388/8253762/09564cbe-1693-11e5-84e1-2f062790ace0.png)
* Overview of all routes with Controller (can break the view of some "low screen" resolutions)
![dump-routes-controllers](https://cloud.githubusercontent.com/assets/1247388/8253775/1fa5314c-1693-11e5-8bf9-050cb37c8cee.png)


### Documentation links

  - [BackBee Standard Edition](http://www.backbee.com/) is a full-featured, open-source Content Management System (CMS) build on top of Symfony Components and Doctrine.
