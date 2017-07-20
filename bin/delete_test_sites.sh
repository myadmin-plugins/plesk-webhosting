#!/bin/bash
php plesk_listSites.php  |grep "'name'.*'\(test\|detain-qa\)" |cut -d\' -f4 | xargs -r -n "1" php plesk_deleteSite.php username
