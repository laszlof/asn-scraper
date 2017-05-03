# asn-scraper

This utility demonstrates how to scrape a list of ASN's off http://bgp.he.net/report/world and output them in JSON format.

I've written this in a couple different languages for portability.



## PHP

This version requires composer to operate. You can install composer by visiting https://getcomposer.org/

Once composer is installed, execute the following commands.

```
# cd asn-scraper/php
# composer install
# bin/scraper output.json
```

You can replace output.json with the file of your choice.


## Python

This version requires BeautifulSoup to operate. You can find information on it here: https://www.crummy.com/software/BeautifulSoup/

```
# cd asn-scraper/python
# scraper output.json
```

You can replace output.json with the file of your choice.
