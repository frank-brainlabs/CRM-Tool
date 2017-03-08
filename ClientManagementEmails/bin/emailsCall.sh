#/bin/bash

php ~/projects/CRMTool/ClientManagementEmails/bin/downloadEmailsCSV.php

php ~/projects/CRMTool/ClientManagementEmails/bin/test_credentials.php

ls ~/projects/CRMTool/ClientManagementEmails/valid_credentials | parallel php ~/projects/CRMTool/ClientManagementEmails/bin/processEmails.php 

php ~/projects/CRMTool/ClientManagementEmails/bin/combineOutputs.php

php ~/projects/CRMTool/ClientManagementEmails/bin/uploadEmailsCSV.php
