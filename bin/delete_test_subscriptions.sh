#!/bin/bash
php plesk_listSubscriptions.php  |grep "'name'.*'\(test\|detain-qa\)" | cut -d\' -f4 | xargs -r -n 1 php plesk_deleteSubscription.php name 
