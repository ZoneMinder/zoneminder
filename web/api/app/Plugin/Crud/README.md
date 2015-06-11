[![Stories in Ready](https://badge.waffle.io/friendsofcake/crud.png?label=ready)](https://waffle.io/friendsofcake/crud)
[![Build Status](https://travis-ci.org/FriendsOfCake/crud.png?branch=master)](https://travis-ci.org/FriendsOfCake/crud)
[![Coverage Status](https://coveralls.io/repos/FriendsOfCake/crud/badge.png?branch=master)](https://coveralls.io/r/FriendsOfCake/crud?branch=master)
[![Total Downloads](https://poser.pugx.org/FriendsOfCake/crud/d/total.png)](https://packagist.org/packages/FriendsOfCake/crud)
[![Latest Stable Version](https://poser.pugx.org/FriendsOfCake/crud/v/stable.png)](https://packagist.org/packages/FriendsOfCake/crud)

# Version notice

The master and develop branches only works for CakePHP 2.x - please use the [cake3 branch](https://github.com/FriendsOfCake/crud/tree/cake3) for CakePHP 3.x

# Introduction

Crud was built to be [scaffolding](http://book.cakephp.org/2.0/en/controllers/scaffolding.html) on
steroids, and allow developers to have enough flexibility to use it for both rapid prototyping and
production applications, even on the same code base -- saving you time.

* Crud is [very fast to install](http://friendsofcake.com/crud/docs/installation.html), a few minutes tops.

* Crud is very flexible and has tons of [configuration options](http://friendsofcake.com/crud/docs/configuration.html).

* Crud aims to stay out of your way, and if it happens to get in your way, you can change the undesired
behavior very easily.

* Crud relies heavily on CakePHP events and is possible to override, extend, or disable almost all
of Crud's functionality either globally or for one specific action.

* Usually, the basic code for controller CRUD actions are very simple and always looks the same. Crud
will add the actions to your controller so you don't have to reimplement them over and over again.

* Crud does not have the same limitations as CakePHP's own scaffolding, which is "my way or the
highway." Crud allows you to hook into all stages of a request, only building the controller code
needed specifically for your business logic, outsourcing all the heavy boilerplating to Crud.

* Less boilerplate code means less code to maintain, and less code to spend time unit testing.

* Crud allows you to use your own views, baked or hand-crafted, in addition to adding the
code needed to fulfill your application logic, using [events](http://friendsofcake.com/crud/docs/events.html). It is
by default compatible with CakePHP's baked views.

* Crud also provides built in features for JSON and XML [API](http://friendsofcake.com/crud/docs/listeners/api.html)
for any action you have enabled through Crud, which eliminates maintaining both a
HTML frontend and a JSON and/or XML interface for your applications -- saving you tons of time and
having a leaner code base.

# Bugs

If you happen to stumble upon a bug, please feel free to create a pull request with a fix
(optionally with a test), and a description of the bug and how it was resolved.

You can also create an issue with a description to raise awareness of the bug.

# Features

If you have a good idea for a Crud feature, please join us on IRC and let's discuss it. Pull
requests are always more than welcome.

# Support / Questions

You can join us on IRC in the #FriendsOfCake channel for any support or questions.
