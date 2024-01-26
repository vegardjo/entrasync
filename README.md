## INTRODUCTION

Module that allows you to connect to the Microsoft Graph API for your tenant, and import users from Microsoft Entra ID (prev. Azure AD) to Drupal user entities. More information at https://www.drupal.org/project/entrasync 

## FEATURES
The module will connect to your tenant and fetch the users that are not already in Drupal, and queue these up for importing using the Queue API.

You are able to map the user fields from Entra to your own Drupal fields on the user, you can decide which roles the incoming users should get, wether the user should be active or not, and wether you want to send welcome e-mail to the users, if they are set to active.

## REQUIREMENTS

The module is dependent on the Microsoft Graph API module, which is what you will use to authenticate to your tenant, so the first thing to do is set that up at Administration » Configuration » Web Services » Microsoft Graph API.

It is also dependent on having an Azure app configured with the right permissions. If you want to test it a good way to start is to sign up for the Microsoft 365 Developer Program. This is free, and will give you a free tenant with demo users to test with.

It's recommended to install Queue UI module, as this will give you a UI to what this modules queue, and also gives the extra possibility to process queues via batch on demand, and not only via cron.

## INSTALLATION

Install as you would normally install a contributed Drupal module.
See: https://www.drupal.org/node/895232 for further information.

## CONFIGURATION
- (Have a configured app for Endtra ID)
- Set up your connection to Microsoft Graph API for your tenant at Administration » Configuration » Web Services » Microsoft Graph API.
- Configure the processing you want to do on the incoming users at Administration » Configuration » Web Services » Microsoft Entra Synchronization Settings.
- Click the sync button, or run up cron. 

## MAINTAINERS

Current maintainers for Drupal 10:

- Vegard A. Johansen (vegardjo) - https://www.drupal.org/u/vegardjo
