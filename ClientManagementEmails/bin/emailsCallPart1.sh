#/bin/bash
php /home/visar/CRMTool/ClientManagementEmails/bin/downloadEmailsCSV.php
ls /home/visar/CRMTool/ClientManagementEmails/credentials | parallel -j0 php /home/visar/CRMTool/ClientManagementEmails/bin/downloadEmails.php

