#!/bin/bash
php plesk_listClients.php  |grep "'login'.*'\(test\|detain\)" |cut -d\' -f4 | xargs -r -n 1 php plesk_deleteClient.php username
