# MageRun Addons

A few additional commands for N98-MageRun Magneto Command-line tool.

The purpose of this project is to add custom commands I need to use for testing store functionality.

## Installation

1. Create ~/.n98-magerun/modules/ if it doesn't already exist.

`mkdir -p ~/.n98-magerun/modules/`

2. Clone the magerun-addons repository in there

`cd ~/.n98-magerun/modules/ && git clone https://github.com/djfordz/magerun-addons.git df-addons`

3. It should be installed. To see that it is installed check to see if one of the new commands is in there.

`n98-magerun.phar customer:sendorderemail`

## Commands

### Customer Send Transactional Email.

A lot of times I have customers who complain their store is not sending order emails, or transactional emails, even with cron set correctly.  To test, it becomes a tedious process to log into admin change the copy to address to your own, and resend an order email. Or create a new order, which again is tedious if you don't want to buy anything. I have created this command to test the transactional email functionality.

`n98-magerun.phar customer:sendorderemail david@magemojo.com`

There are no options just add your email to the end of the command.

This will simply pick a random completed order, change the email address to your selected email, and send the email through the tranactional email system. If using Magento version > 1.9.0, then it will add to cron queue, if another version it will send directly.

This plugin is useful for testing the transactional email functionality.
