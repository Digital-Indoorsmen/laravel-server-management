# SELinux Policy Makefile for Panel
MODULE=panel

all: $(MODULE).pp

$(MODULE).pp: $(MODULE).mod $(MODULE).fc
	semodule_package -o $(MODULE).pp -m $(MODULE).mod -f $(MODULE).fc

$(MODULE).mod: $(MODULE).te
	checkmodule -M -m -o $(MODULE).mod $(MODULE).te

install: $(MODULE).pp
	semodule -i $(MODULE).pp

clean:
	rm -f $(MODULE).mod $(MODULE).pp

.PHONY: all install clean
