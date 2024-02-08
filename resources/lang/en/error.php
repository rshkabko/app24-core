<?php

return [
    // Portal
    'portal_cant_install' => 'Cant install portal!',
    'portal_env__app_name_bugs' => 'Bugs with ENV APP_NAME',
    'portal_cant_create_update' => 'Cant Create Or Update Portal!',
    'portal_empty_domain_in_get' => 'Empty DOMAIN in GET parameters or SESSION expired. Reload page!',
    'portal_empty_domain' => 'Empty DOMAIN!',
    'portal_empty_ID' => 'Cant find ID of your APP! Reinstall please. Domain: ',
    'security_cant_find_portal_code' => 'Module with DOMAIN :domain and CODE :code does not exist!',
    'security_cant_find_portal_id' => 'Module with DOMAIN :domain and ID :id does not exist!',
    'security_finded_two_portal' => '[SERVER::ERROR] Installed two identical applications on one portal! Please uninstall one of the apps! Read <a href="https://en.flamix.solutions/faq/en-two-apps/" target="_blank">how to fix this problem.</a><br />⚠️ If you click the button below, you will <b>REMOVE ALL APPS</b>, after which you will need to reinstall this app again.',

    // User
    'b24user_open_portal_in_b24' => '[USER] Open the APP on the Bitrix24 Portal or reload page!',
    'b24user_cant_take_session' => '[USER] Transfer of sessions on the portal does not work! Frequent mistakes: you are using the Safari browser (Chrome is recommended) or the browser is running in anonymous mode!',

    // With description
    'invalid_token' => 'Invalid token! We sent app accesses for verification and Bitrix24 said that they are not correct. <b>👍 Solutions:</b> Reinstall app! Original message: ',
    'expired_token' => 'Access has expired! Bitrix24 says your access is expired. Access are automatically renewed, so you have one of these problems:<ul><li>⚠️ The user who installed the application is fired, deleted, or lost admin access;</li><li>⚠️ Your Market+ subscription ended more than 20 days ago.</li></ul><b>👍 Solutions:</b> Reinstall app! Original message: ',
    'connection_error' => 'Bitrix24 crashed! It looks like the Bitrix24 authorization servers are temporarily down. <b>👍 Solutions:</b> Wait, this is temporary! Original message: ',
    'maintenance' => 'Maintenance! We are currently making the app better. Try again in 5 minutes!',
    'not_found' => '404 Not Found! This page does not exist. If you got here via a link in the app, please let us know where the link is, maybe we forgot to remove it or made a mistake.',
    'server_error' => '500 Server Error! We have a bug on the server and we will fix it soon. When you see this page, we are automatically notified and we quickly fix it. Come back in a few hours and if it doesn\'t disappear, please let us know.',
];
