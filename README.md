# PHP Script to create gmail draft

From csv formatted personal information, using Gmail API and PHP.

# How to run

1. Prepare a Google account with Gmail enabled.

See below,
https://developers.google.com/gmail/api/quickstart/php

2. Install php libraries

$ composer install

3. Prepare template email body and csv formatted personal data
* Template : message.txt
* Personal data : member_list.csv

3. Run php script

$ php create_gmail_draft_from_csv.php

Gmail drafts will be created, see your Gmail viewer.

# See also

