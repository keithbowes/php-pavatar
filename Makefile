SHELL = /bin/sh

SRCDIR ?= $(shell $(PHP) -r 'echo getcwd();')

MV ?= mv -f
PHP ?= php

ZIP = zip
ZIPIN = $(addprefix $(notdir $(SRCDIR))/,_pavatar.inc.php _pavatar.plugin.php pavatar-wordpress.php \
	locales/messages.pot locales/README.txt $(wildcard locales/*/_global.php) $(wildcard locales/*/LC_MESSAGES/messages.po) \
	README.html)
ZIPOUT = $(SRCDIR)/pavatar-$(VERSION).zip

VERSION = $(shell $(PHP) -r 'include "_pavatar.inc.php"; global $$_pavatar_version; _pavatar_setVersion(); echo $$_pavatar_version;')

in2out = $(PHP) -r "\$$in = file_get_contents('$(1).in'); \$$in = str_replace('@VERSION@', '$(VERSION)', \$$in); \$$fh=fopen('$(1)', 'w');fwrite(\$$fh, \$$in); fclose(\$$fh);"

all: README.html pavatar-wordpress.php
	
dist zip: all $(ZIPOUT)
	@$(MV) $(ZIPOUT) ..

README.html: README.html.in _pavatar.inc.php
	$(call in2out,$@)

pavatar-wordpress.php: _pavatar.inc.php pavatar-wordpress.php.in
	$(call in2out,$@)

$(ZIPOUT):
	cd .. && $(ZIP) $(ZIPOUT) $(ZIPIN)
