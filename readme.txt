=== bbPress - Private Replies - Enhanced ===
Author URI: https://david.dw-perspective.org.uk
Plugin URI: https://github.com/DavidAnderson684/bbpress-private-replies-enhanced
Contributors: mordauk, corsonr, DavidAnderson
Donate link: https://david.dw-perspective.org.uk/donate
Tags: bbPress, private replies, replies, Forums
Requires at least: 4.9
Tested up to: 5.2
Stable Tag: 1.4.0

A simple plugin to allow your bbPress users to mark their replies as private (visible only to the user and moderators), or moderator-only.

== Description ==

This add-on plugin for bbPress will allow your forum posters to mark their replies as private, meaning that only the original poster and forum moderators can see the content of the reply; or as moderator-only. This is a great plugin to install if you use bbPress as a support forum where users may need to share confidential information, such as site URLs, passwords, etc., or where moderators want to share information that is not visible to other users.

If you have suggestions or bugfixes for the plugin, please report them on [Github](https://github.com/pippinsplugins/bbPress-Private-replies).

**Languages**

Private Replies for bbPress as been translated into the following languages:

1. English
2. French
3. German
4. Dutch

Would you like to help translate the plugin into more languages? [Contact Pippin](http://pippinsplugins.com/contact).

== Installation ==

1. Activate the plugin
2. A new "Set reply as private" checkbox will be added to the new reply creation form
3. Replies' private status can be changed by editing the reply

== Screenshots ==

1. Screenshot 1
2. Screenshot 2
3. Screenshot 3
4. Screenshot 4


== Changelog ==

= 1.4.1 - 03/May/2019 =

* Make text domain agree with plugin slug
* Change plugin slug to bbpress-private-replies-enhanced
* Mark as requiring WP 4.9+. Likely works very much earlier, but nothing earlier will receive support.

= 1.4.0 =

* Add a new "moderator-only" replies feature. These differ from "private replies" in that they are also hidden from the topic poster (unless they are a moderator)

= 1.3.3 =

* Fix: Private replies from other users could be emailed to topic authors improperly
* Fix: noreply email address could trigger fatal PHPMailer errors in some scenarios
* Tweak: Added a filter to allow the capability required to view private replies to be changed
* Tweak: Added a filter that allows the noreply address to be overwritten

= 1.3.2 =

* Added a Persian translation file

= 1.3.1 =

* Added Dutch translation files
* Fixed a non-object error message

= 1.3 =

* Tweaked a priority to ensure Private Replies always runs last

= 1.2 =

* Fix: Missing argument message
* Fix: Email notifications no new Private Replies weren't sent since latest bbPress update

= 1.1.2 =

* Fix: bug with hiding private replies from annonymouse users

= 1.1.1 =

* New: added German translation files, thanks to @AlexIhrig

= 1.1 =

* Fixed a bug that allowed topic authors to view private replies of other users within the same thread

= 1.0.13 =

* Fixed an incorrect variable name

= 1.0.12 =

* Fixed a bug (again) that made private replies open to logged-out users

= 1.0.11 =

* Fixed a bug that made private replies open to logged-out users

= 1.0.10 =

* Fixed a bug that caused authorized users to not be able to see private replies

= 1.0.9 =

* Fixed a bug with topic authors would receive email notifications on private replies by other authors on the same thread

= 1.0.8 =

* Fixed a bug with topic authors could see private replies of other authors in the same thread

= 1.0.7 =

* Fixed a compatibility problem with GD bbPress Attachments that resulted in attached files not being private.

= 1.0.6 =

* Fixed missing argument error.

= 1.0.5 =

* Fixed a bug with private replies showing up in search results

= 1.0.4 =

* Fixed another bug with new reply notifications including private details.

= 1.0.3 =

* Fixed a bug with new reply notifications including private details.

= 1.0.2 =

* Darkened background of private replies

= 1.0.1 =

* Fixed a bug with private replies in user's profiles.

= 1.0 =

* Fixed a bug that caused topic subscribers to get emails with new private replies that they didn't have permission to read

= 0.2 =

* Fixed a bug that prevented topic authors from seeing private admin replies
* Changes the capability required to see private replies from bbp_forums_admin to publish_forums
* Changed private reply backgrounds to blue to help distinguish them from sticky topics
* Improved French translation

= 0.1 =

* First beta release!
