#!/bin/bash
php plesk_list_sites.php  |grep "'name'.*'\(test\|detain-qa\)" |cut -d\' -f4 | xargs -r -n 1 php plesk_delete_site.php username
