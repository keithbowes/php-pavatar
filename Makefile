SHELL = /bin/sh

SRCDIR ?= $(shell $(PHP) -r 'echo getcwd();')

MV ?= mv -f
PHP ?= php

ZIP = zip
ZIPIN = $(addprefix $(notdir $(SRCDIR))/,_pavatar.inc.php _pavatar.plugin.php pavatar-wordpress.php \
	locales/README.txt locales/eo/_global.php \
	README.html)
ZIPOUT = $(SRCDIR)/pavatar-$(VERSION).zip

VERSION = $(shell $(PHP) -r 'include "_pavatar.inc.php"; global $$_pavatar_version; _pavatar_setVersion(); echo $$_pavatar_version;')

all dist zip: pavatar-wordpress.php $(ZIPOUT)
	@$(MV) pavatar-$(VERSION).zip ..

pavatar-wordpress.php: _pavatar.inc.php pavatar-wordpress.php.in
	$(shell $(PHP) -r "\$$in = file_get_contents('pavatar-wordpress.php.in'); \$$in = str_replace('@VERSION@', '$(VERSION)', \$$in); file_put_contents('$@', \$$in);")

$(ZIPOUT):
	cd .. && $(ZIP) $(ZIPOUT) $(ZIPIN)
