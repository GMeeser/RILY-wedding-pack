<?php

require_once 'RSVP.php';
( new RSVP )->init();
require_once 'RSVPShortCode.php';
( new RSVPSHortCode )->init();
require_once 'WooCommerceEdits.php';
( new WooCommerceEdits )->init();
require_once 'WordPressEdits.php';
( new WordPressEdits )->init();